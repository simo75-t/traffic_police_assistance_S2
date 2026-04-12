from __future__ import annotations

import logging
import random
from datetime import datetime, timedelta, timezone

from django.conf import settings

from core.heatmap.spatial_grid_service import UnsupportedCityError, get_city_bbox


log = logging.getLogger("HEATMAP_SYNTHETIC")


def generate_synthetic_records(city: str, date_from, date_to) -> list[dict]:
    try:
        bbox = get_city_bbox(city)
    except UnsupportedCityError:
        log.warning("Synthetic heatmap points skipped for unsupported city=%s", city)
        return []

    rng = random.Random(f"{city}:{date_from}:{date_to}")
    count = settings.HEATMAP_SYNTHETIC_POINTS
    total_days = max((date_to - date_from).days + 1, 1)
    lat_span = bbox["max_lat"] - bbox["min_lat"]
    lng_span = bbox["max_lng"] - bbox["min_lng"]
    center_lat = bbox["min_lat"] + (lat_span * 0.5)
    center_lng = bbox["min_lng"] + (lng_span * 0.5)

    centers = [
        {"lat": center_lat + (lat_span * 0.18), "lng": center_lng - (lng_span * 0.12), "spread_lat": 0.0030, "spread_lng": 0.0038, "weight": 0.27},
        {"lat": center_lat + (lat_span * 0.02), "lng": center_lng + (lng_span * 0.10), "spread_lat": 0.0028, "spread_lng": 0.0035, "weight": 0.23},
        {"lat": center_lat - (lat_span * 0.10), "lng": center_lng - (lng_span * 0.05), "spread_lat": 0.0034, "spread_lng": 0.0040, "weight": 0.21},
        {"lat": center_lat - (lat_span * 0.18), "lng": center_lng + (lng_span * 0.16), "spread_lat": 0.0025, "spread_lng": 0.0030, "weight": 0.17},
        {"lat": center_lat + (lat_span * 0.08), "lng": center_lng + (lng_span * 0.24), "spread_lat": 0.0022, "spread_lng": 0.0026, "weight": 0.12},
    ]
    violation_type_ids = [1, 3, 5, 6, 8, 10, 13, 15, 19, 24]
    severity_weights = [1.0, 1.4, 1.8, 2.2, 2.8, 3.2]

    rows = []
    for _ in range(count):
        center = rng.choices(centers, weights=[item["weight"] for item in centers], k=1)[0]
        lat = min(max(rng.gauss(center["lat"], center["spread_lat"]), bbox["min_lat"]), bbox["max_lat"])
        lng = min(max(rng.gauss(center["lng"], center["spread_lng"]), bbox["min_lng"]), bbox["max_lng"])
        day_offset = rng.randrange(0, total_days)
        hour = rng.choice([7, 8, 9, 12, 13, 16, 18, 19, 21, 23, 2])
        created_at = datetime.combine(date_from + timedelta(days=day_offset), datetime.min.time(), tzinfo=timezone.utc)
        created_at = created_at + timedelta(hours=hour, minutes=rng.randrange(0, 60))

        rows.append(
            {
                "latitude": lat,
                "longitude": lng,
                "violation_type_id": rng.choice(violation_type_ids),
                "created_at": created_at.isoformat(),
                "severity_weight": rng.choice(severity_weights),
                "is_synthetic": True,
                "city": city,
                "location_label": f"Demo hotspot {centers.index(center) + 1}",
            }
        )
    return rows
