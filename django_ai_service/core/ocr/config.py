"""OCR configuration values.

This file centralizes RabbitMQ, MongoDB, and Ollama settings.
"""

import logging

import requests
from pymongo import MongoClient

from core import default_settings
from core.runtime_settings import get_runtime_setting


logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
log = logging.getLogger("OCR_WORKER")

RABBIT_HOST = get_runtime_setting("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBIT_PORT = get_runtime_setting("RABBITMQ_PORT", default_settings.RABBITMQ_PORT, int)
RABBIT_USER = get_runtime_setting("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBIT_PASS = get_runtime_setting("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBIT_VHOST = get_runtime_setting("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST)

EXCHANGE = get_runtime_setting("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
JOBS_QUEUE = get_runtime_setting("AI_RMQ_OCR_QUEUE", default_settings.AI_RMQ_OCR_QUEUE)
JOBS_ROUTING_KEY = get_runtime_setting("AI_RMQ_OCR_ROUTING_KEY", default_settings.AI_RMQ_OCR_ROUTING_KEY)
RESULTS_ROUTING_KEY = get_runtime_setting("AI_RMQ_RESULTS_ROUTING_KEY", default_settings.AI_RMQ_RESULTS_ROUTING_KEY)

MONGO_URL = get_runtime_setting("MONGO_URL", default_settings.MONGO_URL)
mongo = MongoClient(MONGO_URL)
db = mongo[get_runtime_setting("MONGO_DB", default_settings.MONGO_DB)]
results_col = db[get_runtime_setting("MONGO_OCR_COLLECTION", default_settings.MONGO_OCR_COLLECTION)]

OLLAMA_URL = get_runtime_setting("OLLAMA_URL", default_settings.OLLAMA_URL)
OLLAMA_MODEL = get_runtime_setting("OLLAMA_MODEL", default_settings.OLLAMA_MODEL)
OLLAMA_TIMEOUT = get_runtime_setting("OLLAMA_TIMEOUT", default_settings.OLLAMA_TIMEOUT, int)
OLLAMA_NUM_CTX = get_runtime_setting("OLLAMA_NUM_CTX", default_settings.OLLAMA_NUM_CTX, int)
OLLAMA_NUM_PREDICT = get_runtime_setting("OLLAMA_NUM_PREDICT", default_settings.OLLAMA_NUM_PREDICT, int)
OLLAMA_RETRIES = get_runtime_setting("OLLAMA_RETRIES", default_settings.OLLAMA_RETRIES, int)

OLLAMA_SESSION = requests.Session()
OLLAMA_SESSION.trust_env = False
