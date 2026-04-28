from __future__ import annotations

from typing import Any


def _is_placeholder_area_name(value: str) -> bool:
    text = str(value or "").strip().lower()
    return not text or text.startswith("demo hotspot") or text.startswith("cell ")


def _normalize_area_name(value: str, city: str) -> str:
    if _is_placeholder_area_name(value):
        return f"قطاع مروري عالي الخطورة في {city}" if city else "قطاع مروري عالي الخطورة"
    return str(value).strip()


def build_fallback_prediction(signal_summary: dict[str, Any]) -> dict[str, Any]:
    hotspots = signal_summary.get("predicted_hotspots") or []
    overall_risk_level = str(signal_summary.get("overall_risk_level") or "medium")
    city = signal_summary.get("city") or ""

    recommendations = []
    for hotspot in hotspots[:3]:
        area_name = _normalize_area_name(hotspot["area_name"], city)
        recommendations.append(
            {
                "priority": hotspot["risk_level"],
                "action": f"تكليف دورية ميدانية إضافية ومتابعة الضبط المروري في {area_name}",
                "target_area": area_name,
                "target_time_bucket": hotspot["predicted_time_bucket"],
                "reason": hotspot["reason"],
            }
        )

    if not recommendations:
        recommendations.append(
            {
                "priority": overall_risk_level,
                "action": f"إعادة توزيع الدوريات داخل {city} ومراجعة البلاغات القادمة قبل اعتماد خطة الانتشار التالية",
                "target_area": city or "unknown",
                "target_time_bucket": "all_day",
                "reason": "البيانات الحالية غير كافية لتحديد قطاع تسمية موثوق بدقة عالية.",
            }
        )

    summary_bits = []
    if hotspots:
        summary_bits.append(f"تم رصد {len(hotspots)} مناطق مرشحة لارتفاع المخاطر")
        summary_bits.append(f"أعلى مستوى خطر عام هو {overall_risk_level}")

    return {
        "prediction_summary": "، ".join(summary_bits) or "تم إنشاء توصيات احتياطية اعتمادًا على الإشارات الحسابية فقط.",
        "overall_risk_level": overall_risk_level,
        "predicted_hotspots": [
            {
                "area_name": _normalize_area_name(item["area_name"], city),
                "risk_level": item["risk_level"],
                "predicted_time_bucket": item["predicted_time_bucket"],
                "predicted_violation_type": item["predicted_violation_type"],
                "confidence": item["confidence"],
                "reason": item["reason"],
            }
            for item in hotspots
        ],
        "recommendations": recommendations,
        "limitations": [
            "تم استخدام مولد احتياطي لأن استجابة النموذج لم تكن متاحة أو لم تطابق الصيغة المطلوبة.",
            "التوصيات مبنية على ملخص الخريطة الحرارية الحالية وليست بديلاً عن مراجعة السياق الميداني.",
        ],
    }
