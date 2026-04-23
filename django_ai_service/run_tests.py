#!/usr/bin/env python
import os
import sys
import django

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from django.core.management import call_command
call_command('test', 'core', verbosity=2)
