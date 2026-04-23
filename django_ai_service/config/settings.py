"""
Django settings for config project.
"""

import os
from pathlib import Path

from core import default_settings
from core.env_loader import load_project_env

# Build paths inside the project like this: BASE_DIR / 'subdir'.
BASE_DIR = Path(__file__).resolve().parent.parent

load_project_env()


def _env_bool(name: str, default: bool) -> bool:
    value = os.getenv(name)
    if value is None:
        return default
    return value.strip().lower() in {"1", "true", "yes", "on"}


# Quick-start development settings - unsuitable for production
# See https://docs.djangoproject.com/en/6.0/howto/deployment/checklist/

# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = os.getenv("DJANGO_SECRET_KEY", default_settings.SECRET_KEY)

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = _env_bool("DJANGO_DEBUG", default_settings.DEBUG)

ALLOWED_HOSTS = [
    host.strip()
    for host in os.getenv("DJANGO_ALLOWED_HOSTS", ",".join(default_settings.ALLOWED_HOSTS)).split(",")
    if host.strip()
]


# Application definition

INSTALLED_APPS = [
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "rest_framework",
    "core.apps.CoreConfig",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "config.urls"

TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
            ],
        },
    },
]

WSGI_APPLICATION = "config.wsgi.application"


# Database
# https://docs.djangoproject.com/en/6.0/ref/settings/#databases

DATABASES = {
    "default": {
        "ENGINE": "djongo",
        "NAME": "tpa_db",
        "CLIENT": {
            "host": "mongodb://localhost:27017/",
        },
    }
}


# Password validation
# https://docs.djangoproject.com/en/6.0/ref/settings/#auth-password-validators

AUTH_PASSWORD_VALIDATORS = [
    {
        "NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator",
    },
    {
        "NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
    },
    {
        "NAME": "django.contrib.auth.password_validation.CommonPasswordValidator",
    },
    {
        "NAME": "django.contrib.auth.password_validation.NumericPasswordValidator",
    },
]


# Internationalization
# https://docs.djangoproject.com/en/6.0/topics/i18n/

LANGUAGE_CODE = "en-us"

TIME_ZONE = "UTC"

USE_I18N = True

USE_TZ = True


# Static files (CSS, JavaScript, Images)
# https://docs.djangoproject.com/en/6.0/howto/static-files/

STATIC_URL = "static/"


# Worker settings
RABBITMQ_HOST = os.getenv("RABBITMQ_HOST", default_settings.RABBITMQ_HOST)
RABBITMQ_PORT = int(os.getenv("RABBITMQ_PORT", str(default_settings.RABBITMQ_PORT)))
RABBITMQ_USER = os.getenv("RABBITMQ_USER", default_settings.RABBITMQ_USER)
RABBITMQ_PASSWORD = os.getenv("RABBITMQ_PASSWORD", default_settings.RABBITMQ_PASSWORD)
RABBITMQ_VHOST = os.getenv("RABBITMQ_VHOST", default_settings.RABBITMQ_VHOST)

AI_RMQ_EXCHANGE = os.getenv("AI_RMQ_EXCHANGE", default_settings.AI_RMQ_EXCHANGE)
AI_RMQ_RESULTS_ROUTING_KEY = os.getenv("AI_RMQ_RESULTS_ROUTING_KEY", default_settings.AI_RMQ_RESULTS_ROUTING_KEY)

AI_RMQ_STT_QUEUE = os.getenv("AI_RMQ_STT_QUEUE", default_settings.AI_RMQ_STT_QUEUE)
AI_RMQ_STT_ROUTING_KEY = os.getenv("AI_RMQ_STT_ROUTING_KEY", default_settings.AI_RMQ_STT_ROUTING_KEY)

AI_RMQ_OCR_QUEUE = os.getenv("AI_RMQ_OCR_QUEUE", default_settings.AI_RMQ_OCR_QUEUE)
AI_RMQ_OCR_ROUTING_KEY = os.getenv("AI_RMQ_OCR_ROUTING_KEY", default_settings.AI_RMQ_OCR_ROUTING_KEY)
AI_RMQ_HEATMAP_QUEUE = os.getenv("AI_RMQ_HEATMAP_QUEUE", default_settings.AI_RMQ_HEATMAP_QUEUE)
AI_RMQ_HEATMAP_ROUTING_KEY = os.getenv("AI_RMQ_HEATMAP_ROUTING_KEY", default_settings.AI_RMQ_HEATMAP_ROUTING_KEY)

LARAVEL_BASE_URL = os.getenv("LARAVEL_BASE_URL", default_settings.LARAVEL_BASE_URL)
LARAVEL_API_PREFIX = os.getenv("LARAVEL_API_PREFIX", default_settings.LARAVEL_API_PREFIX)
LARAVEL_VIOLATIONS_API = os.getenv("LARAVEL_VIOLATIONS_API", default_settings.LARAVEL_VIOLATIONS_API)
LARAVEL_API_TIMEOUT = int(os.getenv("LARAVEL_API_TIMEOUT", str(default_settings.LARAVEL_API_TIMEOUT)))

WHISPER_MODEL_NAME = os.getenv("WHISPER_MODEL_NAME", default_settings.WHISPER_MODEL_NAME)
WHISPER_LANGUAGE = os.getenv("WHISPER_LANGUAGE", default_settings.WHISPER_LANGUAGE)

LMSTUDIO_BASE_URL = os.getenv("LMSTUDIO_BASE_URL", default_settings.LMSTUDIO_BASE_URL)
LMSTUDIO_MODEL = os.getenv("LMSTUDIO_MODEL", default_settings.LMSTUDIO_MODEL)
LMSTUDIO_TIMEOUT = int(os.getenv("LMSTUDIO_TIMEOUT", str(default_settings.LMSTUDIO_TIMEOUT)))

MONGO_URL = os.getenv("MONGO_URL", default_settings.MONGO_URL)
MONGO_DB = os.getenv("MONGO_DB", default_settings.MONGO_DB)
MONGO_OCR_COLLECTION = os.getenv("MONGO_OCR_COLLECTION", default_settings.MONGO_OCR_COLLECTION)

OLLAMA_URL = os.getenv("OLLAMA_URL", default_settings.OLLAMA_URL)
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", default_settings.OLLAMA_MODEL)
OLLAMA_TIMEOUT = int(os.getenv("OLLAMA_TIMEOUT", str(default_settings.OLLAMA_TIMEOUT)))
OLLAMA_NUM_CTX = int(os.getenv("OLLAMA_NUM_CTX", str(default_settings.OLLAMA_NUM_CTX)))
OLLAMA_NUM_PREDICT = int(os.getenv("OLLAMA_NUM_PREDICT", str(default_settings.OLLAMA_NUM_PREDICT)))
OLLAMA_RETRIES = int(os.getenv("OLLAMA_RETRIES", str(default_settings.OLLAMA_RETRIES)))

HEATMAP_CACHE_TTL_SECONDS = int(os.getenv("HEATMAP_CACHE_TTL_SECONDS", str(default_settings.HEATMAP_CACHE_TTL_SECONDS)))
HEATMAP_GRID_DEFAULT_METERS = int(os.getenv("HEATMAP_GRID_DEFAULT_METERS", str(default_settings.HEATMAP_GRID_DEFAULT_METERS)))
HEATMAP_GRID_MIN_METERS = int(os.getenv("HEATMAP_GRID_MIN_METERS", str(default_settings.HEATMAP_GRID_MIN_METERS)))
HEATMAP_GRID_MAX_METERS = int(os.getenv("HEATMAP_GRID_MAX_METERS", str(default_settings.HEATMAP_GRID_MAX_METERS)))
HEATMAP_GRID_MAX_CELLS = int(os.getenv("HEATMAP_GRID_MAX_CELLS", str(default_settings.HEATMAP_GRID_MAX_CELLS)))
HEATMAP_TOP_N = int(os.getenv("HEATMAP_TOP_N", str(default_settings.HEATMAP_TOP_N)))
HEATMAP_BANDWIDTH_METERS = int(os.getenv("HEATMAP_BANDWIDTH_METERS", str(default_settings.HEATMAP_BANDWIDTH_METERS)))
HEATMAP_SYNTHETIC_POINTS = int(os.getenv("HEATMAP_SYNTHETIC_POINTS", str(default_settings.HEATMAP_SYNTHETIC_POINTS)))
HEATMAP_VIOLATIONS_PAGE_SIZE = int(os.getenv("HEATMAP_VIOLATIONS_PAGE_SIZE", str(default_settings.HEATMAP_VIOLATIONS_PAGE_SIZE)))
HEATMAP_MIN_VISIBLE_INTENSITY = float(
    os.getenv("HEATMAP_MIN_VISIBLE_INTENSITY", str(default_settings.HEATMAP_MIN_VISIBLE_INTENSITY))
)
HEATMAP_MAX_RETURN_POINTS = int(os.getenv("HEATMAP_MAX_RETURN_POINTS", str(default_settings.HEATMAP_MAX_RETURN_POINTS)))
