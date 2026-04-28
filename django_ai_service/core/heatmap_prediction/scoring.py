from __future__ import annotations

from collections import defaultdict
from dataclasses import asdict
from typing import Any

from django.conf import settings

from core.heatmap_prediction.payloads import HeatmapPredictionSummary


def _clamp(value: float, minimum: float = 0.0, maximum: float = 1.0) -> float:
    return max(minimum, min(maximum, value))


def _normalize_trend(trend: str) -> str:
    normalized = str(trend or "stable").strip().lower()
    if normalized == "up":
        return "increasing"
    if normalized == "down":
        return "decreasing"
    return normalized


def _trend_score(trend: str) -> float:
    normalized = _normalize_trend(trend)
    if normalized == "increasing":
        return 1.0
    if normalized == "stable":
        return 0.45
    return 0.15


def _change_score(percentage_change: float) -> float:
    if percentage_change <= 0:
        return 0.2 if percentage_change > -10 else 0.05
    return _clamp(percentage_change / 40.0, 0.25, 1.0)


def _rank_score(rank: int, total: int) -> float:
    if total <= 1:
        return 1.0
    return _clamp(1.0 - ((rank - 1) / max(total - 1, 1)))


def _recent_previous_signal(recent_count: int | None, previous_count: int | None, percentage_change: float) -> dict[str, Any]:
    if recent_count is not None and previous_count is not None:
        if recent_count > previous_count:
            direction = "higher"
        elif recent_count < previous_count:
            direction = "lower"
        else:
            direction = "similar"
        baseline = max(previous_count, 1)
        computed_change = ((recent_count - previous_count) / baseline) * 100.0
        return {
            "recent_count": recent_count,
            "previous_count": previous_count,
            "recent_vs_previous": direction,
            "derived_percentage_change": round(computed_change, 2),
        }

    direction = "higher" if percentage_change > 5 else "lower" if percentage_change < -5 else "similar"
    estimated_previous = 100
    estimated_recent = max(0, round(estimated_previous * (1 + (percentage_change / 100.0))))
    return {
        "recent_count": estimated_recent,
        "previous_count": estimated_previous,
        "recent_vs_previous": direction,
        "derived_percentage_change": round(percentage_change, 2),
    }


def classify_risk_level(score: float) -> str:
    if score >= 0.85:
        return "critical"
    if score >= 0.68:
        return "high"
    if score >= 0.45:
        return "medium"
    return "low"


def build_prediction_signals(summary: HeatmapPredictionSummary) -> dict[str, Any]:
    max_hotspots = int(getattr(settings, "HEATMAP_PREDICTION_MAX_HOTSPOTS", 5))
    hotspots = summary.hotspots[:max_hotspots]
    total = len(hotspots)
    hotspot_signals: list[dict[str, Any]] = []
    type_scores: defaultdict[str, float] = defaultdict(float)
    time_bucket_scores: defaultdict[str, float] = defaultdict(float)

    for hotspot in hotspots:
        trend = _normalize_trend(hotspot.trend)
        density_score = _clamp(float(hotspot.density_score), 0.0, 1.2)
        rank_score = _rank_score(hotspot.rank, total)
        trend_score = _trend_score(trend)
        change_score = _change_score(float(hotspot.percentage_change))
        moving_average_score = 0.5
        if hotspot.moving_average_score is not None:
            moving_average_score = _clamp(float(hotspot.moving_average_score))

        composite_score = (
            density_score * 0.35
            + rank_score * 0.2
            + trend_score * 0.2
            + change_score * 0.2
            + moving_average_score * 0.05
        )
        composite_score = round(_clamp(composite_score), 4)
        risk_level = classify_risk_level(composite_score)
        signal_quality = sum(
            [
                1 if hotspot.area_name else 0,
                1 if hotspot.dominant_violation_type else 0,
                1 if hotspot.dominant_time_bucket else 0,
                1 if hotspot.trend else 0,
                1 if hotspot.percentage_change is not None else 0,
            ]
        ) / 5.0
        confidence = round(
            _clamp(0.35 + (composite_score * 0.5) + (signal_quality * 0.15), getattr(settings, "HEATMAP_PREDICTION_MIN_CONFIDENCE", 0.35), 0.99),
            2,
        )

        recent_previous = _recent_previous_signal(
            hotspot.recent_count,
            hotspot.previous_count,
            float(hotspot.percentage_change),
        )

        hotspot_signal = {
            "area_name": hotspot.area_name,
            "risk_level": risk_level,
            "predicted_time_bucket": hotspot.dominant_time_bucket,
            "predicted_violation_type": hotspot.dominant_violation_type,
            "confidence": confidence,
            "reason": (
                f"الكثافة {round(density_score, 2)}، الترتيب {hotspot.rank}، "
                f"الاتجاه {trend}، والتغير {round(float(hotspot.percentage_change), 2)}%"
            ),
            "signals": {
                "density_score": round(density_score, 4),
                "rank": hotspot.rank,
                "rank_score": round(rank_score, 4),
                "trend": trend,
                "trend_score": round(trend_score, 4),
                "percentage_change": round(float(hotspot.percentage_change), 2),
                "change_score": round(change_score, 4),
                "moving_average_score": round(moving_average_score, 4),
                "recent_vs_previous": recent_previous,
                "composite_score": composite_score,
            },
        }
        hotspot_signals.append(hotspot_signal)

        if trend == "increasing" or float(hotspot.percentage_change) > 0:
            type_scores[hotspot.dominant_violation_type] += composite_score
            time_bucket_scores[hotspot.dominant_time_bucket] += composite_score

    hotspot_signals.sort(
        key=lambda item: (
            {"critical": 4, "high": 3, "medium": 2, "low": 1}[item["risk_level"]],
            item["confidence"],
        ),
        reverse=True,
    )

    average_score = sum(item["signals"]["composite_score"] for item in hotspot_signals) / max(len(hotspot_signals), 1)
    peak_score = max((item["signals"]["composite_score"] for item in hotspot_signals), default=0.0)
    overall_score = (average_score * 0.65) + (peak_score * 0.35)
    overall_risk_level = classify_risk_level(overall_score)

    increasing_violation_types = [
        {"violation_type": name, "score": round(score, 4)}
        for name, score in sorted(type_scores.items(), key=lambda item: item[1], reverse=True)
    ]
    high_risk_time_buckets = [
        {"time_bucket": name, "score": round(score, 4)}
        for name, score in sorted(time_bucket_scores.items(), key=lambda item: item[1], reverse=True)
    ]

    return {
        "city": summary.city,
        "from_date": summary.from_date.isoformat(),
        "to_date": summary.to_date.isoformat(),
        "violation_type": summary.violation_type,
        "time_bucket": summary.time_bucket,
        "overall_risk_level": overall_risk_level,
        "overall_score": round(overall_score, 4),
        "predicted_hotspots": hotspot_signals,
        "predicted_increasing_violation_types": increasing_violation_types,
        "predicted_high_risk_time_buckets": high_risk_time_buckets,
        "raw_hotspots": [asdict(item) for item in hotspots],
    }
