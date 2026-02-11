#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Debug-Script für mod_python-Umgebung
Dateiname: debug_modpython_env.py
"""

print("Content-Type: text/html; charset=utf-8")
print()
print("<html><body><pre>")

import sys
import os

print("=== MOD_PYTHON ENVIRONMENT DEBUG ===")
print(f"Python Version: {sys.version}")
print(f"Python Executable: {sys.executable}")
print(f"Python Prefix: {sys.prefix}")
print(f"Python Site Packages: {[p for p in sys.path if 'site-packages' in p]}")
print()

print("=== SYSTEM PATHS ===")
for i, path in enumerate(sys.path):
    print(f"{i:2d}: {path}")
print()

print("=== ENVIRONMENT VARIABLES ===")
env_vars = ['PATH', 'PYTHONPATH', 'LD_LIBRARY_PATH', 'HOME', 'USER']
for var in env_vars:
    print(f"{var}: {os.environ.get(var, 'NOT SET')}")
print()

print("=== MODULE SEARCH TEST ===")
modules_to_find = [
    'pyModbusTCP', 'pyModbusTCP.client', 
    'pymysql', 'pymysql.cursors',
    'pytz', 'struct', 'time', 'datetime'
]

for module in modules_to_find:
    try:
        mod = __import__(module)
        if hasattr(mod, '__file__'):
            location = mod.__file__
        else:
            location = "built-in"
        print(f"✓ {module}: {location}")
    except ImportError as e:
        print(f"✗ {module}: FEHLT - {e}")

print()

print("=== INSTALLATION COMMANDS ===")
print("Für System-Python (sudo erforderlich):")
print("sudo pip3 install pyModbusTCP PyMySQL pytz")
print()
print("Für User-Installation:")
print("pip3 install --user pyModbusTCP PyMySQL pytz")
print()

print("=== MANUAL MODULE PATH ===")
print("Falls Module in anderem Pfad installiert sind:")
print("sys.path.insert(0, '/pfad/zu/ihren/modulen')")

print("</pre></body></html>")