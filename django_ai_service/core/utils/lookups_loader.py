"""Shared local lookup loader.

This file loads static city and violation type lookup JSON files from disk.
"""

import json
from pathlib import Path


BASE = Path(__file__).resolve().parents[1]
CITIES_PATH = BASE / "lookups" / "cities.json"
VTYPES_PATH = BASE / "lookups" / "violation_types.json"

_cache = {"cities": None, "vtypes": None}


def load_lookups():
    """Load static lookup JSON files once and keep them cached in memory."""
    global _cache
    if _cache["cities"] is None:
        _cache["cities"] = json.loads(CITIES_PATH.read_text(encoding="utf-8"))
    if _cache["vtypes"] is None:
        _cache["vtypes"] = json.loads(VTYPES_PATH.read_text(encoding="utf-8"))
    return _cache["cities"], _cache["vtypes"]
