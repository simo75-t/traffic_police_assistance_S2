from __future__ import annotations

import json
import logging
from datetime import datetime

from django.conf import settings

from core.heatmap.cache_keys import build_cache_key
from core.heatmap.cache_service import HeatmapCacheService
from core.heatmap.data_fetcher import ViolationDataFetcher
from core.heatmap.kde_service import evaluate_kde
from core.heatmap.normalization import normalize_scores
from core.heatmap.payloads import HeatmapPayload, validate_payload
from core.heatmap.ranking_service import build_ranking
from core.heatmap.spatial_grid_service import build_grid, is_within_bbox, normalize_city_key
from core.heatmap.temporal_bucket_service import resolve_time_bucket
from core.heatmap.trend_service import compare_heatmaps, shift_period


log = logging.getLogger("HEATMAP_SERVICE")


def _require_pandas():
    try:
        import pandas as pd
    except ImportError as exc:
        raise RuntimeError("pandas is required for heatmap analysis") from exc
    return pd


class HeatmapOrchestrator:
    def __init__(self) -> None:
        self.fetcher = ViolationDataFetcher()
        self.cache_service = HeatmapCacheService()

    def generate_heatmap(self, payload: dict) -> dict:
        parsed = validate_payload(payload)

        cache_key = build_cache_key(parsed.to_cache_filters())
        cache = self.cache_service.get_valid(cache_key)
        if cache:
            return self.cache_service.to_result(cache=cache, request_id=parsed.request_id)

        result = self._generate_fresh(parsed=parsed, cache_key=cache_key)
        self.cache_service.save(payload=parsed, cache_key=cache_key, result=result)
        return result

    def _generate_fresh(self, parsed: HeatmapPayload, cache_key: str) -> dict:
        records = self.fetcher.fetch(
            city=parsed.city,
            date_from=parsed.date_from,
            date_to=parsed.date_to,
            violation_type_id=parsed.violation_type_id,
            include_synthetic=parsed.include_synthetic,
        )
        points = self._prepare_points(records=records, parsed=parsed)
        heatmap_points = self._run_analysis(points=points, city=parsed.city, grid_size_meters=parsed.grid_size_meters)

        ranking = []
        if parsed.include_ranking and heatmap_points:
            ranking = build_ranking(heatmap_points, top_n=settings.HEATMAP_TOP_N)

        trend = []
        if parsed.include_trend and heatmap_points:
            previous_points = self._build_previous_heatmap(parsed=parsed)
            trend = compare_heatmaps(heatmap_points, previous_points, limit=settings.HEATMAP_TOP_N)

        return {
            "request_id": parsed.request_id,
            "cache_key": cache_key,
            "city": parsed.city,
            "heatmap_points": heatmap_points,
            "ranking": ranking,
            "trend": trend,
            "meta": {
                "date_from": parsed.date_from.isoformat(),
                "date_to": parsed.date_to.isoformat(),
                "time_bucket": parsed.time_bucket,
                "grid_size_meters": parsed.grid_size_meters,
                "total_violations": len(points),
                "from_cache": False,
            },
        }

    def _prepare_points(self, records: list[dict], parsed: HeatmapPayload) -> list[dict]:
        pd = _require_pandas()
        if not records:
            return []

        normalized_city_key = normalize_city_key(parsed.city)
        frame = pd.DataFrame(records)
        if frame.empty:
            return []

        if "latitude" not in frame.columns:
            frame["latitude"] = None
        if "longitude" not in frame.columns:
            frame["longitude"] = None
        if "severity_weight" not in frame.columns:
            frame["severity_weight"] = 1.0
        if "created_at" not in frame.columns:
            frame["created_at"] = None
        if "city" not in frame.columns:
            frame["city"] = parsed.city
        if "violation_type_id" not in frame.columns:
            frame["violation_type_id"] = None
        if "location_label" not in frame.columns:
            frame["location_label"] = ""
        if "is_synthetic" not in frame.columns:
            frame["is_synthetic"] = False

        frame["latitude"] = pd.to_numeric(frame.get("latitude"), errors="coerce")
        frame["longitude"] = pd.to_numeric(frame.get("longitude"), errors="coerce")
        frame["severity_weight"] = pd.to_numeric(frame.get("severity_weight"), errors="coerce").fillna(1.0)
        frame["location_label"] = frame.get("location_label").fillna("").astype(str)
        frame["is_synthetic"] = frame.get("is_synthetic").fillna(False).astype(bool)
        frame["created_at"] = frame.get("created_at").apply(self._parse_datetime)
        frame["city"] = frame.get("city").fillna(parsed.city).astype(str).apply(normalize_city_key)
        frame["time_bucket"] = frame["created_at"].apply(lambda value: resolve_time_bucket(value) if value is not None else "")

        frame = frame[frame["city"] == normalized_city_key]
        if frame.empty:
            return []
        frame = frame.dropna(subset=["latitude", "longitude", "created_at"])
        if frame.empty:
            return []
        frame = frame[
            frame.apply(
                lambda row: is_within_bbox(lat=float(row["latitude"]), lng=float(row["longitude"]), city=parsed.city),
                axis=1,
            )
        ]
        if frame.empty:
            return []

        if parsed.violation_type_id is not None and "violation_type_id" in frame.columns:
            frame["violation_type_id"] = pd.to_numeric(frame["violation_type_id"], errors="coerce")
            frame = frame[frame["violation_type_id"] == parsed.violation_type_id]
            if frame.empty:
                return []

        if parsed.time_bucket:
            frame = frame[frame["time_bucket"] == parsed.time_bucket]
            if frame.empty:
                return []

        return frame[
            ["latitude", "longitude", "created_at", "time_bucket", "severity_weight", "location_label", "is_synthetic"]
        ].to_dict("records")

    def _run_analysis(self, points: list[dict], city: str, grid_size_meters: int) -> list[dict]:
        if not points:
            return []

        grid_cells = build_grid(city=city, grid_size_meters=grid_size_meters, points=points)
        densities = evaluate_kde(points=points, grid_cells=grid_cells)
        intensities = normalize_scores(densities)

        rows = []
        for cell, intensity in zip(grid_cells, intensities):
            row = {
                "cell_id": cell["cell_id"],
                "lat": round(cell["center_lat"], 6),
                "lng": round(cell["center_lng"], 6),
                "intensity": round(float(intensity), 6),
            }
            representative = self._representative_point_for_cell(cell=cell, points=points)
            if representative is not None:
                row["lat"] = round(float(representative["latitude"]), 6)
                row["lng"] = round(float(representative["longitude"]), 6)
                row["area_label"] = str(representative.get("location_label") or "").strip()
                row["location_label"] = row["area_label"]
            rows.append(row)

        visible_rows = self._compact_heatmap_points(rows=rows, total_points=len(points))
        for row in visible_rows:
            if not row.get("area_label"):
                row["area_label"] = self._nearest_location_label_for_coords(
                    lat=float(row["lat"]),
                    lng=float(row["lng"]),
                    points=points,
                )
        return visible_rows

    def _compact_heatmap_points(self, rows: list[dict], total_points: int) -> list[dict]:
        if not rows or total_points <= 0:
            return []

        min_intensity = float(getattr(settings, "HEATMAP_MIN_VISIBLE_INTENSITY", 0.02))
        max_return_points = max(10, int(getattr(settings, "HEATMAP_MAX_RETURN_POINTS", 60)))

        filtered_rows = [row for row in rows if float(row.get("intensity", 0.0)) >= min_intensity]
        if not filtered_rows:
            filtered_rows = [row for row in rows if float(row.get("intensity", 0.0)) > 0.0]
        if not filtered_rows:
            return []

        filtered_rows.sort(key=lambda row: float(row.get("intensity", 0.0)), reverse=True)
        dynamic_limit = min(max_return_points, max(settings.HEATMAP_TOP_N * 3, total_points * 2))
        return filtered_rows[:dynamic_limit]

    def _representative_point_for_cell(self, cell: dict, points: list[dict]) -> dict | None:
        valid_candidates = []
        for point in points:
            try:
                lat = float(point["latitude"])
                lng = float(point["longitude"])
            except (TypeError, ValueError, KeyError):
                continue

            if not (cell["min_lat"] <= lat <= cell["max_lat"] and cell["min_lng"] <= lng <= cell["max_lng"]):
                continue

            valid_candidates.append((point, lat, lng))

        if not valid_candidates:
            return None

        center_lat = (cell["min_lat"] + cell["max_lat"]) / 2
        center_lng = (cell["min_lng"] + cell["max_lng"]) / 2

        def score(candidate):
            point, lat, lng = candidate
            label = str(point.get("location_label") or "").strip()
            severity = float(point.get("severity_weight") or 1.0)
            distance = (lat - center_lat) ** 2 + (lng - center_lng) ** 2
            return (
                1 if label else 0,
                severity,
                -distance,
            )

        best = max(valid_candidates, key=score)
        return best[0]

    def _nearest_location_label_for_coords(self, lat: float, lng: float, points: list[dict]) -> str:
        nearest_real = None
        nearest_real_distance = None
        nearest_demo = None
        nearest_demo_distance = None

        for point in points:
            label = str(point.get("location_label") or "").strip()
            if not label:
                continue

            distance = (float(point["latitude"]) - lat) ** 2 + (float(point["longitude"]) - lng) ** 2
            is_demo_label = bool(point.get("is_synthetic")) or label.lower().startswith("demo hotspot")

            if is_demo_label:
                if nearest_demo_distance is None or distance < nearest_demo_distance:
                    nearest_demo = label
                    nearest_demo_distance = distance
                continue

            if nearest_real_distance is None or distance < nearest_real_distance:
                nearest_real = label
                nearest_real_distance = distance

        if nearest_real:
            return nearest_real
        if nearest_demo:
            return nearest_demo
        return ""

    def _build_previous_heatmap(self, parsed: HeatmapPayload) -> list[dict]:
        previous_from, previous_to = shift_period(parsed.date_from, parsed.date_to, parsed.comparison_mode)
        previous_records = self.fetcher.fetch(
            city=parsed.city,
            date_from=previous_from,
            date_to=previous_to,
            violation_type_id=parsed.violation_type_id,
            include_synthetic=parsed.include_synthetic,
        )
        previous_payload = HeatmapPayload(
            job_type=parsed.job_type,
            request_id=parsed.request_id,
            city=parsed.city,
            date_from=previous_from,
            date_to=previous_to,
            violation_type_id=parsed.violation_type_id,
            time_bucket=parsed.time_bucket,
            grid_size_meters=parsed.grid_size_meters,
            include_ranking=False,
            include_trend=False,
            include_synthetic=parsed.include_synthetic,
            comparison_mode="",
        )
        previous_points = self._prepare_points(previous_records, previous_payload)
        return self._run_analysis(previous_points, city=parsed.city, grid_size_meters=parsed.grid_size_meters)

    def fail_job(self, payload: dict, exc: Exception) -> None:
        request_id = str(payload.get("request_id") or "").strip()
        if not request_id:
            return
        log.warning(
            "Heatmap generation failed before Laravel AiJob was updated request_id=%s error=%s payload=%s",
            request_id,
            exc,
            json.dumps(payload, ensure_ascii=False),
        )

    def _parse_datetime(self, value):
        if not value:
            return None
        if isinstance(value, datetime):
            return value
        text = str(value).strip()
        if not text:
            return None
        if text.endswith("Z"):
            text = text[:-1] + "+00:00"
        try:
            return datetime.fromisoformat(text)
        except ValueError:
            return None
