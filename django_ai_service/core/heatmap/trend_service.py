from __future__ import annotations

from datetime import timedelta


def shift_period(date_from, date_to, comparison_mode: str):
    duration = date_to - date_from
    if comparison_mode == "week_over_week":
        offset = timedelta(days=7)
    elif comparison_mode == "month_over_month":
        offset = timedelta(days=30)
    else:
        raise ValueError("Unsupported comparison mode")
    return date_from - offset, date_from - offset + duration


def compare_heatmaps(current_points: list[dict], previous_points: list[dict], limit: int = 10) -> list[dict]:
    previous_map = {point["cell_id"]: point for point in previous_points}
    trend = []
    for point in current_points:
        prev = previous_map.get(point["cell_id"])
        previous_intensity = float(prev["intensity"]) if prev else 0.0
        current_intensity = float(point["intensity"])
        diff = current_intensity - previous_intensity
        if max(current_intensity, previous_intensity) < 0.05 and abs(diff) < 0.05:
            continue

        if diff > 0.05:
            label = "up"
        elif diff < -0.05:
            label = "down"
        else:
            label = "stable"
        trend.append(
            {
                "cell_id": point["cell_id"],
                "lat": point["lat"],
                "lng": point["lng"],
                "area_label": point.get("area_label", ""),
                "current_intensity": round(current_intensity, 6),
                "previous_intensity": round(previous_intensity, 6),
                "difference": round(diff, 6),
                "trend": label,
            }
        )

    trend.sort(
        key=lambda item: (
            abs(float(item["difference"])),
            max(float(item["current_intensity"]), float(item["previous_intensity"])),
        ),
        reverse=True,
    )
    return trend[:limit]
