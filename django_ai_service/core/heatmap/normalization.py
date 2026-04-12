from __future__ import annotations

from typing import Iterable


def normalize_scores(values: Iterable[float]) -> list[float]:
    numbers = [float(v) for v in values]
    if not numbers:
        return []
    low = min(numbers)
    high = max(numbers)
    if high == low:
        return [1.0 if high > 0 else 0.0 for _ in numbers]
    return [(value - low) / (high - low) for value in numbers]
