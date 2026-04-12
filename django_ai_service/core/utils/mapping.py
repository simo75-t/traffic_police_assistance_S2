"""Shared mapping helpers.

This file maps extracted text values to known city and violation type ids.
"""

from difflib import SequenceMatcher
from typing import Any, Dict, List, Optional


def best_match(name: str, items: List[Dict[str, Any]], min_ratio: float = 0.72) -> Optional[Dict[str, Any]]:
    """Return the closest lookup item for one free-text name."""
    if not name:
        return None
    name = name.strip()

    best = None
    best_ratio = 0.0
    for item in items:
        item_name = str(item.get("name", "")).strip()
        if not item_name:
            continue
        if item_name in name or name in item_name:
            return item
        ratio = SequenceMatcher(None, name, item_name).ratio()
        if ratio > best_ratio:
            best_ratio = ratio
            best = item

    return best if best and best_ratio >= min_ratio else None


def map_extracted_to_fields(
    extracted: Dict[str, Any],
    cities: List[Dict[str, Any]],
    violation_types: List[Dict[str, Any]],
) -> Dict[str, Any]:
    """Convert extracted names into the final field payload expected downstream."""
    fields: Dict[str, Any] = {}

    if extracted.get("street_name"):
        fields["street_name"] = extracted["street_name"]
    if extracted.get("landmark"):
        fields["landmark"] = extracted["landmark"]
    if extracted.get("description"):
        fields["description"] = extracted["description"]

    city = best_match(extracted.get("city_name") or "", cities)
    if city:
        fields["city_id"] = str(city["id"])

    violation_type = best_match(extracted.get("violation_type_name") or "", violation_types)
    if violation_type:
        fields["violation_type_id"] = str(violation_type["id"])

    return fields
