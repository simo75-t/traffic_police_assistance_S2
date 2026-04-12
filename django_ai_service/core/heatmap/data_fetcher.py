from __future__ import annotations

from datetime import date
from typing import Any

import requests
from django.conf import settings

from core.heatmap.synthetic_data_service import generate_synthetic_records


class ViolationDataFetcher:
    def __init__(self) -> None:
        self.session = requests.Session()
        self.session.trust_env = False

    def _violations_url(self) -> str:
        if settings.LARAVEL_VIOLATIONS_API:
            return settings.LARAVEL_VIOLATIONS_API
        return f"{settings.LARAVEL_BASE_URL}{settings.LARAVEL_API_PREFIX}/violations"

    def fetch(
        self,
        city: str,
        date_from: date,
        date_to: date,
        violation_type_id: int | None,
        include_synthetic: bool,
    ) -> list[dict[str, Any]]:
        records = self._fetch_real_records(
            city=city,
            date_from=date_from,
            date_to=date_to,
            violation_type_id=violation_type_id,
        )
        if include_synthetic and not records:
            records.extend(generate_synthetic_records(city=city, date_from=date_from, date_to=date_to))
        return records

    def _fetch_real_records(
        self,
        city: str,
        date_from: date,
        date_to: date,
        violation_type_id: int | None,
    ) -> list[dict[str, Any]]:
        url = self._violations_url()
        page = 1
        rows: list[dict[str, Any]] = []

        while True:
            params = {
                "from": date_from.isoformat(),
                "to": date_to.isoformat(),
                "per_page": settings.HEATMAP_VIOLATIONS_PAGE_SIZE,
                "page": page,
            }
            if city:
                params["city"] = city
            if violation_type_id is not None:
                params["violation_type_id"] = violation_type_id

            response = self.session.get(
                url,
                params=params,
                timeout=settings.LARAVEL_API_TIMEOUT,
            )
            response.raise_for_status()
            payload = response.json()
            data = payload.get("data") if isinstance(payload, dict) else payload
            if not isinstance(data, list):
                break

            for item in data:
                rows.append(self._flatten_violation(item))

            meta = payload.get("meta") if isinstance(payload, dict) else None
            if not isinstance(meta, dict) or page >= int(meta.get("last_page") or page):
                break
            page += 1

        return rows

    def _flatten_violation(self, item: dict[str, Any]) -> dict[str, Any]:
        location = item.get("location") or item.get("violation_location") or {}
        city_payload = location.get("city") if isinstance(location, dict) else None
        city_name = None
        if isinstance(city_payload, dict):
            city_name = city_payload.get("name")
        elif isinstance(location, dict):
            city_name = location.get("city_name") or location.get("city")

        violation_type = item.get("violation_type") or {}
        severity = item.get("severity_level")
        severity_weight = self._resolve_severity_weight(severity=severity, violation_type=violation_type)
        location_label = self._build_location_label(location=location, city_name=city_name)

        return {
            "latitude": location.get("latitude") if isinstance(location, dict) else None,
            "longitude": location.get("longitude") if isinstance(location, dict) else None,
            "violation_type_id": item.get("violation_type_id") or violation_type.get("id"),
            "created_at": item.get("occurred_at") or item.get("created_at"),
            "severity_weight": severity_weight,
            "is_synthetic": bool(item.get("is_synthetic", False)),
            "city": city_name,
            "location_label": location_label,
        }

    def _build_location_label(self, location: Any, city_name: Any) -> str:
        if not isinstance(location, dict):
            return str(city_name or "").strip()

        parts = [
            location.get("area_name"),
            location.get("street_name"),
            location.get("landmark"),
            location.get("address"),
            city_name,
        ]
        labels: list[str] = []
        for part in parts:
            text = str(part or "").strip()
            if text and text not in labels:
                labels.append(text)

        return " - ".join(labels[:2])

    def _resolve_severity_weight(self, severity: Any, violation_type: dict[str, Any]) -> float:
        raw_type_weight = violation_type.get("severity_weight") if isinstance(violation_type, dict) else None
        if raw_type_weight is not None:
            try:
                return max(float(raw_type_weight), 1.0)
            except (TypeError, ValueError):
                pass

        if severity is not None:
            try:
                return max(float(severity), 1.0)
            except (TypeError, ValueError):
                pass

        severity_key = str(severity or "").strip().lower()
        severity_map = {
            "low": 1.0,
            "medium": 1.5,
            "high": 2.2,
            "critical": 3.0,
        }
        return severity_map.get(severity_key, 1.0)
