"""Legacy STT worker entrypoint.

Prefer using `core.stt_worker`.
This file stays as a compatibility wrapper for older imports.
"""

import sys
from pathlib import Path

if __package__ in (None, ""):
    sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core.stt_worker import main


if __name__ == "__main__":
    main()
