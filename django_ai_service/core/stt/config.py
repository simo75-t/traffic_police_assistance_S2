"""STT configuration values.

This file centralizes RabbitMQ, Whisper, LM Studio, and Laravel lookup settings.
"""

import logging

import whisper

from core import default_settings
from core.runtime_settings import get_runtime_setting


logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
log = logging.getLogger("STT_WORKER_BEST")

RABBIT_HOST = get_runtime_setting("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBIT_PORT = get_runtime_setting("RABBITMQ_PORT", default_settings.RABBITMQ_PORT, int)
RABBIT_USER = get_runtime_setting("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBIT_PASS = get_runtime_setting("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBIT_VHOST = get_runtime_setting("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST)

AI_EXCHANGE = get_runtime_setting("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
STT_QUEUE = get_runtime_setting("AI_RMQ_STT_QUEUE", default_settings.AI_RMQ_STT_QUEUE)
STT_ROUTING_KEY = get_runtime_setting("AI_RMQ_STT_ROUTING_KEY", default_settings.AI_RMQ_STT_ROUTING_KEY)
RESULT_ROUTING_KEY = get_runtime_setting("AI_RMQ_RESULTS_ROUTING_KEY", default_settings.AI_RMQ_RESULTS_ROUTING_KEY)

LARAVEL_BASE = get_runtime_setting("LARAVEL_BASE_URL", default_settings.LARAVEL_BASE_URL)
LARAVEL_API_PREFIX = get_runtime_setting("LARAVEL_API_PREFIX", default_settings.LARAVEL_API_PREFIX)
CITIES_API = f"{LARAVEL_BASE}{LARAVEL_API_PREFIX}/ai_cities"
VIOLATION_TYPES_API = f"{LARAVEL_BASE}{LARAVEL_API_PREFIX}/ai_violation-types"

WHISPER_MODEL_NAME = get_runtime_setting("WHISPER_MODEL_NAME", default_settings.WHISPER_MODEL_NAME)
WHISPER_LANGUAGE = get_runtime_setting("WHISPER_LANGUAGE", default_settings.WHISPER_LANGUAGE)

LMSTUDIO_BASE = get_runtime_setting("LMSTUDIO_BASE_URL", default_settings.LMSTUDIO_BASE_URL)
LMSTUDIO_CHAT = f"{LMSTUDIO_BASE}/v1/chat/completions"
LMSTUDIO_MODEL = get_runtime_setting("LMSTUDIO_MODEL", default_settings.LMSTUDIO_MODEL)
LMSTUDIO_TIMEOUT = get_runtime_setting("LMSTUDIO_TIMEOUT", default_settings.LMSTUDIO_TIMEOUT, int)

log.info("Loading Whisper model...")
whisper_model = whisper.load_model(WHISPER_MODEL_NAME)
log.info("Whisper model loaded")
