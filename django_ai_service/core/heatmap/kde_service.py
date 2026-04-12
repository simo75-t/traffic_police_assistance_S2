from __future__ import annotations

import math
from typing import Any

from django.conf import settings


def _require_numpy():
    try:
        import numpy as np
    except ImportError as exc:
        raise RuntimeError("numpy is required for heatmap analysis") from exc
    return np


def _require_sklearn():
    try:
        from sklearn.neighbors import KernelDensity
    except ImportError as exc:
        raise RuntimeError("scikit-learn is required for heatmap analysis") from exc
    return KernelDensity


def evaluate_kde(points: list[dict[str, Any]], grid_cells: list[dict[str, Any]]) -> list[float]:
    if not grid_cells:
        return []
    if not points:
        return [0.0 for _ in grid_cells]

    np = _require_numpy()
    KernelDensity = _require_sklearn()

    train = []
    weights = []
    for point in points:
        train.append([float(point["latitude"]), float(point["longitude"])])
        weights.append(float(point.get("severity_weight") or 1.0))

    evaluate = [[cell["center_lat"], cell["center_lng"]] for cell in grid_cells]
    bandwidth = settings.HEATMAP_BANDWIDTH_METERS / 111_320
    kde = KernelDensity(kernel="gaussian", bandwidth=max(bandwidth, 0.0001))
    kde.fit(np.array(train), sample_weight=np.array(weights))
    scores = kde.score_samples(np.array(evaluate))
    return [float(math.exp(score)) for score in scores]
