from __future__ import annotations

import hashlib
import json


def build_cache_key(filters: dict) -> str:
    raw = json.dumps(filters, sort_keys=True, ensure_ascii=False, separators=(",", ":"))
    digest = hashlib.sha256(raw.encode("utf-8")).hexdigest()
    return f"heatmap:{digest}"
