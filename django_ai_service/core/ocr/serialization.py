"""OCR serialization helpers.

This file contains small helpers for JSON-safe payload conversion.
"""

from typing import Any

from bson import ObjectId


def to_jsonable(obj: Any) -> Any:
    """Convert nested Mongo values into plain JSON-safe values."""
    if isinstance(obj, ObjectId):
        return str(obj)
    if isinstance(obj, dict):
        return {key: to_jsonable(value) for key, value in obj.items()}
    if isinstance(obj, (list, tuple)):
        return [to_jsonable(item) for item in obj]
    return obj
