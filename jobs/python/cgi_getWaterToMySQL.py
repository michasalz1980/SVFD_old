#!/usr/bin/env python3
# -*- coding: utf-8 -*-

print("Content-Type: text/plain; charset=utf-8")
print()

import sys
import os
sys.path.insert(0, './modules')

from pyModbusTCP.client import ModbusClient
import struct
import pymysql.cursors
from datetime import datetime
from pytz import timezone

# Importiere die Konfigurationsdatei
script_dir = os.path.dirname(os.path.abspath(__file__))
config_path = os.path.join(script_dir, 'config.py')

if os.path.exists(config_path):
    sys.path.insert(0, script_dir)
    import config
else:
    print("Fehler: Die Konfigurationsdatei 'config.py' wurde nicht gefunden.")
    sys.exit(1)

# Erstelle ein Objekt für die deutsche Zeitzone
de_timezone = timezone('Europe/Berlin')

# Modbus Server Konfiguration
server_ip = "iykjlt0jy435sqad.myfritz.net"
server_port = 8502
unit_id = 1

# Register Konfiguration
register_configs = [
    {"address": 20, "length": 4, "column": "counter"}
]

# Verbindung zum Modbus Server herstellen
client = ModbusClient(host=server_ip, port=server_port, unit_id=unit_id, auto_open=True, debug=False)

# MySQL Verbindung herstellen
connection = pymysql.connect(
    host=config.db_host,
    user=config.db_user,
    password=config.db_password,
    database=config.db_database,
    cursorclass=pymysql.cursors.DictCursor
)

try:
    with connection.cursor() as cursor:
        data_to_insert = {}
        
        for config in register_configs:
            response = client.read_holding_registers(config["address"], config["length"])
            
            if response:
                # Daten erfolgreich abgerufen
                uint_data = struct.unpack('>Q', struct.pack('>HHHH', *response))[0]
                data_to_insert[config["column"]] = uint_data
                
            else:
                print(f"Fehler beim Abrufen der Daten für {config['column']}.")
                connection.rollback()
                break
        
        # Berechne consumption
        cursor.execute("SELECT counter FROM ffd_frischwasser ORDER BY id DESC LIMIT 1")
        last_counter = cursor.fetchone()
        if last_counter:
            consumption = data_to_insert["counter"] - last_counter["counter"]
        else:
            consumption = 0
        
        data_to_insert["consumption"] = consumption
        # Erstelle ein datetime-Objekt mit der deutschen Zeitzone
        now_de = datetime.now(de_timezone).replace(second=0)
        data_to_insert["datetime"] = now_de
        
        # Insert Daten in die Tabelle
        source = "sensor"
        sql = "INSERT INTO ffd_frischwasser (datetime, counter, consumption, source) VALUES (%s, %s, %s, %s)"
        cursor.execute(sql, (data_to_insert["datetime"], data_to_insert["counter"], data_to_insert["consumption"], source))
        connection.commit()
        print("Content-Type: text/plain") # Set content type to plain text
        print()  # Print a blank line to indicate the end of headers
        print("Daten erfolgreich eingefügt.")
                
finally:
    connection.close()