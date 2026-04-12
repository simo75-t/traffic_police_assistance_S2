"""OCR image helpers.

This file handles local/remote image loading and JPEG base64 encoding.
"""

import base64
import tempfile
from pathlib import Path

import cv2
import requests


def read_image_bgr(image_path: str):
    """Read one image from disk and fail clearly if OpenCV cannot decode it."""
    img = cv2.imread(image_path)
    if img is None:
        raise RuntimeError(f"Cannot read image: {image_path}")
    return img


def resolve_image_path(local_image_path: str | None = None, image_url: str | None = None) -> str:
    """Resolve one OCR image source into a readable local path."""
    if local_image_path:
        local_path = Path(local_image_path)
        if local_path.exists():
            return str(local_path)
    if not image_url:
        raise RuntimeError("No readable image source found")
    response = requests.get(image_url, timeout=30)
    response.raise_for_status()
    suffix = Path(image_url).suffix or ".img"
    tmp = tempfile.NamedTemporaryFile(delete=False, suffix=suffix)
    tmp.write(response.content)
    tmp.close()
    return tmp.name


def encode_jpeg_b64(img_bgr, quality: int = 95) -> str:
    """Encode one OpenCV image as JPEG base64 without resizing it."""
    ok, buf = cv2.imencode(".jpg", img_bgr, [int(cv2.IMWRITE_JPEG_QUALITY), quality])
    if not ok:
        raise RuntimeError("Failed to encode JPEG")
    return base64.b64encode(buf.tobytes()).decode("utf-8")
