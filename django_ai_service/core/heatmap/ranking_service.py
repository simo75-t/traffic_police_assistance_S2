from __future__ import annotations


def build_ranking(points: list[dict], top_n: int) -> list[dict]:
    rows = sorted(points, key=lambda item: item["intensity"], reverse=True)[:top_n]
    return [
        {
            "rank": index + 1,
            "cell_id": row.get("cell_id"),
            "lat": row["lat"],
            "lng": row["lng"],
            "intensity": row["intensity"],
            "area_label": row.get("area_label", ""),
        }
        for index, row in enumerate(rows)
    ]
