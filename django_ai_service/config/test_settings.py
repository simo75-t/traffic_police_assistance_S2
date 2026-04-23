"""
Test-specific Django settings to use SQLite instead of MongoDB.
Imports base settings and overrides database configuration.
"""

import os
import sys
from pathlib import Path

# Add project root to path
BASE_DIR = Path(__file__).resolve().parent.parent

# Mock missing packages before importing settings
sys.modules['whisper'] = __import__('unittest.mock', fromlist=['MagicMock']).MagicMock()
sys.modules['cv2'] = __import__('unittest.mock', fromlist=['MagicMock']).MagicMock()

from config.settings import *  # noqa

# Use the default MongoDB/Djongo settings from config.settings
# This keeps the test environment consistent with the production DB dependency.
DEBUG = True
TESTING = True
TEST_RUNNER = "config.test_runner.MongoDBTestRunner"

# Silence logging during tests
import logging
logging.disable(logging.CRITICAL)
