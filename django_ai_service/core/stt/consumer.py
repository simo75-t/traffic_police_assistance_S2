"""STT RabbitMQ consumer.

This file manages RabbitMQ connectivity and message lifecycle for STT jobs.
"""

import json
import time

import pika
from pika.exceptions import AMQPConnectionError, StreamLostError

from core.stt.config import (
    AI_EXCHANGE,
    RABBIT_HOST,
    RABBIT_PASS,
    RABBIT_PORT,
    RABBIT_USER,
    RABBIT_VHOST,
    RESULT_ROUTING_KEY,
    STT_QUEUE,
    STT_ROUTING_KEY,
    log,
)
from core.stt.service import handle_job


def publish_result(channel, job_id, correlation_id, status, result=None, error=None):
    """Publish one STT success or failure message to the shared result route."""
    payload = {
        "job_id": job_id,
        "request_id": job_id,
        "status": status,
        "result": result,
        "error": error,
        "type": "stt",
    }
    props = pika.BasicProperties(content_type="application/json", correlation_id=correlation_id, delivery_mode=2)
    channel.basic_publish(
        exchange=AI_EXCHANGE,
        routing_key=RESULT_ROUTING_KEY,
        body=json.dumps(payload, ensure_ascii=False),
        properties=props,
    )
    log.info("Result sent for job %s (%s)", job_id, status)


def on_message(ch, method, properties, body):
    """Parse one message, run STT, then ack or nack safely."""
    data = None
    job_id = None
    correlation_id = properties.correlation_id if properties else None
    try:
        data = json.loads(body)
        job_id = data.get("job_id")
        correlation_id = data.get("correlation_id") or correlation_id
        log.info("STT JOB STARTED: %s", job_id)
        result = handle_job(data)
        publish_result(ch, job_id, correlation_id, "success", result=result)
        ch.basic_ack(method.delivery_tag)
        log.info("STT JOB FINISHED: %s", job_id)
    except Exception as exc:
        log.exception("STT JOB FAILED")
        try:
            publish_result(
                ch,
                job_id or (data.get("job_id") if isinstance(data, dict) else None),
                correlation_id,
                "failed",
                error=str(exc),
            )
            ch.basic_ack(method.delivery_tag)
        except Exception:
            ch.basic_nack(method.delivery_tag, requeue=True)


def rabbit_connection():
    """Build one RabbitMQ blocking connection for the STT worker."""
    creds = pika.PlainCredentials(RABBIT_USER, RABBIT_PASS)
    params = pika.ConnectionParameters(
        host=RABBIT_HOST,
        port=RABBIT_PORT,
        virtual_host=RABBIT_VHOST,
        credentials=creds,
        heartbeat=600,
        blocked_connection_timeout=300,
    )
    return pika.BlockingConnection(params)


def consume_forever():
    """Start the STT consumer loop and keep consuming until interrupted."""
    conn = rabbit_connection()
    ch = conn.channel()
    ch.exchange_declare(AI_EXCHANGE, "direct", durable=True)
    ch.queue_declare(STT_QUEUE, durable=True)
    ch.queue_bind(STT_QUEUE, AI_EXCHANGE, STT_ROUTING_KEY)
    ch.basic_qos(prefetch_count=1)
    log.info("Listening: %s", STT_QUEUE)
    ch.basic_consume(queue=STT_QUEUE, on_message_callback=on_message, auto_ack=False)
    ch.start_consuming()


def main():
    """Keep the STT worker alive and reconnect when RabbitMQ drops."""
    while True:
        try:
            consume_forever()
        except KeyboardInterrupt:
            log.info("Stopping STT worker...")
            break
        except (AMQPConnectionError, StreamLostError, ConnectionResetError, OSError) as exc:
            log.warning("RabbitMQ disconnected. reconnecting... err=%r", exc)
            time.sleep(3)
        except Exception as exc:
            log.warning("Unexpected STT worker error. restarting... err=%r", exc)
            time.sleep(3)
