"""STT service layer.

This file contains the business logic for one STT job.
"""

from typing import Any, Dict

from core.stt.audio import fetch_file, transcribe
from core.stt.extraction import finalize_fields, lmstudio_extract


def handle_job(data: Dict[str, Any]) -> Dict[str, Any]:
    """Process one STT payload and return the final structured result."""
    payload = data.get("payload") or {}
    audio_source = payload.get("local_audio_path") or payload.get("audio_url")
    if not data.get("job_id") or not audio_source:
        raise ValueError("Invalid message: missing job_id or audio source")
    audio_path = fetch_file(audio_source)
    stt_text = transcribe(audio_path)
    llm = lmstudio_extract(stt_text)
    fields = finalize_fields(stt_text, llm)
    return {"text": stt_text, "llm": llm, "fields": fields}
