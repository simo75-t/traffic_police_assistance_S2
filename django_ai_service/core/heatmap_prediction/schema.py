from __future__ import annotations

from typing import Any


RISK_LEVELS = {"low", "medium", "high", "critical"}
TIME_BUCKETS = {"morning", "afternoon", "evening", "night", "all_day"}
ROOT_KEYS = {
    "prediction_summary",
    "overall_risk_level",
    "predicted_hotspots",
    "recommendations",
    "limitations",
}
HOTSPOT_KEYS = {
    "area_name",
    "risk_level",
    "predicted_time_bucket",
    "predicted_violation_type",
    "confidence",
    "reason",
}
RECOMMENDATION_KEYS = {
    "priority",
    "action",
    "target_area",
    "target_time_bucket",
    "reason",
}


class PredictionSchemaError(ValueError):
    pass


def _ensure_dict(value: Any, field_name: str) -> dict[str, Any]:
    if not isinstance(value, dict):
        raise PredictionSchemaError(f"{field_name} must be an object")
    return value


def _ensure_list(value: Any, field_name: str) -> list[Any]:
    if not isinstance(value, list):
        raise PredictionSchemaError(f"{field_name} must be a list")
    return value


def _ensure_text(value: Any, field_name: str) -> str:
    text = str(value or "").strip()
    if not text:
        raise PredictionSchemaError(f"{field_name} is required")
    return text


def _ensure_risk(value: Any, field_name: str) -> str:
    text = _ensure_text(value, field_name).lower()
    if text not in RISK_LEVELS:
        raise PredictionSchemaError(f"{field_name} has invalid value")
    return text


def _ensure_time_bucket(value: Any, field_name: str) -> str:
    text = _ensure_text(value, field_name).lower()
    if text not in TIME_BUCKETS:
        raise PredictionSchemaError(f"{field_name} has invalid value")
    return text


def _ensure_confidence(value: Any, field_name: str) -> float:
    try:
        parsed = float(value)
    except (TypeError, ValueError) as exc:
        raise PredictionSchemaError(f"{field_name} must be numeric") from exc
    if parsed < 0 or parsed > 1:
        raise PredictionSchemaError(f"{field_name} must be between 0 and 1")
    return round(parsed, 2)


def _ensure_no_extra_keys(value: dict[str, Any], allowed_keys: set[str], field_name: str) -> None:
    extra_keys = sorted(set(value.keys()) - allowed_keys)
    if extra_keys:
        raise PredictionSchemaError(f"{field_name} contains unsupported keys: {', '.join(extra_keys)}")


def validate_prediction_output(data: dict[str, Any]) -> dict[str, Any]:
    root = _ensure_dict(data, "root")
    _ensure_no_extra_keys(root, ROOT_KEYS, "root")
    predicted_hotspots = []
    recommendations = []
    limitations = []

    for item in _ensure_list(root.get("predicted_hotspots"), "predicted_hotspots"):
        row = _ensure_dict(item, "predicted_hotspots[]")
        _ensure_no_extra_keys(row, HOTSPOT_KEYS, "predicted_hotspots[]")
        predicted_hotspots.append(
            {
                "area_name": _ensure_text(row.get("area_name"), "predicted_hotspots.area_name"),
                "risk_level": _ensure_risk(row.get("risk_level"), "predicted_hotspots.risk_level"),
                "predicted_time_bucket": _ensure_time_bucket(
                    row.get("predicted_time_bucket"),
                    "predicted_hotspots.predicted_time_bucket",
                ),
                "predicted_violation_type": _ensure_text(
                    row.get("predicted_violation_type"),
                    "predicted_hotspots.predicted_violation_type",
                ),
                "confidence": _ensure_confidence(row.get("confidence"), "predicted_hotspots.confidence"),
                "reason": _ensure_text(row.get("reason"), "predicted_hotspots.reason"),
            }
        )

    for item in _ensure_list(root.get("recommendations"), "recommendations"):
        row = _ensure_dict(item, "recommendations[]")
        _ensure_no_extra_keys(row, RECOMMENDATION_KEYS, "recommendations[]")
        recommendations.append(
            {
                "priority": _ensure_risk(row.get("priority"), "recommendations.priority"),
                "action": _ensure_text(row.get("action"), "recommendations.action"),
                "target_area": _ensure_text(row.get("target_area"), "recommendations.target_area"),
                "target_time_bucket": _ensure_time_bucket(
                    row.get("target_time_bucket"),
                    "recommendations.target_time_bucket",
                ),
                "reason": _ensure_text(row.get("reason"), "recommendations.reason"),
            }
        )

    for item in _ensure_list(root.get("limitations"), "limitations"):
        limitations.append(_ensure_text(item, "limitations[]"))

    return {
        "prediction_summary": _ensure_text(root.get("prediction_summary"), "prediction_summary"),
        "overall_risk_level": _ensure_risk(root.get("overall_risk_level"), "overall_risk_level"),
        "predicted_hotspots": predicted_hotspots,
        "recommendations": recommendations,
        "limitations": limitations,
    }
