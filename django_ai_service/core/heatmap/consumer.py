"""Heatmap RabbitMQ consumer.

This file manages RabbitMQ connectivity and message lifecycle for heatmap jobs.
"""

import json
import logging
import time
from typing import Any, Dict, Optional

import pika
from pika.exceptions import AMQPConnectionError, StreamLostError

from core import default_settings
from core.heatmap.orchestrator import HeatmapOrchestrator
from core.runtime_settings import get_runtime_setting


logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
log = logging.getLogger("HEATMAP_WORKER")

RABBIT_HOST = get_runtime_setting("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBIT_PORT = get_runtime_setting("RABBITMQ_PORT", default_settings.RABBITMQ_PORT, int)
RABBIT_USER = get_runtime_setting("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBIT_PASS = get_runtime_setting("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBIT_VHOST = get_runtime_setting("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST)

EXCHANGE = get_runtime_setting("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
JOBS_QUEUE = get_runtime_setting("AI_RMQ_HEATMAP_QUEUE", default_settings.AI_RMQ_HEATMAP_QUEUE)
JOBS_ROUTING_KEY = get_runtime_setting("AI_RMQ_HEATMAP_ROUTING_KEY", default_settings.AI_RMQ_HEATMAP_ROUTING_KEY)
RESULTS_ROUTING_KEY = get_runtime_setting("AI_RMQ_RESULTS_ROUTING_KEY", default_settings.AI_RMQ_RESULTS_ROUTING_KEY)

orchestrator = HeatmapOrchestrator()


def rabbit_connection():
    """Build one RabbitMQ blocking connection for the heatmap worker."""
    creds = pika.PlainCredentials(RABBIT_USER, RABBIT_PASS)
    params = pika.ConnectionParameters(
        host=RABBIT_HOST,
        port=RABBIT_PORT,
        virtual_host=RABBIT_VHOST,
        credentials=creds,
        heartbeat=300,
        blocked_connection_timeout=600,
        socket_timeout=30,
        connection_attempts=10,
        retry_delay=5,
    )
    return pika.BlockingConnection(params)


def publish_result(channel, payload: Dict[str, Any], correlation_id: Optional[str]) -> None:
    """Publish one heatmap success or failure message to the shared result route."""
    channel.basic_publish(
        exchange=EXCHANGE,
        routing_key=RESULTS_ROUTING_KEY,
        body=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        properties=pika.BasicProperties(
            content_type="application/json",
            delivery_mode=2,
            correlation_id=correlation_id,
        ),
    )


def consume_forever():
    """Start the heatmap consumer loop and handle one message at a time."""
    conn = rabbit_connection()
    ch = conn.channel()

    ch.exchange_declare(exchange=EXCHANGE, exchange_type="direct", durable=True)
    ch.queue_declare(queue=JOBS_QUEUE, durable=True)
    ch.queue_bind(queue=JOBS_QUEUE, exchange=EXCHANGE, routing_key=JOBS_ROUTING_KEY)
    ch.basic_qos(prefetch_count=1)

    def on_message(_ch, method, properties, body):
        """Parse one message, run analysis, then ack or nack safely."""
        payload = None
        try:
            payload = json.loads(body)
            correlation_id = payload.get("correlation_id") or getattr(properties, "correlation_id", None)
            request_id = payload.get("request_id")

            log.info("HEATMAP JOB STARTED request_id=%s", request_id)
            result = orchestrator.generate_heatmap(payload)

            publish_result(
                ch,
                {
                    "job_id": request_id,
                    "request_id": request_id,
                    "status": "success",
                    "result": result,
                    "error": None,
                    "type": "generate_heatmap",
                },
                correlation_id=correlation_id,
            )
            ch.basic_ack(method.delivery_tag)
            log.info("HEATMAP JOB FINISHED request_id=%s", request_id)
        except Exception as exc:
            log.exception("HEATMAP JOB FAILED")
            if isinstance(payload, dict):
                orchestrator.fail_job(payload, exc)
            try:
                publish_result(
                    ch,
                    {
                        "job_id": payload.get("request_id") if isinstance(payload, dict) else None,
                        "request_id": payload.get("request_id") if isinstance(payload, dict) else None,
                        "status": "failed",
                        "result": None,
                        "error": str(exc),
                        "type": "generate_heatmap",
                    },
                    correlation_id=(payload.get("correlation_id") if isinstance(payload, dict) else None),
                )
            finally:
                ch.basic_nack(method.delivery_tag, requeue=False)

    ch.basic_consume(queue=JOBS_QUEUE, on_message_callback=on_message, auto_ack=False)
    log.info("Heatmap worker listening queue=%s routing_key=%s", JOBS_QUEUE, JOBS_ROUTING_KEY)
    ch.start_consuming()


def main():
    """Keep the heatmap worker alive and reconnect when RabbitMQ drops."""
    while True:
        try:
            consume_forever()
        except KeyboardInterrupt:
            log.info("Stopping heatmap worker...")
            break
        except (AMQPConnectionError, StreamLostError, ConnectionResetError, OSError) as exc:
            log.warning("RabbitMQ disconnected. reconnecting... err=%r", exc)
            time.sleep(3)
        except Exception as exc:
            log.warning("Unexpected worker error. restarting... err=%r", exc)
            time.sleep(3)
