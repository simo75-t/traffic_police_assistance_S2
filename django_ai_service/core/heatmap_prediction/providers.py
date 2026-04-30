from __future__ import annotations

FALLBACK_PROVIDER_NAMES = {"fallback", "rule_based", "rules", "heuristic"}
OPENAI_PROVIDER_NAME = "openai"
QWEN_PROVIDER_NAME = "qwen_api"


def resolve_prediction_source(provider: str, failed: bool) -> str:
    normalized = str(provider or "").strip().lower()

    if not failed:
        if normalized in FALLBACK_PROVIDER_NAMES:
            return "fallback"
        if normalized == OPENAI_PROVIDER_NAME:
            return OPENAI_PROVIDER_NAME
        if normalized == QWEN_PROVIDER_NAME:
            return "qwen_api"
        return normalized

    if normalized == OPENAI_PROVIDER_NAME:
        return "fallback_after_openai_failure"
    if normalized == QWEN_PROVIDER_NAME:
        return "fallback_after_qwen_failure"
    if normalized in FALLBACK_PROVIDER_NAMES:
        return "fallback"
    return "fallback_after_provider_failure"
