"""STT extraction helpers.

This file handles LLM extraction and field repair logic.
"""

import json
import re
from typing import Any, Dict, Optional

import requests

from core.stt.config import (
    LLM_CHAT_URL,
    LLM_API_KEY,
    LLM_MODEL,
    LLM_PROVIDER,
    LLM_TIMEOUT,
    OPENROUTER_REFERER,
    OPENROUTER_TITLE,
    log,
)
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


EXPECTED_LLM_FIELDS = (
    "vehicle_plate",
    "vehicle_owner",
    "vehicle_model",
    "vehicle_color",
    "city_id",
    "city",
    "city_name",
    "street_name",
    "landmark",
    "violation_type_id",
    "violation_type",
    "violation_type_name",
    "description",
    "occurred_at",
)


def first_json_object(s: str) -> str:
    """Extract the first balanced JSON object from model output."""
    s = (s or "").strip()
    if not s:
        return ""
    if s.startswith("```"):
        s = re.sub(r"^```(?:json)?\s*", "", s, flags=re.IGNORECASE)
        s = re.sub(r"\s*```$", "", s)
    start = s.find("{")
    if start == -1:
        return ""

    depth = 0
    in_string = False
    escaped = False
    for index in range(start, len(s)):
        char = s[index]
        if in_string:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == '"':
                in_string = False
            continue

        if char == '"':
            in_string = True
        elif char == "{":
            depth += 1
        elif char == "}":
            depth -= 1
            if depth == 0:
                return s[start : index + 1].strip()

    return ""


def _extract_message_content(data: Dict[str, Any]) -> str:
    choices = data.get("choices") or []
    if not choices:
        return ""

    message = choices[0].get("message") or {}
    content = message.get("content")
    if isinstance(content, str):
        return content
    if isinstance(content, list):
        parts = []
        for item in content:
            if isinstance(item, dict):
                text = item.get("text")
                if isinstance(text, str) and text.strip():
                    parts.append(text)
        return "\n".join(parts)
    return ""


def _sanitize_extracted_fields(payload: Dict[str, Any]) -> Dict[str, str]:
    sanitized: Dict[str, str] = {}
    for key in EXPECTED_LLM_FIELDS:
        value = payload.get(key, "")
        if value is None:
            sanitized[key] = ""
        elif isinstance(value, str):
            sanitized[key] = value.strip()
        else:
            sanitized[key] = str(value).strip()
    return sanitized


def lmstudio_extract(stt_text: str) -> Dict[str, Any]:
    """Ask the configured LLM provider to convert transcript text into JSON."""
    if not LLM_API_KEY:
        log.warning(
            "STT semantic extraction skipped: missing API key. "
            "Set OPENAI_API_KEY or OPENROUTER_API_KEY or QWEN_API_KEY in the environment."
        )
        return {}

    log.info(
        "STT semantic extraction provider=%s model=%s",
        LLM_PROVIDER,
        LLM_MODEL,
    )

    system_prompt = """
You extract structured data from Arabic traffic violation speech transcripts.
Return exactly one JSON object only.
Do not return markdown.
Do not return prose.
Do not explain the answer.
If a field is unknown, return an empty string.
""".strip()

    user_prompt = f"""
Transcript:
{stt_text}

Return exactly one JSON object with this schema:
{{
  "vehicle_plate": "",
  "vehicle_owner": "",
  "vehicle_model": "",
  "vehicle_color": "",
  "city_id": "",
  "city_name": "",
  "street_name": "",
  "landmark": "",
  "violation_type_id": "",
  "violation_type_name": "",
  "description": "",
  "occurred_at": ""
}}

Rules:
1. Output JSON only.
2. No markdown fences.
3. No explanatory text before or after JSON.
4. vehicle_plate must contain digits only.
5. city_name must contain one city or governorate only.
6. violation_type_name must contain a short violation label only.
7. If ids are unknown, keep city_id and violation_type_id as empty strings.
""".strip()

    payload = {
        "model": LLM_MODEL,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
        "temperature": 0.0,
        "top_p": 1.0,
        "max_tokens": 260,
    }
    headers = {
        "Authorization": f"Bearer {LLM_API_KEY}",
        "Content-Type": "application/json",
    }
    if LLM_PROVIDER == "openrouter" and OPENROUTER_REFERER:
        headers["HTTP-Referer"] = OPENROUTER_REFERER
    if LLM_PROVIDER == "openrouter" and OPENROUTER_TITLE:
        headers["X-Title"] = OPENROUTER_TITLE

    try:
        response = requests.post(
            LLM_CHAT_URL,
            json=payload,
            headers=headers,
            timeout=LLM_TIMEOUT,
        )
        if not response.ok:
            log.warning("STT LLM HTTP %s: %s", response.status_code, response.text[:500])
            return {}

        data = response.json()
        content = _extract_message_content(data).strip()
        log.info("STT raw LLM output: %s", content)
        cleaned = first_json_object(content)
        if not cleaned:
            log.warning("STT LLM returned no JSON object")
            return {}
        parsed = json.loads(cleaned)
        if not isinstance(parsed, dict):
            log.warning("STT LLM output did not parse to a JSON object")
            return {}

        sanitized = _sanitize_extracted_fields(parsed)
        log.info("STT parsed JSON fields: %s", sanitized)
        return sanitized
    except requests.RequestException as exc:
        log.warning("STT semantic extraction request failed: %s", exc)
        return {}
    except Exception as exc:
        log.warning("STT semantic extraction failed: %s", exc)
        return {}


def finalize_fields(
    stt_text: str,
    llm: Dict[str, Any],
    auth_header: Optional[str] = None,
) -> Dict[str, Any]:
    """Merge LLM output with rule-based repair and id mapping."""
    log.info("STT text: %s", stt_text)
    llm = _sanitize_extracted_fields(llm) if isinstance(llm, dict) else {}

    plate = normalize_plate(llm.get("vehicle_plate", "")) or best_plate_from_text(stt_text)
    owner = clean_owner(llm.get("vehicle_owner", ""))
    model = norm(llm.get("vehicle_model", ""))
    color = normalize_color(llm.get("vehicle_color", "")) or normalize_color(stt_text)
    if model and normalize_color(model) and not color:
        color = normalize_color(model)
        model = ""

    city = (
        normalize_city_name(llm.get("city_name", ""))
        or normalize_city_name(llm.get("city", ""))
        or guess_city_from_text(stt_text)
    )
    street = norm(llm.get("street_name", ""))
    if street and not looks_like_street(street):
        street = ""
    if not street:
        street = extract_street_from_text(stt_text) or ""
    if city and looks_like_street(city) and not street:
        street = city
        city = None

    landmark = clean_landmark(llm.get("landmark", ""))
    violation = (
        norm(llm.get("violation_type_name", "") or llm.get("violation_type", ""))
        .replace("ط§طµط·ظپط§ط­", "ط§طµط·ظپط§ظپ")
        .replace("ط¥طµط·ظپط§ظپ", "ط§طµط·ظپط§ظپ")
    )
    desc = norm(llm.get("description", ""))
    occurred_at = norm(llm.get("occurred_at", ""))

    city_id, vio_id, city_fixed, vio_fixed = map_ids(
        city,
        violation,
        auth_header=auth_header,
    )
    city_id = city_id or llm.get("city_id") or None
    vio_id = vio_id or llm.get("violation_type_id") or None
    if city and not city_id:
        log.warning('STT city mapping failed for "%s"', city)
    if violation and not vio_id:
        log.warning('STT violation type mapping failed for "%s"', violation)

    if city_fixed:
        city = "دمشق" if city_fixed.strip().lower() in ("ريف دمشق", "ريفدمشق") else city_fixed
    if vio_fixed:
        violation = norm(vio_fixed)

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

    if desc:
        lower_desc = desc.lower()
        missing_parts = [part for part in parts if part.lower() not in lower_desc]
        if missing_parts:
            desc = f"{desc} | {' | '.join(missing_parts)}"
    else:
        desc = " | ".join(parts) if parts else norm(stt_text)

    result = {
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
        "occurred_at": occurred_at or None,
    }
    log.info("STT finalized fields: %s", result)
    return result
