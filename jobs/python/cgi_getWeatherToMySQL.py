#!/usr/bin/env python3
# -*- coding: utf-8 -*-

print("Content-Type: text/plain; charset=utf-8")
print()

import time
import sys
import os
sys.path.insert(0, './modules')

time.sleep(5)
try:
    import requests
except ImportError:
    print("Fehler: Das Modul 'requests' ist nicht installiert.")
    sys.exit(1)

import json
from datetime import datetime
import pymysql

# Importiere die Konfigurationsdatei
script_dir = os.path.dirname(os.path.abspath(__file__))
config_path = os.path.join(script_dir, 'config.py')

if os.path.exists(config_path):
    sys.path.insert(0, script_dir)
    import config
else:
    print("Fehler: Die Konfigurationsdatei 'config.py' wurde nicht gefunden.")
    sys.exit(1)

# API URL
new_position_lat = '51.085620'
new_position_lng = '7.192630'
app_id = '3263986d38dc6001ed46f3b327841ac4'
url_api = f'https://api.openweathermap.org/data/2.5/weather?lat={new_position_lat}&lon={new_position_lng}&units=metric&APPID={app_id}'

response = requests.get(url_api)
data = response.json()

# MySQL Verbindung herstellen
conn = pymysql.connect(
    host=config.db_host,
    user=config.db_user,
    password=config.db_password,
    database=config.db_database,
    cursorclass=pymysql.cursors.DictCursor
)
cursor = conn.cursor()

# Prepare the SQL query
sql = """
    INSERT INTO ffd_weather (dateTime, temp, feels_like, temp_min, temp_max, pressure, humidity, cloud, rain_1h, rain_3h)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
"""

current_time = datetime.now().strftime('%Y-%m-%d %H:%M:00')

# Extract rain data from the JSON object
rain_1h = 0
rain_3h = 0

if 'rain' in data and '1h' in data['rain']:
    rain_1h = data['rain']['1h']

if 'rain' in data and '3h' in data['rain']:
    rain_3h = data['rain']['3h']

# Insert data into the database
try:
    cursor.execute(sql, (
        current_time,
        data['main']['temp'],
        data['main']['feels_like'],
        data['main']['temp_min'],
        data['main']['temp_max'],
        data['main']['pressure'],
        data['main']['humidity'],
        data['clouds']['all'],
        rain_1h,
        rain_3h
    ))
    conn.commit()
    print("Daten erfolgreich eingefügt.")
except Exception as e:
    conn.rollback()
    print(f"Error: {str(e)}")

# Close the database connection
conn.close()
