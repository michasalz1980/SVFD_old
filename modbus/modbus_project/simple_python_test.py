#!/usr/bin/env python3
import sys
import os
from datetime import datetime

# Log in Datei schreiben
with open("/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/python_test.log", "a") as f:
    f.write(f"=== Python Test - {datetime.now()} ===\n")
    f.write(f"Python Version: {sys.version}\n")
    f.write(f"Python Executable: {sys.executable}\n")
    f.write(f"Current Directory: {os.getcwd()}\n")
    f.write(f"Script Path: {__file__}\n")
    f.write("Python Test successful!\n")
    f.write("\n")

print("Python test completed - check python_test.log")
