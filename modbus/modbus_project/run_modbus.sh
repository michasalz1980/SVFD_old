#!/bin/bash
# Wrapper Script für Modbus Reader

# Debugging aktivieren
set -x

# Arbeitsverzeichnis setzen
cd "/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project"

# Log-Datei
LOGFILE="/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/wrapper.log"

echo "=== Modbus Reader Start - $(date) ===" >> "$LOGFILE"
echo "Current directory: $(pwd)" >> "$LOGFILE"
echo "User: $(whoami)" >> "$LOGFILE"

# Python-Pfade probieren
PYTHON_PATHS=(
    "/opt/plesk/python/3/bin/python3.10"
    "/opt/alt/python311/bin/python3.11" 
    "/usr/bin/python3.10"
    "/usr/bin/python3"
    "python3"
)

for PYTHON_PATH in "${PYTHON_PATHS[@]}"; do
    echo "Trying: $PYTHON_PATH" >> "$LOGFILE"
    if command -v "$PYTHON_PATH" >/dev/null 2>&1; then
        echo "Found: $PYTHON_PATH" >> "$LOGFILE"
        echo "Version: $($PYTHON_PATH --version 2>&1)" >> "$LOGFILE"
        
        # Script ausführen
        "$PYTHON_PATH" "/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/modbus_reader_local.py" "/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/config.ini" >> "$LOGFILE" 2>&1
        EXIT_CODE=$?
        
        echo "Exit code: $EXIT_CODE" >> "$LOGFILE"
        echo "=== End - $(date) ===" >> "$LOGFILE"
        echo "" >> "$LOGFILE"
        
        exit $EXIT_CODE
    else
        echo "Not found: $PYTHON_PATH" >> "$LOGFILE"
    fi
done

echo "ERROR: No working Python found!" >> "$LOGFILE"
echo "=== End with Error - $(date) ===" >> "$LOGFILE"
echo "" >> "$LOGFILE"
exit 1
