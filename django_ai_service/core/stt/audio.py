"""STT audio helpers.

This file handles audio download, conversion, and transcription.
"""

import os
import tempfile
from pathlib import Path

import requests

from core.stt.config import WHISPER_LANGUAGE, log, whisper_model
from core.stt.normalization import norm, words_to_digits


def ffmpeg_to_wav16k_mono(input_path: str) -> str:
    """Convert one audio file to wav 16k mono when ffmpeg exists."""
    out_path = tempfile.NamedTemporaryFile(delete=False, suffix=".wav").name
    cmd = f'ffmpeg -y -i "{input_path}" -ac 1 -ar 16000 -c:a pcm_s16le "{out_path}"'
    rc = os.system(cmd)
    return out_path if rc == 0 else input_path


def fetch_file(path_or_url: str) -> str:
    """Resolve one local audio path or download one remote audio file."""
    if not path_or_url:
        raise ValueError("Missing audio path")
    local_path = Path(path_or_url)
    if local_path.exists():
        return str(local_path)
    response = requests.get(path_or_url, timeout=30)
    response.raise_for_status()
    tmp = tempfile.NamedTemporaryFile(delete=False, suffix=".wav")
    tmp.write(response.content)
    tmp.close()
    return tmp.name


def transcribe(audio_path: str) -> str:
    """Transcribe one audio file with Whisper and normalize its text."""
    audio_clean = ffmpeg_to_wav16k_mono(audio_path)
    result = whisper_model.transcribe(audio_clean, language=WHISPER_LANGUAGE, fp16=False)
    text = norm(result.get("text", "") or "")
    text = norm(words_to_digits(text))
    log.info("STT TEXT: %s", text)
    return text
