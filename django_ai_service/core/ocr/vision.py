"""OCR model helpers.

This file prepares the Ollama request and parses the model response.
"""

import json
import re
import time
from typing import Any, Dict, Optional

from core.ocr.config import (
    OLLAMA_MODEL,
    OLLAMA_NUM_CTX,
    OLLAMA_NUM_PREDICT,
    OLLAMA_RETRIES,
    OLLAMA_SESSION,
    OLLAMA_TIMEOUT,
    OLLAMA_URL,
    log,
)


def parse_json_from_text(text: str) -> Dict[str, Any]:
    """Extract one JSON object from raw model text or fenced markdown."""
    text = (text or "").strip()
    if not text:
        raise ValueError("Empty model response")
    text = re.sub(r"^```(?:json)?\s*", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s*```$", "", text)
    if text.startswith("{") and text.endswith("}"):
        return json.loads(text)
    match = re.search(r"\{[\s\S]*\}", text)
    if not match:
        raise ValueError(f"No JSON found in model response: {text[:200]!r}")
    return json.loads(match.group(0))


def normalize_out(data: Any) -> Dict[str, str]:
    """Normalize OCR output into a stable schema."""
    if not isinstance(data, dict):
        data = {}
    plate = re.sub(r"\s+", " ", (data.get("plate_number") or "").strip()).strip()
    model = re.sub(r"\s+", " ", (data.get("model") or "").strip()).strip()
    color = re.sub(r"\s+", " ", (data.get("color") or "").strip()).strip()
    plate = re.sub(r"[^\u0600-\u06FF0-9A-Za-z\s\-]", "", plate).strip()
    color = color.split()[0] if color else ""
    return {"plate_number": plate, "model": model, "color": color}


def call_ollama_vision_json(prompt_text: str, image_b64: str) -> Dict[str, Any]:
    """Call Ollama vision and force a JSON object response."""
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt_text,
        "stream": False,
        "images": [image_b64],
        "format": "json",
        "options": {
            "temperature": 0,
            "num_predict": OLLAMA_NUM_PREDICT,
            "num_ctx": OLLAMA_NUM_CTX,
        },
    }
    last_err: Optional[Exception] = None
    for attempt in range(OLLAMA_RETRIES + 1):
        try:
            response = OLLAMA_SESSION.post(
                OLLAMA_URL,
                json=payload,
                timeout=(10, OLLAMA_TIMEOUT),
            )
            if response.status_code >= 400:
                raise RuntimeError(f"Ollama HTTP {response.status_code}: {response.text}")
            data = response.json()
            text = (data.get("response") or "").strip() or (data.get("thinking") or "").strip()
            if not text:
                raise ValueError(f"Empty Ollama output. Full={data}")
            parsed = parse_json_from_text(text)
            if not isinstance(parsed, dict):
                raise ValueError(f"Ollama output is not a JSON object. got={type(parsed)} parsed={parsed}")
            return parsed
        except Exception as exc:
            last_err = exc
            if attempt < OLLAMA_RETRIES:
                sleep_s = 1.5 * (attempt + 1)
                log.warning(
                    "Ollama failed (attempt %s/%s). retry in %.1fs. err=%r",
                    attempt + 1,
                    OLLAMA_RETRIES + 1,
                    sleep_s,
                    exc,
                )
                time.sleep(sleep_s)
    raise RuntimeError(f"Ollama call failed after retries: {last_err}") from last_err
