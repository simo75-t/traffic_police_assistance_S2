from __future__ import annotations

import json
from datetime import timedelta

from django.conf import settings
from django.utils import timezone

from core.models import HeatmapCache


class HeatmapCacheService:
    def get_valid(self, cache_key: str):
        now = timezone.now()
        return HeatmapCache.objects.filter(cache_key=cache_key, expires_at__gt=now, status=HeatmapCache.STATUS_COMPLETED).first()

    def save(self, payload, cache_key: str, result: dict) -> HeatmapCache:
        expires_at = timezone.now() + timedelta(seconds=settings.HEATMAP_CACHE_TTL_SECONDS)
        cache, _ = HeatmapCache.objects.update_or_create(
            cache_key=cache_key,
            defaults={
                "city": payload.city,
                "date_from": payload.date_from,
                "date_to": payload.date_to,
                "violation_type_id": payload.violation_type_id,
                "time_bucket": payload.time_bucket,
                "grid_size_meters": payload.grid_size_meters,
                "include_synthetic": payload.include_synthetic,
                "comparison_mode": payload.comparison_mode,
                "heatmap_json": json.dumps(result.get("heatmap_points", []), ensure_ascii=False),
                "ranking_json": json.dumps(result.get("ranking", []), ensure_ascii=False),
                "trend_json": json.dumps(result.get("trend", []), ensure_ascii=False),
                "total_violations": int(result.get("meta", {}).get("total_violations", 0)),
                "status": HeatmapCache.STATUS_COMPLETED,
                "expires_at": expires_at,
            },
        )
        return cache

    def to_result(self, cache: HeatmapCache, request_id: str) -> dict:
        return {
            "request_id": request_id,
            "cache_key": cache.cache_key,
            "city": cache.city,
            "heatmap_points": cache.heatmap,
            "ranking": cache.ranking,
            "trend": cache.trend,
            "meta": {
                "date_from": cache.date_from.isoformat(),
                "date_to": cache.date_to.isoformat(),
                "time_bucket": cache.time_bucket or "",
                "grid_size_meters": cache.grid_size_meters,
                "total_violations": cache.total_violations,
                "from_cache": True,
            },
        }
