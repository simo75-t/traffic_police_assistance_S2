"""Legacy wrapper for static lookup loading helpers.

Prefer importing from `core.utils.lookups_loader`.
This wrapper keeps older imports working.
"""

from core.utils.lookups_loader import load_lookups

__all__ = ["load_lookups"]
