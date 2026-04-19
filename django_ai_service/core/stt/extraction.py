"""STT extraction helpers.

This file handles LM Studio extraction and field repair logic.
"""

import json
import re
from typing import Any, Dict

import requests

from core.stt.config import LMSTUDIO_CHAT, LMSTUDIO_MODEL, LMSTUDIO_TIMEOUT, log
from core.stt.lookups import map_ids
from core.stt.normalization import (
    best_plate_from_text,
    clean_landmark,
    clean_owner,
    extract_street_from_text,
    guess_city_from_text,
    looks_like_street,
    norm,
    normalize_city_name,
    normalize_color,
    normalize_plate,
)


def extract_json_block(s: str) -> str:
    """Extract the first JSON object block from LM Studio output."""
    s = (s or "").strip()
    if s.startswith("```"):
        s = re.sub(r"^```(?:json)?\s*", "", s, flags=re.IGNORECASE)
        s = re.sub(r"\s*```$", "", s)
    match = re.search(r"\{[\s\S]*\}", s)
    return match.group(0).strip() if match else s


def lmstudio_extract(stt_text: str) -> Dict[str, Any]:
    """Ask LM Studio to convert transcript text into a structured JSON object."""
    system_prompt = """
You extract structured data from Arabic traffic violation speech transcripts for Syrian traffic police.
Return ONLY one valid JSON object with the exact schema requested.
Do not write markdown, comments, code fences, or explanatory text.
If a value is missing, return an empty string for that field.
Never invent a city, street, plate, or person name that is not supported by the transcript.
""".strip()

    user_prompt = f"""
Transcript:
{stt_text}

Schema:
{{
  "vehicle_plate": "",
  "vehicle_owner": "",
  "vehicle_model": "",
  "vehicle_color": "",
  "city": "",
  "street_name": "",
  "landmark": "",
  "violation_type": "",
  "description": ""
}}

Extraction rules:
1. vehicle_plate:
   - digits only
   - no spaces, no country code, no extra words
2. city:
   - one Syrian city/governorate name only
   - do not put street names here
3. street_name:
   - include the street/road/highway phrase if clearly mentioned
4. landmark:
   - nearest landmark or notable place only
5. violation_type:
   - very short label in Arabic such as "اصطفاف مزدوج" or "قطع إشارة"
6. description:
   - one short Arabic sentence summarizing the violation
7. Do not mix owner/model/color/location across fields.
8. If uncertain, leave the field empty.
""".strip()

    payload = {
        "model": LMSTUDIO_MODEL,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
        "temperature": 0.0,
        "top_p": 1.0,
        "max_tokens": 260,
        "stream": False,
        "response_format": {"type": "json_object"},
    }
    try:
        response = requests.post(LMSTUDIO_CHAT, json=payload, timeout=LMSTUDIO_TIMEOUT)
        if not response.ok:
            log.warning("LM Studio HTTP %s: %s", response.status_code, response.text[:300])
            return {}
        data = response.json()
        content = data["choices"][0]["message"]["content"]
        cleaned = extract_json_block((content or "").strip())
        obj = json.loads(cleaned)
        return obj if isinstance(obj, dict) else {}
    except Exception as exc:
        log.warning("LM Studio extract failed: %r", exc)
        return {}


def finalize_fields(stt_text: str, llm: Dict[str, Any]) -> Dict[str, Any]:
    """Merge LM Studio output with rule-based repair and id mapping."""
    llm = llm if isinstance(llm, dict) else {}
    plate = normalize_plate(llm.get("vehicle_plate", "")) or best_plate_from_text(stt_text)
    owner = clean_owner(llm.get("vehicle_owner", ""))
    model = norm(llm.get("vehicle_model", ""))
    color = normalize_color(llm.get("vehicle_color", "")) or normalize_color(stt_text)
    if model and normalize_color(model) and not color:
        color = normalize_color(model)
        model = ""
    city = normalize_city_name(llm.get("city", "")) or guess_city_from_text(stt_text)
    street = norm(llm.get("street_name", ""))
    if street and not looks_like_street(street):
        street = ""
    if not street:
        street = extract_street_from_text(stt_text) or ""
    if city and looks_like_street(city) and not street:
        street = city
        city = None
    landmark = clean_landmark(llm.get("landmark", ""))
    violation = norm(llm.get("violation_type", "")).replace("اصطفاح", "اصطفاف").replace("إصطفاف", "اصطفاف")
    desc = norm(llm.get("description", ""))
    city_id, vio_id, city_fixed, vio_fixed = map_ids(city, violation)
    if city_fixed:
        city = "دمشق" if city_fixed.strip().lower() in ("ريف دمشق", "ريفدمشق") else city_fixed
    if vio_fixed:
        violation = norm(vio_fixed)
    if not desc:
        parts = []
        if plate:
            parts.append(f"plate {plate}")
        if owner:
            parts.append(f"owner {owner}")
        if city:
            parts.append(f"city {city}")
        if street:
            parts.append(f"street {street}")
        if landmark:
            parts.append(f"landmark {landmark}")
        if violation:
            parts.append(f"violation {violation}")
        desc = " | ".join(parts) if parts else norm(stt_text)
    return {
        "vehicle_plate": plate or "",
        "vehicle_owner": owner,
        "vehicle_model": model or None,
        "vehicle_color": color,
        "street_name": street,
        "landmark": landmark,
        "city_id": city_id,
        "violation_type_id": vio_id,
        "city_name": city,
        "violation_type_name": violation or None,
        "description": desc,
        "occurred_at": None,
    }
