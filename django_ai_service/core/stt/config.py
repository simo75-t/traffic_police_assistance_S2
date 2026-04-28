"""STT configuration values.

This module preserves the legacy names the STT pipeline imports while also
supporting newer env names that were introduced for OpenRouter/Qwen.
"""

import logging
from urllib.parse import unquote, urlparse

import whisper

from core import default_settings
from core.runtime_settings import get_runtime_setting


def _clean_vhost(value: str) -> str:
    value = (value or "/").strip()
    if not value or value == "/":
        return "/"
    return value if value.startswith("/") else f"/{value}"


def _split_rabbitmq_url(url: str):
    parsed = urlparse((url or "").strip())
    if parsed.scheme not in {"amqp", "amqps"} or not parsed.hostname:
        return None

    username = unquote(parsed.username or "")
    password = unquote(parsed.password or "")
    vhost = unquote(parsed.path[1:]) if parsed.path and parsed.path != "/" else "/"
    return {
        "host": parsed.hostname,
        "port": parsed.port or 5672,
        "user": username or None,
        "password": password or None,
        "vhost": _clean_vhost(vhost),
    }


logging.basicConfig(level=logging.INFO, format="%(asctime)s | %(levelname)s | %(message)s")
log = logging.getLogger("core.stt")

_rabbitmq_url = get_runtime_setting("RABBITMQ_URL") or get_runtime_setting("CELERY_BROKER_URL")
_rabbitmq_parts = _split_rabbitmq_url(_rabbitmq_url) if _rabbitmq_url else None

RABBIT_HOST = _rabbitmq_parts["host"] if _rabbitmq_parts else get_runtime_setting("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBIT_PORT = (
    int(_rabbitmq_parts["port"])
    if _rabbitmq_parts
    else get_runtime_setting("RABBITMQ_PORT", default_settings.RABBITMQ_PORT, int)
)
RABBIT_USER = _rabbitmq_parts["user"] if _rabbitmq_parts and _rabbitmq_parts["user"] is not None else get_runtime_setting("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBIT_PASS = _rabbitmq_parts["password"] if _rabbitmq_parts and _rabbitmq_parts["password"] is not None else get_runtime_setting("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBIT_VHOST = _rabbitmq_parts["vhost"] if _rabbitmq_parts else _clean_vhost(get_runtime_setting("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST))

AI_EXCHANGE = get_runtime_setting("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
STT_QUEUE = get_runtime_setting("AI_RMQ_STT_QUEUE", default_settings.AI_RMQ_STT_QUEUE)
STT_ROUTING_KEY = get_runtime_setting("AI_RMQ_STT_ROUTING_KEY", default_settings.AI_RMQ_STT_ROUTING_KEY)
RESULT_ROUTING_KEY = get_runtime_setting(
    "AI_RMQ_RESULTS_ROUTING_KEY",
    default_settings.AI_RMQ_RESULTS_ROUTING_KEY,
)
AI_PREFETCH_COUNT = get_runtime_setting("AI_PREFETCH_COUNT", 1, int)

LARAVEL_BASE_URL = str(get_runtime_setting("LARAVEL_BASE_URL", default_settings.LARAVEL_BASE_URL)).rstrip("/")
LARAVEL_API_PREFIX = str(get_runtime_setting("LARAVEL_API_PREFIX", default_settings.LARAVEL_API_PREFIX)).strip()
LARAVEL_VIOLATIONS_API = str(get_runtime_setting("LARAVEL_VIOLATIONS_API", default_settings.LARAVEL_VIOLATIONS_API)).strip()
CITIES_API = str(
    get_runtime_setting(
        "CITIES_API",
        f"{LARAVEL_BASE_URL}{LARAVEL_API_PREFIX}/cities",
    )
).strip()
VIOLATION_TYPES_API = str(
    get_runtime_setting(
        "VIOLATION_TYPES_API",
        LARAVEL_VIOLATIONS_API,
    )
).strip()

WHISPER_MODEL_NAME = get_runtime_setting("WHISPER_MODEL_NAME", default_settings.WHISPER_MODEL_NAME)
WHISPER_LANGUAGE = get_runtime_setting("WHISPER_LANGUAGE", default_settings.WHISPER_LANGUAGE)
whisper_model = whisper.load_model(WHISPER_MODEL_NAME)

QWEN_MODEL = str(get_runtime_setting("QWEN_MODEL", default_settings.QWEN_MODEL)).strip()
OPENROUTER_API_KEY = str(
    get_runtime_setting(
        "OPENROUTER_API_KEY",
        get_runtime_setting("QWEN_API_KEY", ""),
    )
    or ""
).strip()
OPENROUTER_BASE_URL = str(
    get_runtime_setting(
        "OPENROUTER_BASE_URL",
        get_runtime_setting("QWEN_BASE_URL", default_settings.QWEN_BASE_URL),
    )
).rstrip("/")
OPENROUTER_REFERER = str(get_runtime_setting("OPENROUTER_REFERER", get_runtime_setting("APP_URL", "")) or "").strip()
OPENROUTER_TITLE = str(
    get_runtime_setting(
        "OPENROUTER_TITLE",
        get_runtime_setting("APP_NAME", "django_ai_service"),
    )
    or ""
).strip()
LLM_TIMEOUT = float(
    get_runtime_setting(
        "LLM_TIMEOUT",
        get_runtime_setting("LMSTUDIO_TIMEOUT", default_settings.LMSTUDIO_TIMEOUT),
        float,
    )
)
LLM_PROVIDER = (
    "openrouter"
    if "openrouter" in QWEN_MODEL.lower() or "openrouter" in OPENROUTER_BASE_URL.lower()
    else "generic"
)
LLM_CHAT_URL = f"{OPENROUTER_BASE_URL}/chat/completions"

# Backward-compatible aliases for any remaining imports.
RABBITMQ_URL = _rabbitmq_url or f"amqp://{RABBIT_USER}:{RABBIT_PASS}@{RABBIT_HOST}:{RABBIT_PORT}{RABBIT_VHOST}"
AI_QUEUE_STT = STT_QUEUE
AI_ROUTING_KEY_STT = STT_ROUTING_KEY
AI_RESULT_QUEUE = RESULT_ROUTING_KEY
LMSTUDIO_MODEL = QWEN_MODEL
LMSTUDIO_CHAT = LLM_CHAT_URL
LMSTUDIO_TIMEOUT = LLM_TIMEOUT
