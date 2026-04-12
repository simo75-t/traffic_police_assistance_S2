"""OCR RabbitMQ consumer.

This file manages RabbitMQ connectivity and message lifecycle for OCR jobs.
"""

import json
import time
from typing import Any, Dict, Optional

import pika
from pika.exceptions import AMQPConnectionError, StreamLostError

from core.ocr.config import (
    EXCHANGE,
    JOBS_QUEUE,
    JOBS_ROUTING_KEY,
    RABBIT_HOST,
    RABBIT_PASS,
    RABBIT_PORT,
    RABBIT_USER,
    RABBIT_VHOST,
    RESULTS_ROUTING_KEY,
    log,
)
from core.ocr.serialization import to_jsonable
from core.ocr.service import handle_job


def rabbit_connection():
    """Build one RabbitMQ blocking connection for the OCR worker."""
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
    """Publish one OCR success or failure message to the shared result queue."""
    if not channel.is_open:
        return
    channel.basic_publish(
        exchange=EXCHANGE,
        routing_key=RESULTS_ROUTING_KEY,
        body=json.dumps(to_jsonable(payload), ensure_ascii=False).encode("utf-8"),
        properties=pika.BasicProperties(
            content_type="application/json",
            delivery_mode=2,
            correlation_id=correlation_id,
        ),
    )


def consume_forever():
    """Start the OCR consumer loop and handle one message at a time."""
    conn = rabbit_connection()
    ch = conn.channel()
    ch.exchange_declare(exchange=EXCHANGE, exchange_type="direct", durable=True)
    ch.queue_declare(queue=JOBS_QUEUE, durable=True)
    ch.queue_bind(queue=JOBS_QUEUE, exchange=EXCHANGE, routing_key=JOBS_ROUTING_KEY)
    ch.basic_qos(prefetch_count=1)

    def on_message(_ch, method, props, body_bytes):
        """Parse one message, run OCR, then ack or nack safely."""
        payload = None
        try:
            payload = json.loads(body_bytes)
            correlation_id = payload.get("correlation_id") or getattr(props, "correlation_id", None)
            job_id = payload.get("job_id")
            log.info("OCR JOB STARTED: %s", job_id)
            result_payload = handle_job(payload)
            publish_result(
                ch,
                {
                    "job_id": result_payload["job_id"],
                    "request_id": result_payload["job_id"],
                    "status": "success",
                    "result": result_payload,
                    "error": None,
                    "type": "plate_ocr",
                },
                correlation_id=correlation_id,
            )
            ch.basic_ack(method.delivery_tag)
            log.info("OCR JOB FINISHED: %s", job_id)
        except Exception as exc:
            job_id = payload.get("job_id") if isinstance(payload, dict) else None
            correlation_id = payload.get("correlation_id") if isinstance(payload, dict) else getattr(props, "correlation_id", None)
            log.exception("OCR JOB FAILED job_id=%s", job_id)
            try:
                publish_result(
                    ch,
                    {
                        "job_id": job_id,
                        "request_id": job_id,
                        "status": "failed",
                        "result": None,
                        "error": str(exc),
                        "type": "plate_ocr",
                    },
                    correlation_id=correlation_id,
                )
            finally:
                ch.basic_nack(method.delivery_tag, requeue=False)

    ch.basic_consume(queue=JOBS_QUEUE, on_message_callback=on_message, auto_ack=False)
    log.info("AI OCR worker listening queue=%s routing_key=%s", JOBS_QUEUE, JOBS_ROUTING_KEY)
    ch.start_consuming()


def main():
    """Keep the OCR worker alive and reconnect when RabbitMQ drops."""
    while True:
        try:
            consume_forever()
        except KeyboardInterrupt:
            log.info("Stopping OCR worker...")
            break
        except (AMQPConnectionError, StreamLostError, ConnectionResetError, OSError) as exc:
            log.warning("RabbitMQ connection lost. Reconnecting... err=%r", exc)
            time.sleep(3)
        except Exception as exc:
            log.warning("Unexpected OCR worker error. Restarting... err=%r", exc)
            time.sleep(3)
