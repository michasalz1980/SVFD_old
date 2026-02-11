#!/bin/bash
# CronJob Debug Test Script
echo "=== CronJob Environment Debug ===" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "Date: $(date)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "User: $(whoami)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "PATH: $PATH" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "PWD: $(pwd)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log

echo "Testing Python paths:" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "which python3: $(which python3 2>&1)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "which python: $(which python 2>&1)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log

echo "" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "Testing specific paths:" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log

for python_path in "/usr/bin/python3" "/usr/bin/python3.10" "/opt/plesk/python/3/bin/python3.10" "/opt/alt/python311/bin/python3.11"; do
    if [ -f "$python_path" ]; then
        echo "$python_path: EXISTS - $($python_path --version 2>&1)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
    else
        echo "$python_path: NOT FOUND" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
    fi
done

echo "" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "File permissions:" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "Script: $(ls -la /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/modbus_reader_local.py)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "Config: $(ls -la /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/config.ini)" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log

echo "=== End Debug ===" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
echo "" >> /var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/modbus/modbus_project/debug_output.log
