"""STT RabbitMQ worker entrypoint.

This is the preferred simple entrypoint for the STT worker.
It delegates execution to the STT consumer package.
"""

import sys
from pathlib import Path

if __package__ in (None, ""):
    sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core.stt.consumer import main


if __name__ == "__main__":
    main()
