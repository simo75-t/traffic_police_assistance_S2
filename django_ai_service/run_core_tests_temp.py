import os
import sys
import unittest

os.chdir(r"c:\Users\marya\TPA -2-\django_ai_service")
os.environ["DJANGO_SETTINGS_MODULE"] = "config.settings"

loader = unittest.TestLoader()
suite = loader.loadTestsFromName("core.tests")
result = unittest.TextTestRunner(verbosity=2).run(suite)
sys.exit(0 if result.wasSuccessful() else 1)
