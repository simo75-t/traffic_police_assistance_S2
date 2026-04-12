from __future__ import annotations

from datetime import datetime


def resolve_time_bucket(ts: datetime) -> str:
    hour = ts.hour
    if 6 <= hour <= 11:
        return "morning"
    if 12 <= hour <= 17:
        return "afternoon"
    if 18 <= hour <= 23:
        return "evening"
    return "night"
