"""Legacy wrapper for mapping helpers.

Prefer importing from `core.utils.mapping`.
This wrapper keeps older imports working.
"""

from core.utils.mapping import best_match, map_extracted_to_fields

__all__ = ["best_match", "map_extracted_to_fields"]
