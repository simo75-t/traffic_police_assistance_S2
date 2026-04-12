"""STT lookup helpers.

This file loads city and violation type vocabularies from Laravel.
"""

import time
from typing import Any, Dict, Optional, Tuple

import difflib
import requests

from core.stt.config import CITIES_API, VIOLATION_TYPES_API, log
from core.stt.normalization import norm


CACHE = {"cities": None, "types": None, "ts": 0.0}
CACHE_TTL = 300


def fetch_lookup_map(url: str, name_key: str = "name") -> Dict[str, Any]:
    """Fetch lookup items and convert them to a lower-case name-to-id map."""
    response = requests.get(url, timeout=8)
    response.raise_for_status()
    data = response.json()
    mapping: Dict[str, Any] = {}

    def add_item(item: dict):
        """Store one lookup entry when both its id and display name exist."""
        name = norm(item.get(name_key, ""))
        item_id = item.get("id")
        if name and item_id is not None:
            mapping[name.lower()] = item_id

    if isinstance(data, list):
        for item in data:
            if isinstance(item, dict):
                add_item(item)
    elif isinstance(data, dict):
        arr = data.get("data") or data.get("results") or data.get("items") or []
        if isinstance(arr, list):
            for item in arr:
                if isinstance(item, dict):
                    add_item(item)
    return mapping


def get_lookups() -> Tuple[Dict[str, Any], Dict[str, Any]]:
    """Return cached city and violation-type lookups."""
    now = time.time()
    if CACHE["cities"] and CACHE["types"] and (now - CACHE["ts"]) < CACHE_TTL:
        return CACHE["cities"], CACHE["types"]
    cities = fetch_lookup_map(CITIES_API, "name")
    types = fetch_lookup_map(VIOLATION_TYPES_API, "name")
    CACHE["cities"], CACHE["types"], CACHE["ts"] = cities, types, now
    return cities, types


def fuzzy_pick_key(name: str, vocab_keys: list, cutoff: float) -> Optional[str]:
    """Match a free-text value against one lookup vocabulary."""
    name = norm(name).lower()
    if not name:
        return None
    if name in vocab_keys:
        return name
    best = difflib.get_close_matches(name, vocab_keys, n=1, cutoff=cutoff)
    return best[0] if best else None


def map_ids(city_name: Optional[str], violation_name: Optional[str]) -> Tuple[Optional[Any], Optional[Any], Optional[str], Optional[str]]:
    """Resolve extracted city and violation type names into Laravel ids."""
    city_id = vio_id = None
    city_fixed = vio_fixed = None
    try:
        cities, types = get_lookups()
        city_keys = list(cities.keys())
        type_keys = list(types.keys())
        if city_name:
            city_key = fuzzy_pick_key(city_name, city_keys, cutoff=0.55)
            if city_key:
                city_id = cities[city_key]
                city_fixed = city_key
        if violation_name:
            violation_key = fuzzy_pick_key(violation_name, type_keys, cutoff=0.50)
            if violation_key:
                vio_id = types[violation_key]
                vio_fixed = violation_key
    except Exception as exc:
        log.warning("Lookup mapping failed: %r", exc)
    return city_id, vio_id, (city_fixed.title() if city_fixed else None), (vio_fixed if vio_fixed else None)
