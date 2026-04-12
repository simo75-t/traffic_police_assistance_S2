import os

from django.core.exceptions import ImproperlyConfigured

from core.env_loader import load_project_env


load_project_env()


# Read the runtime value from env first, then Django settings, then fallback.
def get_runtime_setting(name, default=None, cast=None):
    value = os.getenv(name)

    if value is None:
        try:
            from django.conf import settings

            value = getattr(settings, name, default)
        except (ImproperlyConfigured, ImportError):
            value = default

    if cast is None or value is None:
        return value

    return cast(value)
