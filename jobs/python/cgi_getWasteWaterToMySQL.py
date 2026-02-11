#!/usr/bin/env python3
# -*- coding: utf-8 -*-

print("Content-Type: text/plain; charset=utf-8")
print()

import time
import sys
import os
import struct
import traceback
sys.path.insert(0, './modules')

time.sleep(1)

try:
    from pyModbusTCP.client import ModbusClient
    import pymysql.cursors
    from datetime import datetime
    from pytz import timezone
except ImportError as e:
    print(f"Fehler beim Importieren der Module: {e}")
    sys.exit(1)

# Konfiguration laden
script_dir = os.path.dirname(os.path.abspath(__file__))
config_path = os.path.join(script_dir, 'config.py')

if os.path.exists(config_path):
    sys.path.insert(0, script_dir)
    try:
        import config
    except ImportError as e:
        print(f"Fehler beim Importieren der Konfiguration: {e}")
        sys.exit(1)
else:
    print("Fehler: Die Konfigurationsdatei 'config.py' wurde nicht gefunden.")
    sys.exit(1)

de_timezone = timezone('Europe/Berlin')

def convert_float32_from_modbus(response):
    """Konvertiert 2 Modbus-Register zu IEEE 754 Float32 (Big-Endian)"""
    try:
        if len(response) != 2:
            raise ValueError(f"Erwarte 2 Register, erhalten: {len(response)}")
        
        float_value = struct.unpack('>f', struct.pack('>HH', response[0], response[1]))[0]
        return float_value
    except Exception as e:
        raise ValueError(f"Float32-Konvertierung fehlgeschlagen: {e}")

def main():
    client = None
    connection = None
    
    try:
        # Modbus-Client erstellen
        client = ModbusClient(
            host=config.modbus_server_ip,
            port=config.modbus_server_port,
            unit_id=config.modbus_unit_id,
            auto_open=True,
            debug=False,
            timeout=10.0
        )
        
        # Datenbank-Verbindung
        connection = pymysql.connect(
            host=config.db_host,
            user=config.db_user,
            password=config.db_password,
            database=config.db_database,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=False
        )
        
        with connection.cursor() as cursor:
            data_to_insert = {}
            modbus_status = "OK"
            error_message = ""
            
            # Register lesen
            for register in config.abwasser_register_configs:
                try:
                    if register["type"] == "float32":
                        response = client.read_holding_registers(register["address"], 2)
                        
                        if response is not None:
                            float_value = convert_float32_from_modbus(response)
                            
                            # Skalierung anwenden falls definiert
                            if "scale_factor" in register:
                                float_value = float_value * register["scale_factor"]
                            
                            # Negativwerte behandeln
                            if register["column"] == "durchflussrate" and float_value < 0:
                                float_value = abs(float_value)
                            
                            data_to_insert[register["column"]] = float_value
                        else:
                            raise ConnectionError(f"Keine Antwort für Register {register['address']}")
                    
                    elif register["type"] == "single":
                        response = client.read_holding_registers(register["address"], 1)
                        
                        if response is not None:
                            raw_value = response[0]
                            print(f"DEBUG: Wasserstand - Adresse {register['address']}, Rohwert: {raw_value}")
                            scaled_value = raw_value / register.get("scale", 1)
                            
                            # Negative Werte erlauben falls konfiguriert
                            if not register.get("allow_negative", False) and scaled_value < 0:
                                scaled_value = abs(scaled_value)
                            
                            data_to_insert[register["column"]] = scaled_value
                        else:
                            raise ConnectionError(f"Keine Antwort für Register {register['address']}")
                        
                except Exception as e:
                    modbus_status = "ERROR"
                    error_message = f"Fehler bei {register['column']}: {str(e)}"
                    break
            
            # Verarbeitung nur wenn alle Register erfolgreich gelesen wurden
            if modbus_status == "OK" and len(data_to_insert) == len(config.abwasser_register_configs):
                
                # Consumption berechnen
                try:
                    cursor.execute("SELECT totalizer FROM abwasser_messwerte ORDER BY id DESC LIMIT 1")
                    last_counter = cursor.fetchone()
                    if last_counter and last_counter["totalizer"] is not None:
                        last_total = float(last_counter["totalizer"])
                        current_total = data_to_insert["totalizer"]
                        consumption = max(0.0, current_total - last_total)
                    else:
                        consumption = 0.0
                    data_to_insert["consumption"] = consumption
                except Exception as e:
                    data_to_insert["consumption"] = 0.0
                
                # Zeitstempel - KORREKTUR: Sekunden nur für timestamp auf :00 setzen
                timestamp_de = datetime.now(de_timezone).replace(microsecond=0, second=0)
                created_at_de = datetime.now(de_timezone).replace(microsecond=0)
                
                # In Datenbank speichern
                sql = """
                INSERT INTO abwasser_messwerte (
                    wasserstand,
                    durchflussrate,
                    totalizer,
                    sensor_strom,
                    consumption,
                    modbus_status,
                    error_message,
                    timestamp,
                    created_at
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
                """
                
                cursor.execute(sql, (
                    data_to_insert["wasserstand"],
                    data_to_insert["durchflussrate"],
                    data_to_insert["totalizer"],
                    data_to_insert["sensor_strom"],
                    data_to_insert["consumption"],
                    modbus_status,
                    error_message,
                    timestamp_de,
                    created_at_de
                ))
                
                connection.commit()
                print("Daten erfolgreich eingefügt.")
                print(f"Wasserstand: {data_to_insert['wasserstand']} cm")
                print(f"Durchfluss: {data_to_insert['durchflussrate']} l/s") 
                print(f"Totalizer: {data_to_insert['totalizer']} m³")
                print(f"Sensor: {data_to_insert['sensor_strom']} mA")
                print(f"Verbrauch: {data_to_insert['consumption']} m³")
                
            else:
                # Fehler-Eintrag - KORREKTUR: Sekunden nur für timestamp auf :00 setzen
                timestamp_de = datetime.now(de_timezone).replace(microsecond=0, second=0)
                created_at_de = datetime.now(de_timezone).replace(microsecond=0)
                sql_error = """
                INSERT INTO abwasser_messwerte (
                    modbus_status,
                    error_message,
                    timestamp,
                    created_at
                )
                VALUES (%s, %s, %s, %s)
                """
                cursor.execute(sql_error, (modbus_status, error_message, timestamp_de, created_at_de))
                connection.commit()
                print(f"Fehler aufgetreten: {error_message}")
                
    except Exception as e:
        error_msg = f"Unerwarteter Fehler: {str(e)}"
        print(error_msg)
        
        # Fehler in DB loggen - KORREKTUR: Sekunden nur für timestamp auf :00 setzen
        if connection:
            try:
                with connection.cursor() as cursor:
                    timestamp_de = datetime.now(de_timezone).replace(microsecond=0, second=0)
                    created_at_de = datetime.now(de_timezone).replace(microsecond=0)
                    sql_error = """
                    INSERT INTO abwasser_messwerte (
                        modbus_status,
                        error_message,
                        timestamp,
                        created_at
                    )
                    VALUES (%s, %s, %s, %s)
                    """
                    cursor.execute(sql_error, ("ERROR", error_msg, timestamp_de, created_at_de))
                    connection.commit()
            except:
                pass
                
    finally:
        if client:
            client.close()
        if connection:
            connection.close()

# Script ausführen
main()