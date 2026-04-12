from __future__ import annotations

import math

from django.conf import settings

from core.heatmap.constants import CITY_BOUNDING_BOXES


class UnsupportedCityError(ValueError):
    pass


CITY_ALIASES = {
    "damascus": "damascus",
    "damascus city": "damascus",
    "damascus governorate": "damascus",
    "دمشق": "damascus",
    "مدينة دمشق": "damascus",
    "محافظة دمشق": "damascus",
    "rif dimashq": "rif_dimashq",
    "rural damascus": "rif_dimashq",
    "damascus countryside": "rif_dimashq",
    "ريف دمشق": "rif_dimashq",
    "aleppo": "aleppo",
    "aleppo governorate": "aleppo",
    "حلب": "aleppo",
    "homs": "homs",
    "homs governorate": "homs",
    "حمص": "homs",
    "hama": "hama",
    "hama governorate": "hama",
    "حماة": "hama",
    "latakia": "latakia",
    "latakia governorate": "latakia",
    "اللاذقية": "latakia",
    "tartus": "tartus",
    "tartus governorate": "tartus",
    "طرطوس": "tartus",
    "idlib": "idlib",
    "idlib governorate": "idlib",
    "إدلب": "idlib",
    "ادلب": "idlib",
    "ar-raqqah": "ar_raqqah",
    "ar raqqah": "ar_raqqah",
    "raqqa": "ar_raqqah",
    "raqqa governorate": "ar_raqqah",
    "الرقة": "ar_raqqah",
    "الرقه": "ar_raqqah",
    "deir ez-zor": "deir_ez_zor",
    "deir ez zor": "deir_ez_zor",
    "deir ezzor": "deir_ez_zor",
    "deir ez-zor governorate": "deir_ez_zor",
    "دير الزور": "deir_ez_zor",
    "ديرالزور": "deir_ez_zor",
    "daraa": "daraa",
    "daraa governorate": "daraa",
    "درعا": "daraa",
    "as-suwayda": "as_suwayda",
    "as suwayda": "as_suwayda",
    "sweida": "as_suwayda",
    "suwayda": "as_suwayda",
    "السويداء": "as_suwayda",
    "السويدا": "as_suwayda",
    "quneitra": "quneitra",
    "quneitra governorate": "quneitra",
    "القنيطرة": "quneitra",
    "القنيطره": "quneitra",
    "al-hasakah": "al_hasakah",
    "al hasakah": "al_hasakah",
    "hasakah": "al_hasakah",
    "qamishli": "al_hasakah",
    "al-qamishli": "al_hasakah",
    "al qamishli": "al_hasakah",
    "al-hasakah governorate": "al_hasakah",
    "القامشلي": "al_hasakah",
    "الحسكة": "al_hasakah",
    "ط¯ظ…ط´ظ‚": "damascus",
    "ظ…ط­ط§ظپط¸ط© ط¯ظ…ط´ظ‚": "damascus",
    "ظ…ط¯ظٹظ†ط© ط¯ظ…ط´ظ‚": "damascus",
    "ط±ظٹظپ ط¯ظ…ط´ظ‚": "rif_dimashq",
    "ط±ظٹظپط¯ظ…ط´ظ‚": "rif_dimashq",
}


def normalize_city_key(city: str) -> str:
    raw = city.strip()
    if not raw:
        return ""

    lowered = raw.lower()
    return CITY_ALIASES.get(raw, CITY_ALIASES.get(lowered, lowered))


def get_city_bbox(city: str) -> dict:
    city_key = normalize_city_key(city)
    bbox = CITY_BOUNDING_BOXES.get(city_key)
    if not bbox:
        raise UnsupportedCityError(f"Unsupported city: {city}")
    return bbox


def build_bbox_from_points(points: list[dict]) -> dict:
    valid_points = [
        point for point in points
        if point.get("latitude") is not None and point.get("longitude") is not None
    ]
    if not valid_points:
        raise UnsupportedCityError("Unsupported city and no points available for dynamic bbox")

    latitudes = [float(point["latitude"]) for point in valid_points]
    longitudes = [float(point["longitude"]) for point in valid_points]
    lat_padding = max(0.02, (max(latitudes) - min(latitudes)) * 0.15)
    lng_padding = max(0.02, (max(longitudes) - min(longitudes)) * 0.15)

    return {
        "name": "dynamic",
        "min_lat": min(latitudes) - lat_padding,
        "max_lat": max(latitudes) + lat_padding,
        "min_lng": min(longitudes) - lng_padding,
        "max_lng": max(longitudes) + lng_padding,
    }


def build_grid(city: str, grid_size_meters: int, points: list[dict] | None = None) -> list[dict]:
    try:
        bbox = get_city_bbox(city)
    except UnsupportedCityError:
        bbox = build_bbox_from_points(points or [])

    avg_lat = (bbox["min_lat"] + bbox["max_lat"]) / 2
    lat_step = grid_size_meters / 111_320
    lng_divisor = max(0.000001, 111_320 * math.cos(math.radians(avg_lat)))
    lng_step = grid_size_meters / lng_divisor

    estimated_rows = max(1, math.ceil((bbox["max_lat"] - bbox["min_lat"]) / lat_step))
    estimated_cols = max(1, math.ceil((bbox["max_lng"] - bbox["min_lng"]) / lng_step))
    estimated_cells = estimated_rows * estimated_cols
    max_cells = max(100, int(getattr(settings, "HEATMAP_GRID_MAX_CELLS", 2500)))

    if estimated_cells > max_cells:
        scale = math.sqrt(estimated_cells / max_cells)
        lat_step *= scale
        lng_step *= scale

    cells = []
    row = 0
    lat = bbox["min_lat"]
    while lat < bbox["max_lat"]:
        col = 0
        lng = bbox["min_lng"]
        next_lat = min(lat + lat_step, bbox["max_lat"])
        while lng < bbox["max_lng"]:
            next_lng = min(lng + lng_step, bbox["max_lng"])
            cells.append(
                {
                    "cell_id": f"{row}:{col}",
                    "row": row,
                    "col": col,
                    "min_lat": lat,
                    "max_lat": next_lat,
                    "min_lng": lng,
                    "max_lng": next_lng,
                    "center_lat": (lat + next_lat) / 2,
                    "center_lng": (lng + next_lng) / 2,
                }
            )
            lng = next_lng
            col += 1
        lat = next_lat
        row += 1
    return cells


def is_within_bbox(lat: float, lng: float, city: str) -> bool:
    try:
        bbox = get_city_bbox(city)
    except UnsupportedCityError:
        return True
    return bbox["min_lat"] <= lat <= bbox["max_lat"] and bbox["min_lng"] <= lng <= bbox["max_lng"]
