"""Heatmap prediction RabbitMQ consumer."""

import json
import logging
import time
from typing import Any, Dict, Optional

import pika
from pika.exceptions import AMQPConnectionError, StreamLostError

from core import default_settings
from core.heatmap_prediction.orchestrator import HeatmapPredictionOrchestrator
from core.runtime_settings import get_runtime_setting


logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
log = logging.getLogger("HEATMAP_PREDICTION_WORKER")

RABBIT_HOST = get_runtime_setting("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBIT_PORT = get_runtime_setting("RABBITMQ_PORT", default_settings.RABBITMQ_PORT, int)
RABBIT_USER = get_runtime_setting("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBIT_PASS = get_runtime_setting("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBIT_VHOST = get_runtime_setting("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST)

EXCHANGE = get_runtime_setting("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
JOBS_QUEUE = get_runtime_setting(
    "AI_RMQ_HEATMAP_PREDICTION_QUEUE",
    default_settings.AI_RMQ_HEATMAP_PREDICTION_QUEUE,
)
JOBS_ROUTING_KEY = get_runtime_setting(
    "AI_RMQ_HEATMAP_PREDICTION_ROUTING_KEY",
    default_settings.AI_RMQ_HEATMAP_PREDICTION_ROUTING_KEY,
)
RESULTS_ROUTING_KEY = get_runtime_setting("AI_RMQ_RESULTS_ROUTING_KEY", default_settings.AI_RMQ_RESULTS_ROUTING_KEY)

orchestrator = HeatmapPredictionOrchestrator()


def rabbit_connection():
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


def on_message(channel, method, properties, body_bytes):
    payload = None
    try:
        payload = json.loads(body_bytes)
        correlation_id = payload.get("correlation_id") or getattr(properties, "correlation_id", None)
        request_id = payload.get("request_id")
        log.info("HEATMAP PREDICTION JOB STARTED request_id=%s", request_id)
        result = orchestrator.generate_prediction(payload)
        publish_result(
            channel,
            {
                "job_id": request_id,
                "request_id": request_id,
                "status": "success",
                "result": result,
                "error": None,
                "type": "generate_heatmap_prediction",
            },
            correlation_id=correlation_id,
        )
        channel.basic_ack(method.delivery_tag)
    except Exception as exc:
        log.exception("HEATMAP PREDICTION JOB FAILED")
        if isinstance(payload, dict):
            orchestrator.fail_job(payload, exc)
        try:
            publish_result(
                channel,
                {
                    "job_id": payload.get("request_id") if isinstance(payload, dict) else None,
                    "request_id": payload.get("request_id") if isinstance(payload, dict) else None,
                    "status": "failed",
                    "result": None,
                    "error": str(exc),
                    "type": "generate_heatmap_prediction",
                },
                correlation_id=(payload.get("correlation_id") if isinstance(payload, dict) else None),
            )
            channel.basic_ack(method.delivery_tag)
        except Exception:
            channel.basic_nack(method.delivery_tag, requeue=True)


def consume_forever():
    conn = rabbit_connection()
    ch = conn.channel()
    ch.exchange_declare(exchange=EXCHANGE, exchange_type="direct", durable=True)
    ch.queue_declare(queue=JOBS_QUEUE, durable=True)
    ch.queue_bind(queue=JOBS_QUEUE, exchange=EXCHANGE, routing_key=JOBS_ROUTING_KEY)
    ch.basic_qos(prefetch_count=1)
    ch.basic_consume(queue=JOBS_QUEUE, on_message_callback=on_message, auto_ack=False)
    log.info("Heatmap prediction worker listening queue=%s routing_key=%s", JOBS_QUEUE, JOBS_ROUTING_KEY)
    ch.start_consuming()


def main():
    while True:
        try:
            consume_forever()
        except KeyboardInterrupt:
            log.info("Stopping heatmap prediction worker...")
            break
        except (AMQPConnectionError, StreamLostError, ConnectionResetError, OSError) as exc:
            log.warning("RabbitMQ disconnected. reconnecting... err=%r", exc)
            time.sleep(3)
        except Exception as exc:
            log.warning("Unexpected worker error. restarting... err=%r", exc)
            time.sleep(3)
