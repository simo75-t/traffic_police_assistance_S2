"""STT package.

This package contains the internal STT pipeline pieces.
The outer worker file only starts the consumer loop.
"""

from core.stt.consumer import main
from core.stt.service import handle_job

__all__ = ["main", "handle_job"]
