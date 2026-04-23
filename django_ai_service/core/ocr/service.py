"""OCR service layer.

This file contains the business logic for one OCR job.
The returned payload is sent back to Laravel, where AiJob remains the single
runtime source of truth for job results.
"""

import time
from typing import Any, Dict

from core.ocr.image_utils import encode_jpeg_b64, read_image_bgr, resolve_image_path
from core.ocr.vision import call_ollama_vision_json, normalize_out


def ocr_vehicle(image_path: str) -> Dict[str, str]:
    """Run the full OCR flow for one local vehicle image."""
    img = read_image_bgr(image_path)
    image_b64 = encode_jpeg_b64(img, quality=95)
    prompt = (
        'Return ONLY a JSON object with EXACT keys:\n'
        '{"plate_number":"","model":"","color":""}\n'
        'plate_number: read exactly what you see on the plate (keep Arabic text if present).\n'
        'model: car make only (Toyota/Hyundai/Kia...) or "".\n'
        'color: ONE word only (white/black/silver/gray/red/blue/green/yellow/brown/beige/gold/orange) or "".\n'
        "No extra keys. No extra text."
    )
    raw = call_ollama_vision_json(prompt, image_b64)
    return normalize_out(raw)


def handle_job(msg: Dict[str, Any]) -> Dict[str, Any]:
    """Process one OCR payload and return the normalized OCR result."""
    job_id = msg["job_id"]
    payload = msg.get("payload") or {}
    image_path = resolve_image_path(
        local_image_path=payload.get("local_image_path"),
        image_url=payload.get("image_url"),
    )
    ocr_result = ocr_vehicle(image_path)
    doc = {
        "job_id": job_id,
        "plate_number": ocr_result.get("plate_number", ""),
        "model": ocr_result.get("model", ""),
        "color": ocr_result.get("color", ""),
        "image_path": image_path,
        "created_at": time.time(),
    }
    return {
        "job_id": job_id,
        "plate_number": doc["plate_number"],
        "model": doc["model"],
        "color": doc["color"],
        "image_path": doc["image_path"],
        "created_at": doc["created_at"],
    }
