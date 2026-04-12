"""Shared Ollama extraction helper.

This file contains a small reusable extractor for Arabic traffic transcripts.
"""

import json
import re
from typing import Any, Dict, Optional

import requests


OLLAMA_SESSION = requests.Session()
OLLAMA_SESSION.trust_env = False


def first_json_object(text: str) -> Optional[Dict[str, Any]]:
    """Extract the first JSON object from model output."""
    if not text:
        return None
    try:
        return json.loads(text)
    except Exception:
        pass

    match = re.search(r"\{.*\}", text, flags=re.DOTALL)
    if not match:
        return None
    try:
        return json.loads(match.group(0))
    except Exception:
        return None


def normalize_optional_text(value: Any) -> Optional[str]:
    """Normalize one optional text field returned by the model."""
    if value is None:
        return None
    text = str(value).strip()
    return text if text else None


def ollama_extract_fields(
    transcript: str,
    model: str = "qwen2.5vl:3b",
    ollama_url: str = "http://127.0.0.1:11434",
    timeout_s: int = 120,
) -> Dict[str, Any]:
    """Extract a small set of structured traffic fields with Ollama."""
    prompt = f"""
You are an information extraction system for traffic violation reports (Arabic).
Extract structured fields from the transcript.

Return ONLY valid JSON. No markdown. No explanations. No extra keys.

Rules:
- If a field is not mentioned, return null.
- Keep Arabic names as-is.
- description: short Arabic summary of the violation.

JSON schema (exact keys only):
{{
  "street_name": string|null,
  "landmark": string|null,
  "city_name": string|null,
  "violation_type_name": string|null,
  "description": string|null
}}

Transcript:
<<<{transcript}>>>
""".strip()

    payload = {
        "model": model,
        "prompt": prompt,
        "stream": False,
        "options": {
            "temperature": 0.1,
            "top_p": 0.9,
        },
    }

    response = OLLAMA_SESSION.post(f"{ollama_url}/api/generate", json=payload, timeout=timeout_s)
    response.raise_for_status()

    data = response.json()
    raw = data.get("response", "")
    parsed = first_json_object(raw)
    if parsed is None:
        raise RuntimeError(f"LLM output is not valid JSON. Raw: {raw[:300]}")

    return {
        "street_name": normalize_optional_text(parsed.get("street_name")),
        "landmark": normalize_optional_text(parsed.get("landmark")),
        "city_name": normalize_optional_text(parsed.get("city_name")),
        "violation_type_name": normalize_optional_text(parsed.get("violation_type_name")),
        "description": normalize_optional_text(parsed.get("description")),
    }
