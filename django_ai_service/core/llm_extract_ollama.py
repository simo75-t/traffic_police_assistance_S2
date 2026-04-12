"""Legacy wrapper for Ollama extraction helpers.

Prefer importing from `core.utils.llm_extract_ollama`.
This wrapper keeps older imports working.
"""

from core.utils.llm_extract_ollama import ollama_extract_fields

__all__ = ["ollama_extract_fields"]
