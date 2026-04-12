"""OCR package.

This package contains the internal OCR pipeline pieces.
The outer worker file only starts the consumer loop.
"""

from core.ocr.consumer import main
from core.ocr.service import handle_job, ocr_vehicle

__all__ = ["main", "handle_job", "ocr_vehicle"]
