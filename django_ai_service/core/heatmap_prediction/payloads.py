from __future__ import annotations

from dataclasses import dataclass
from datetime import date
from typing import Any


TIME_BUCKETS = {"morning", "afternoon", "evening", "night", "all_day"}
RISK_LEVELS = {"low", "medium", "high", "critical"}
ALLOWED_TRENDS = {"increasing", "stable", "decreasing", "up", "down"}


class PayloadValidationError(ValueError):
    pass


@dataclass(frozen=True)
class HeatmapPredictionHotspot:
    area_name: str
    density_score: float
    rank: int
    trend: str
    percentage_change: float
    dominant_violation_type: str
    dominant_time_bucket: str
    recent_count: int | None = None
    previous_count: int | None = None
    moving_average_score: float | None = None


@dataclass(frozen=True)
class HeatmapPredictionSummary:
    city: str
    from_date: date
    to_date: date
    violation_type: str
    time_bucket: str
    hotspots: list[HeatmapPredictionHotspot]


@dataclass(frozen=True)
class HeatmapPredictionPayload:
    job_type: str
    request_id: str
    correlation_id: str
    heatmap_summary: HeatmapPredictionSummary


def _parse_date(value: Any, field_name: str) -> date:
    text = str(value or "").strip()
    if not text:
        raise PayloadValidationError(f"Missing {field_name}")
    try:
        return date.fromisoformat(text)
    except ValueError as exc:
        raise PayloadValidationError(f"Invalid {field_name}") from exc


def _parse_float(value: Any, field_name: str) -> float:
    try:
        return float(value)
    except (TypeError, ValueError) as exc:
        raise PayloadValidationError(f"Invalid {field_name}") from exc


def _parse_optional_int(value: Any, field_name: str) -> int | None:
    if value in (None, ""):
        return None
    try:
        return int(value)
    except (TypeError, ValueError) as exc:
        raise PayloadValidationError(f"Invalid {field_name}") from exc


def _require_text(value: Any, field_name: str) -> str:
    text = str(value or "").strip()
    if not text:
        raise PayloadValidationError(f"Missing {field_name}")
    return text


def _validate_hotspot(item: dict[str, Any]) -> HeatmapPredictionHotspot:
    area_name = _require_text(item.get("area_name"), "hotspots.area_name")
    density_score = _parse_float(item.get("density_score"), "hotspots.density_score")
    if density_score < 0:
        raise PayloadValidationError("hotspots.density_score must be >= 0")

    rank = _parse_optional_int(item.get("rank"), "hotspots.rank")
    if rank is None or rank <= 0:
        raise PayloadValidationError("hotspots.rank must be a positive integer")

    trend = str(item.get("trend") or "stable").strip().lower()
    if trend not in ALLOWED_TRENDS:
        raise PayloadValidationError("Invalid hotspots.trend")

    percentage_change = _parse_float(item.get("percentage_change", 0), "hotspots.percentage_change")
    dominant_violation_type = _require_text(
        item.get("dominant_violation_type") or "Unknown",
        "hotspots.dominant_violation_type",
    )
    dominant_time_bucket = str(item.get("dominant_time_bucket") or "all_day").strip().lower()
    if dominant_time_bucket not in TIME_BUCKETS:
        raise PayloadValidationError("Invalid hotspots.dominant_time_bucket")

    moving_average_score = None
    if item.get("moving_average_score") not in (None, ""):
        moving_average_score = _parse_float(item.get("moving_average_score"), "hotspots.moving_average_score")

    return HeatmapPredictionHotspot(
        area_name=area_name,
        density_score=density_score,
        rank=rank,
        trend=trend,
        percentage_change=percentage_change,
        dominant_violation_type=dominant_violation_type,
        dominant_time_bucket=dominant_time_bucket,
        recent_count=_parse_optional_int(item.get("recent_count"), "hotspots.recent_count"),
        previous_count=_parse_optional_int(item.get("previous_count"), "hotspots.previous_count"),
        moving_average_score=moving_average_score,
    )


def validate_payload(payload: dict[str, Any]) -> HeatmapPredictionPayload:
    job_type = _require_text(payload.get("job_type"), "job_type")
    if job_type != "generate_heatmap_prediction":
        raise PayloadValidationError("Unsupported job_type")

    request_id = _require_text(payload.get("request_id"), "request_id")
    correlation_id = str(payload.get("correlation_id") or "").strip()

    raw_summary = payload.get("heatmap_summary")
    if not isinstance(raw_summary, dict):
        raise PayloadValidationError("Missing heatmap_summary")

    city = _require_text(raw_summary.get("city"), "heatmap_summary.city")
    from_date = _parse_date(raw_summary.get("from_date"), "heatmap_summary.from_date")
    to_date = _parse_date(raw_summary.get("to_date"), "heatmap_summary.to_date")
    if from_date > to_date:
        raise PayloadValidationError("heatmap_summary.from_date must be before to_date")

    violation_type = str(raw_summary.get("violation_type") or "all").strip() or "all"
    time_bucket = str(raw_summary.get("time_bucket") or "all_day").strip().lower() or "all_day"
    if time_bucket not in TIME_BUCKETS:
        raise PayloadValidationError("Invalid heatmap_summary.time_bucket")

    raw_hotspots = raw_summary.get("hotspots")
    if not isinstance(raw_hotspots, list):
        raise PayloadValidationError("heatmap_summary.hotspots must be a list")

    hotspots = []
    for item in raw_hotspots:
        if not isinstance(item, dict):
            raise PayloadValidationError("heatmap_summary.hotspots items must be objects")
        hotspots.append(_validate_hotspot(item))
    if not hotspots:
        raise PayloadValidationError("heatmap_summary.hotspots cannot be empty")

    return HeatmapPredictionPayload(
        job_type=job_type,
        request_id=request_id,
        correlation_id=correlation_id,
        heatmap_summary=HeatmapPredictionSummary(
            city=city,
            from_date=from_date,
            to_date=to_date,
            violation_type=violation_type,
            time_bucket=time_bucket,
            hotspots=hotspots,
        ),
    )
