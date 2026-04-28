from __future__ import annotations

import json
import logging
from typing import Any

import requests
from requests import exceptions as requests_exceptions

from core import default_settings
from core.heatmap_prediction.fallbacks import build_fallback_prediction
from core.heatmap_prediction.providers import FALLBACK_PROVIDER_NAMES, QWEN_PROVIDER_NAME
from core.heatmap_prediction.schema import PredictionSchemaError
from core.heatmap_prediction.schema import validate_prediction_output
from core.runtime_settings import get_runtime_setting
from core.utils.llm_extract_ollama import first_json_object


class PredictionLlmError(RuntimeError):
    pass


class PredictionLlmTimeoutError(PredictionLlmError):
    pass


class PredictionLlmConnectionError(PredictionLlmError):
    pass


class PredictionLlmInvalidJsonError(PredictionLlmError):
    pass


class PredictionLlmSchemaError(PredictionLlmError):
    pass


class PredictionLlmEmptyResponseError(PredictionLlmError):
    pass


SESSION = requests.Session()
SESSION.trust_env = False
log = logging.getLogger("HEATMAP_PREDICTION_LLM")

PREDICTION_OUTPUT_SCHEMA = {
    "type": "object",
    "properties": {
        "prediction_summary": {"type": "string"},
        "overall_risk_level": {"type": "string", "enum": ["low", "medium", "high", "critical"]},
        "predicted_hotspots": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "area_name": {"type": "string"},
                    "risk_level": {"type": "string", "enum": ["low", "medium", "high", "critical"]},
                    "predicted_time_bucket": {
                        "type": "string",
                        "enum": ["morning", "afternoon", "evening", "night", "all_day"],
                    },
                    "predicted_violation_type": {"type": "string"},
                    "confidence": {"type": "number"},
                    "reason": {"type": "string"},
                },
                "required": [
                    "area_name",
                    "risk_level",
                    "predicted_time_bucket",
                    "predicted_violation_type",
                    "confidence",
                    "reason",
                ],
                "additionalProperties": False,
            },
        },
        "recommendations": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "priority": {"type": "string", "enum": ["low", "medium", "high", "critical"]},
                    "action": {"type": "string"},
                    "target_area": {"type": "string"},
                    "target_time_bucket": {
                        "type": "string",
                        "enum": ["morning", "afternoon", "evening", "night", "all_day"],
                    },
                    "reason": {"type": "string"},
                },
                "required": ["priority", "action", "target_area", "target_time_bucket", "reason"],
                "additionalProperties": False,
            },
        },
        "limitations": {
            "type": "array",
            "items": {"type": "string"},
        },
    },
    "required": [
        "prediction_summary",
        "overall_risk_level",
        "predicted_hotspots",
        "recommendations",
        "limitations",
    ],
    "additionalProperties": False,
}


def _provider() -> str:
    return str(
        get_runtime_setting(
            "HEATMAP_PREDICTION_LLM_PROVIDER",
            default_settings.HEATMAP_PREDICTION_LLM_PROVIDER,
        )
        or ""
    ).strip().lower()


def current_provider() -> str:
    return _provider()


def _qwen_api_key() -> str:
    return str(get_runtime_setting("QWEN_API_KEY", default_settings.QWEN_API_KEY) or "").strip()


def _qwen_base_url() -> str:
    return str(get_runtime_setting("QWEN_BASE_URL", default_settings.QWEN_BASE_URL)).rstrip("/")


def _qwen_model() -> str:
    return str(get_runtime_setting("QWEN_MODEL", default_settings.QWEN_MODEL)).strip()


def _qwen_timeout_seconds() -> int:
    return max(
        5,
        get_runtime_setting("QWEN_TIMEOUT_SECONDS", default_settings.QWEN_TIMEOUT_SECONDS, int),
    )


def _qwen_max_tokens() -> int:
    return max(
        128,
        get_runtime_setting("QWEN_MAX_TOKENS", default_settings.QWEN_MAX_TOKENS, int),
    )


def _qwen_temperature() -> float:
    return max(
        0.0,
        min(
            1.0,
            float(get_runtime_setting("QWEN_TEMPERATURE", default_settings.QWEN_TEMPERATURE, float)),
        ),
    )


def _ollama_url() -> str:
    return str(get_runtime_setting("OLLAMA_URL", default_settings.OLLAMA_URL)).rstrip("/")


def _ollama_model() -> str:
    return str(
        get_runtime_setting(
            "HEATMAP_PREDICTION_OLLAMA_MODEL",
            default_settings.HEATMAP_PREDICTION_OLLAMA_MODEL,
        )
    ).strip()


def _ollama_timeout_seconds() -> int:
    return max(
        5,
        get_runtime_setting("OLLAMA_TIMEOUT_SECONDS", default_settings.OLLAMA_TIMEOUT_SECONDS, int),
    )


def _prediction_ollama_num_predict() -> int:
    default_value = max(
        int(default_settings.OLLAMA_NUM_PREDICT),
        int(default_settings.HEATMAP_PREDICTION_OLLAMA_NUM_PREDICT),
    )
    return max(
        360,
        get_runtime_setting(
            "HEATMAP_PREDICTION_OLLAMA_NUM_PREDICT",
            default_value,
            int,
        ),
    )


def _prediction_ollama_retries() -> int:
    return max(
        1,
        get_runtime_setting(
            "HEATMAP_PREDICTION_OLLAMA_RETRIES",
            default_settings.HEATMAP_PREDICTION_OLLAMA_RETRIES,
            int,
        ),
    )


def _compact_signal_summary(signal_summary: dict[str, Any]) -> dict[str, Any]:
    hotspots = []
    for item in (signal_summary.get("predicted_hotspots") or [])[:3]:
        hotspots.append(
            {
                "area_name": item.get("area_name"),
                "risk_level": item.get("risk_level"),
                "predicted_time_bucket": item.get("predicted_time_bucket"),
                "predicted_violation_type": item.get("predicted_violation_type"),
                "confidence": item.get("confidence"),
                "reason": item.get("reason"),
                "composite_score": ((item.get("signals") or {}).get("composite_score")),
                "percentage_change": ((item.get("signals") or {}).get("percentage_change")),
            }
        )

    return {
        "city": signal_summary.get("city"),
        "from_date": signal_summary.get("from_date"),
        "to_date": signal_summary.get("to_date"),
        "violation_type": signal_summary.get("violation_type"),
        "time_bucket": signal_summary.get("time_bucket"),
        "overall_risk_level": signal_summary.get("overall_risk_level"),
        "overall_score": signal_summary.get("overall_score"),
        "predicted_hotspots": hotspots,
        "predicted_increasing_violation_types": (signal_summary.get("predicted_increasing_violation_types") or [])[:3],
        "predicted_high_risk_time_buckets": (signal_summary.get("predicted_high_risk_time_buckets") or [])[:3],
    }


def _build_prompt(signal_summary: dict[str, Any]) -> str:
    compact = json.dumps(_compact_signal_summary(signal_summary), ensure_ascii=False, separators=(",", ":"))
    return (
        "Heatmap signal summary:\n"
        f"{compact}\n\n"
        "Return ONLY one valid JSON object. Do not return an array.\n"
        "Do not wrap the output in a list.\n"
        "Do not include markdown, code fences, comments, or text outside JSON.\n"
        "Language: Arabic.\n"
        "Audience: police manager only.\n"
        "Use exact area names from the input.\n"
        "Return exactly these root keys only:\n"
        "prediction_summary, overall_risk_level, predicted_hotspots, recommendations, limitations.\n"
        "overall_risk_level must be one of: low, medium, high, critical.\n"
        "Return at most 2 hotspots, 2 recommendations, and 2 limitations.\n"
        "Each predicted_hotspots item must be an object with exactly these keys:\n"
        "area_name, risk_level, predicted_time_bucket, predicted_violation_type, confidence, reason.\n"
        "risk_level must be one of: low, medium, high, critical.\n"
        "predicted_time_bucket must be one of: morning, afternoon, evening, night, all_day.\n"
        "confidence must be a number between 0 and 1.\n"
        "Do not include composite_score, percentage_change, rank, density_score, or any extra hotspot fields.\n"
        "Each recommendations item must be an object with exactly these keys:\n"
        "priority, action, target_area, target_time_bucket, reason.\n"
        "priority must be one of: low, medium, high, critical.\n"
        "target_time_bucket must be one of: morning, afternoon, evening, night, all_day.\n"
        "recommendations must not be strings; each recommendation must be an object.\n"
        "limitations must be an array of short Arabic strings.\n"
        "Example shape only:\n"
        "{"
        "\"prediction_summary\":\"...\","
        "\"overall_risk_level\":\"high\","
        "\"predicted_hotspots\":[{"
        "\"area_name\":\"...\","
        "\"risk_level\":\"high\","
        "\"predicted_time_bucket\":\"evening\","
        "\"predicted_violation_type\":\"كل الأنواع\","
        "\"confidence\":0.85,"
        "\"reason\":\"...\""
        "}],"
        "\"recommendations\":[{"
        "\"priority\":\"high\","
        "\"action\":\"...\","
        "\"target_area\":\"...\","
        "\"target_time_bucket\":\"evening\","
        "\"reason\":\"...\""
        "}],"
        "\"limitations\":[\"...\"]"
        "}"
    )


def _clean_model_output(text: str) -> str:
    cleaned = str(text or "").strip()
    if cleaned.startswith("```"):
        lines = cleaned.splitlines()
        if lines:
            lines = lines[1:]
        if lines and lines[-1].strip().startswith("```"):
            lines = lines[:-1]
        cleaned = "\n".join(lines).strip()
    return cleaned


def _normalize_model_output(parsed: Any, provider_name: str) -> dict[str, Any]:
    if isinstance(parsed, list):
        if not parsed:
            raise PredictionLlmEmptyResponseError(f"{provider_name} returned an empty JSON array")
        log.warning("%s returned root array; using first item", provider_name)
        parsed = parsed[0]

    if not isinstance(parsed, dict):
        raise PredictionLlmSchemaError(f"{provider_name} root JSON must be an object")

    normalized = dict(parsed)

    hotspots = normalized.get("predicted_hotspots")
    if isinstance(hotspots, list):
        normalized_hotspots = []
        for hotspot in hotspots:
            if not isinstance(hotspot, dict):
                continue
            normalized_hotspots.append(
                {
                    "area_name": str(hotspot.get("area_name") or ""),
                    "risk_level": str(hotspot.get("risk_level") or "medium").lower(),
                    "predicted_time_bucket": str(hotspot.get("predicted_time_bucket") or "all_day").lower(),
                    "predicted_violation_type": str(hotspot.get("predicted_violation_type") or "كل الأنواع"),
                    "confidence": float(hotspot.get("confidence") or 0.5),
                    "reason": str(
                        hotspot.get("reason")
                        or hotspot.get("description")
                        or "تم تحديد هذه المنطقة بناءً على مؤشرات الخطر في ملخص الخريطة الحرارية."
                    ),
                }
            )
        normalized["predicted_hotspots"] = normalized_hotspots

    recommendations = normalized.get("recommendations")
    if isinstance(recommendations, list):
        normalized_recommendations = []
        hotspots_for_mapping = normalized.get("predicted_hotspots") or []

        for index, recommendation in enumerate(recommendations):
            if isinstance(recommendation, dict):
                normalized_recommendations.append(
                    {
                        "priority": str(recommendation.get("priority") or "medium").lower(),
                        "action": str(recommendation.get("action") or ""),
                        "target_area": str(recommendation.get("target_area") or ""),
                        "target_time_bucket": str(recommendation.get("target_time_bucket") or "all_day").lower(),
                        "reason": str(
                            recommendation.get("reason")
                            or "إجراء تشغيلي مقترح بناءً على مستوى الخطر المتوقع."
                        ),
                    }
                )
                continue

            if isinstance(recommendation, str):
                linked_hotspot = {}
                if index < len(hotspots_for_mapping) and isinstance(hotspots_for_mapping[index], dict):
                    linked_hotspot = hotspots_for_mapping[index]

                normalized_recommendations.append(
                    {
                        "priority": str(linked_hotspot.get("risk_level") or normalized.get("overall_risk_level") or "medium").lower(),
                        "action": recommendation,
                        "target_area": str(linked_hotspot.get("area_name") or "غير محدد"),
                        "target_time_bucket": str(linked_hotspot.get("predicted_time_bucket") or "all_day").lower(),
                        "reason": str(linked_hotspot.get("reason") or "تم اقتراح الإجراء بناءً على مخرجات تحليل المخاطر."),
                    }
                )

        normalized["recommendations"] = normalized_recommendations

    limitations = normalized.get("limitations")
    if isinstance(limitations, str):
        normalized["limitations"] = [limitations]

    return normalized


def _parse_and_validate_output(raw_text: str, provider_name: str) -> dict[str, Any]:
    cleaned = _clean_model_output(raw_text)
    if not cleaned:
        log.error("%s returned empty content", provider_name)
        raise PredictionLlmEmptyResponseError(f"{provider_name} returned empty content")

    parsed = first_json_object(cleaned)
    if parsed is None:
        log.error("%s returned invalid JSON raw=%r", provider_name, raw_text[:2000])
        raise PredictionLlmInvalidJsonError(f"{provider_name} returned invalid JSON")

    parsed = _normalize_model_output(parsed, provider_name)

    try:
        return validate_prediction_output(parsed)
    except PredictionSchemaError as exc:
        log.error(
            "%s schema validation failed err=%s parsed=%s",
            provider_name,
            exc,
            json.dumps(parsed, ensure_ascii=False),
        )
        raise PredictionLlmSchemaError(str(exc)) from exc


def _extract_openai_content(data: dict[str, Any], provider_name: str) -> str:
    choices = data.get("choices") or []
    if not choices or not isinstance(choices[0], dict):
        log.error("%s returned no choices payload=%s", provider_name, json.dumps(data, ensure_ascii=False)[:2000])
        raise PredictionLlmEmptyResponseError(f"{provider_name} returned no choices")

    message = choices[0].get("message") or {}
    content = message.get("content")

    if isinstance(content, list):
        text_parts = []
        for item in content:
            if isinstance(item, dict) and item.get("type") == "text":
                text_parts.append(str(item.get("text") or ""))
        content = "".join(text_parts)

    text = str(content or "").strip()
    if not text:
        log.error("%s returned empty message content payload=%s", provider_name, json.dumps(data, ensure_ascii=False)[:2000])
        raise PredictionLlmEmptyResponseError(f"{provider_name} returned empty message content")

    return text


def _call_openai_compatible_api(
    *,
    provider_name: str,
    endpoint_url: str,
    model_name: str,
    api_key: str,
    timeout_seconds: int,
    max_tokens: int,
    temperature: float,
    prompt: str,
) -> dict[str, Any]:
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}",
    }

    payload = {
        "model": model_name,
        "messages": [
            {
                "role": "system",
                "content": (
                    "You are a traffic analysis assistant. "
                    "Return only one valid JSON object matching the required schema. "
                    "Do not return an array. Do not include markdown."
                ),
            },
            {
                "role": "user",
                "content": prompt,
            },
        ],
        "temperature": temperature,
        "max_tokens": max_tokens,
        "response_format": {"type": "json_object"},
    }

    log.info(
        "%s request started model=%s url=%s timeout=%ss",
        provider_name,
        model_name,
        endpoint_url,
        timeout_seconds,
    )

    try:
        response = SESSION.post(endpoint_url, json=payload, headers=headers, timeout=timeout_seconds)
        response.raise_for_status()
    except requests_exceptions.Timeout as exc:
        log.error("%s timeout model=%s url=%s err=%s", provider_name, model_name, endpoint_url, exc)
        raise PredictionLlmTimeoutError(str(exc)) from exc
    except requests_exceptions.ConnectionError as exc:
        log.error("%s connection error model=%s url=%s err=%s", provider_name, model_name, endpoint_url, exc)
        raise PredictionLlmConnectionError(str(exc)) from exc
    except requests_exceptions.RequestException as exc:
        log.error("%s request failed model=%s url=%s err=%s", provider_name, model_name, endpoint_url, exc)
        raise PredictionLlmConnectionError(str(exc)) from exc

    data = response.json()
    content = _extract_openai_content(data, provider_name)

    log.info("%s request success model=%s", provider_name, model_name)
    log.info("%s raw response=%r", provider_name, content[:2000])

    return _parse_and_validate_output(content, provider_name)


def generate_qwen_recommendations(signal_summary: dict[str, Any]) -> dict[str, Any]:
    api_key = _qwen_api_key()
    if not api_key:
        raise PredictionLlmConnectionError("Qwen API key is not configured")

    endpoint_url = f"{_qwen_base_url()}/chat/completions"

    return _call_openai_compatible_api(
        provider_name="qwen_api",
        endpoint_url=endpoint_url,
        model_name=_qwen_model(),
        api_key=api_key,
        timeout_seconds=_qwen_timeout_seconds(),
        max_tokens=_qwen_max_tokens(),
        temperature=_qwen_temperature(),
        prompt=_build_prompt(signal_summary),
    )


def generate_ollama_recommendations(signal_summary: dict[str, Any]) -> dict[str, Any]:
    prompt = _build_prompt(signal_summary)
    raw_url = _ollama_url()
    base_url = raw_url[:-13] if raw_url.endswith("/api/generate") else raw_url
    model_name = _ollama_model()
    endpoint_url = f"{base_url}/api/chat"
    num_predict = _prediction_ollama_num_predict()
    max_attempts = _prediction_ollama_retries()
    last_error: Exception | None = None

    for attempt in range(1, max_attempts + 1):
        payload = {
            "model": model_name,
            "messages": [
                {
                    "role": "system",
                    "content": (
                        "Return only valid JSON matching the provided schema. "
                        "No markdown. No code fences. No explanations."
                    ),
                },
                {
                    "role": "user",
                    "content": prompt,
                },
            ],
            "stream": False,
            "think": False,
            "format": PREDICTION_OUTPUT_SCHEMA,
            "options": {
                "temperature": 0.1,
                "top_p": 0.8,
                "num_ctx": default_settings.OLLAMA_NUM_CTX,
                "num_predict": num_predict,
            },
        }

        log.info(
            "ollama request started attempt=%s/%s model=%s url=%s timeout=%ss num_predict=%s",
            attempt,
            max_attempts,
            model_name,
            endpoint_url,
            _ollama_timeout_seconds(),
            num_predict,
        )

        try:
            response = SESSION.post(endpoint_url, json=payload, timeout=_ollama_timeout_seconds())
            response.raise_for_status()
        except requests_exceptions.Timeout as exc:
            log.error("ollama timeout model=%s url=%s err=%s", model_name, endpoint_url, exc)
            raise PredictionLlmTimeoutError(str(exc)) from exc
        except requests_exceptions.ConnectionError as exc:
            log.error("ollama connection error model=%s url=%s err=%s", model_name, endpoint_url, exc)
            raise PredictionLlmConnectionError(str(exc)) from exc
        except requests_exceptions.RequestException as exc:
            log.error("ollama request failed model=%s url=%s err=%s", model_name, endpoint_url, exc)
            raise PredictionLlmConnectionError(str(exc)) from exc

        data = response.json()
        done_reason = str(data.get("done_reason") or "")
        raw = str(((data.get("message") or {}).get("content") or "")).strip()

        log.info(
            "ollama request success model=%s done=%s done_reason=%s",
            model_name,
            data.get("done"),
            done_reason,
        )
        log.info("ollama raw response=%r", raw[:2000])

        if done_reason.lower() == "length" and attempt < max_attempts:
            num_predict = max(num_predict + 240, int(round(num_predict * 1.35)))
            last_error = PredictionLlmInvalidJsonError("Ollama response was truncated before JSON completed")
            log.warning(
                "ollama response truncated attempt=%s/%s retrying_with_num_predict=%s",
                attempt,
                max_attempts,
                num_predict,
            )
            continue

        try:
            return _parse_and_validate_output(raw, "ollama")
        except PredictionLlmInvalidJsonError as exc:
            last_error = exc
            if done_reason.lower() == "length" and attempt < max_attempts:
                num_predict = max(num_predict + 240, int(round(num_predict * 1.35)))
                log.warning(
                    "ollama invalid JSON after truncation attempt=%s/%s retrying_with_num_predict=%s",
                    attempt,
                    max_attempts,
                    num_predict,
                )
                continue
            raise

    if last_error is not None:
        raise last_error

    raise PredictionLlmInvalidJsonError("Ollama prediction failed without a usable response")


def generate_recommendations(signal_summary: dict[str, Any]) -> dict[str, Any]:
    provider = _provider()
    log.info("Prediction provider selected provider=%s", provider)

    if provider in FALLBACK_PROVIDER_NAMES:
        return build_fallback_prediction(signal_summary)

    if provider == QWEN_PROVIDER_NAME:
        return generate_qwen_recommendations(signal_summary)

    if provider == "ollama":
        return generate_ollama_recommendations(signal_summary)

    if provider == "lmstudio":
        raise PredictionLlmConnectionError("LM Studio provider is no longer supported for heatmap prediction")

    raise PredictionLlmConnectionError(f"Unsupported prediction provider: {provider}")
