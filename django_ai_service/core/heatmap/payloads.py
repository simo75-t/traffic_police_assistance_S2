from __future__ import annotations

from dataclasses import dataclass
from datetime import date
from typing import Any, Optional

from django.conf import settings

from core.heatmap.constants import SUPPORTED_COMPARISON_MODES, TIME_BUCKETS


class PayloadValidationError(ValueError):
    pass


@dataclass(frozen=True)
class HeatmapPayload:
    job_type: str
    request_id: str
    city: str
    date_from: date
    date_to: date
    violation_type_id: Optional[int]
    time_bucket: str
    grid_size_meters: int
    include_ranking: bool
    include_trend: bool
    include_synthetic: bool
    comparison_mode: str

    def to_cache_filters(self) -> dict[str, Any]:
        return {
            "schema_version": 5,
            "city": self.city,
            "date_from": self.date_from.isoformat(),
            "date_to": self.date_to.isoformat(),
            "violation_type_id": self.violation_type_id,
            "time_bucket": self.time_bucket,
            "grid_size_meters": self.grid_size_meters,
            "include_synthetic": self.include_synthetic,
            "comparison_mode": self.comparison_mode,
        }


def _parse_date(value: Any, field_name: str) -> date:
    if not isinstance(value, str) or not value.strip():
        raise PayloadValidationError(f"Missing {field_name}")
    try:
        return date.fromisoformat(value.strip())
    except ValueError as exc:
        raise PayloadValidationError(f"Invalid {field_name}") from exc


def _parse_bool(value: Any) -> bool:
    if isinstance(value, bool):
        return value
    if isinstance(value, str):
        return value.strip().lower() in {"1", "true", "yes", "on"}
    return bool(value)


def validate_payload(payload: dict[str, Any]) -> HeatmapPayload:
    job_type = str(payload.get("job_type") or "").strip()
    if job_type != "generate_heatmap":
        raise PayloadValidationError("Unsupported job_type")

    request_id = str(payload.get("request_id") or "").strip()
    if not request_id:
        raise PayloadValidationError("Missing request_id")

    city = str(payload.get("city") or "").strip()
    if not city:
        raise PayloadValidationError("Missing city")

    date_from = _parse_date(payload.get("date_from"), "date_from")
    date_to = _parse_date(payload.get("date_to"), "date_to")
    if date_from > date_to:
        raise PayloadValidationError("date_from must be before date_to")

    raw_violation_type_id = payload.get("violation_type_id")
    violation_type_id = None
    if raw_violation_type_id not in (None, ""):
        try:
            violation_type_id = int(raw_violation_type_id)
        except (TypeError, ValueError) as exc:
            raise PayloadValidationError("Invalid violation_type_id") from exc

    time_bucket = str(payload.get("time_bucket") or "").strip().lower()
    if time_bucket and time_bucket not in TIME_BUCKETS:
        raise PayloadValidationError("Invalid time_bucket")

    try:
        grid_size_meters = int(payload.get("grid_size_meters") or settings.HEATMAP_GRID_DEFAULT_METERS)
    except (TypeError, ValueError) as exc:
        raise PayloadValidationError("Invalid grid_size_meters") from exc

    if grid_size_meters < settings.HEATMAP_GRID_MIN_METERS or grid_size_meters > settings.HEATMAP_GRID_MAX_METERS:
        raise PayloadValidationError("grid_size_meters is out of range")

    include_ranking = _parse_bool(payload.get("include_ranking", False))
    include_trend = _parse_bool(payload.get("include_trend", False))
    include_synthetic = True

    comparison_mode = str(payload.get("comparison_mode") or "").strip().lower()
    if not include_trend:
        comparison_mode = ""
    if comparison_mode not in SUPPORTED_COMPARISON_MODES:
        raise PayloadValidationError("Invalid comparison_mode")
    if include_trend and not comparison_mode:
        raise PayloadValidationError("comparison_mode is required when include_trend is true")

    return HeatmapPayload(
        job_type=job_type,
        request_id=request_id,
        city=city,
        date_from=date_from,
        date_to=date_to,
        violation_type_id=violation_type_id,
        time_bucket=time_bucket,
        grid_size_meters=grid_size_meters,
        include_ranking=include_ranking,
        include_trend=include_trend,
        include_synthetic=include_synthetic,
        comparison_mode=comparison_mode,
    )
