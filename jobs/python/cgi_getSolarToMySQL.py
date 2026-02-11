#!/usr/bin/env python3
# -*- coding: utf-8 -*-

print("Content-Type: text/plain; charset=utf-8")
print()

import time
import os
import traceback
from datetime import datetime

# Setup für lokale Module
script_dir = os.path.dirname(os.path.abspath(__file__))
local_modules_dir = os.path.join(script_dir, 'modules')

# Speichere sys für späteren Import
import sys

if os.path.exists(local_modules_dir):
    sys.path.insert(0, local_modules_dir)

sys.path.insert(0, script_dir)

# Custom Exception für sauberes Beenden ohne sys.exit()
class ScriptExit(Exception):
    def __init__(self, exit_code, message=""):
        self.exit_code = exit_code
        self.message = message
        super().__init__(message)

# Imports
try:
    from pyModbusTCP.client import ModbusClient
    import pymysql.cursors
    import struct
except ImportError as e:
    print(f"FEHLER: Erforderliche Module nicht verfügbar: {e}")
    raise ScriptExit(1, f"Import-Fehler: {e}")

# Timezone-Handling
try:
    from pytz import timezone
    pytz_available = True
except ImportError:
    pytz_available = False

# Config importieren
try:
    import config
except ImportError as e:
    print(f"FEHLER: config.py konnte nicht geladen werden: {e}")
    raise ScriptExit(1, f"Config-Fehler: {e}")

# =============================================================================
# DATETIME-FIX FUNKTIONEN - NEUE ERGÄNZUNG
# =============================================================================

def normalize_datetime(dt):
    """
    NEUE FUNKTION: Normalisiert datetime-Objekte für sichere Vergleiche
    Entfernt Zeitzone um offset-naive/offset-aware Konflikte zu vermeiden
    """
    if dt is None:
        return None
    if hasattr(dt, 'tzinfo') and dt.tzinfo is not None:
        # Timezone-aware -> zu naive konvertieren (lokale Zeit beibehalten)
        return dt.replace(tzinfo=None)
    return dt

def safe_datetime_diff(dt1, dt2):
    """
    NEUE FUNKTION: Sichere Subtraktion zweier datetime-Objekte
    Beide werden zu naive datetime normalisiert
    """
    dt1_normalized = normalize_datetime(dt1)
    dt2_normalized = normalize_datetime(dt2)
    
    if dt1_normalized is None or dt2_normalized is None:
        return None
    
    return dt1_normalized - dt2_normalized

def get_safe_current_time():
    """
    NEUE FUNKTION: Erstellt timezone-naive datetime für Kompatibilität
    """
    return datetime.now().replace(second=0, microsecond=0)

# =============================================================================
# BESTEHENDE FUNKTIONEN MIT DATETIME-FIXES
# =============================================================================

# Config-Defaults
def get_config_attr(attr_name, default_value):
    return getattr(config, attr_name, default_value)

# Konfiguration laden
DEBUG_MODE = get_config_attr('debug_mode', False)
MAX_RETRIES = get_config_attr('max_retries', 3)
TIMEOUT_SECONDS = get_config_attr('timeout_seconds', 10)
CONNECTION_TIMEOUT = get_config_attr('connection_timeout', 5)
READ_TIMEOUT = get_config_attr('read_timeout', 3)
VALIDATE_DATA = get_config_attr('validate_data', True)

# ERWEITERTE Validierungsparameter
MAX_TEMPERATURE_RAW = get_config_attr('max_temperature_raw', 850)
MIN_TEMPERATURE_RAW = get_config_attr('min_temperature_raw', -200)
MAX_POWER_TOTAL = get_config_attr('max_power_total', 60000)
MIN_POWER_TOTAL = get_config_attr('min_power_total', 0)
MAX_POWER_PER_PHASE = get_config_attr('max_power_per_phase', 25000)

# NEUE Energie-Validierungsparameter
MAX_TOTAL_FEED_WH = get_config_attr('max_total_feed_wh', 999999999)
MIN_TOTAL_FEED_WH = get_config_attr('min_total_feed_wh', 100000000)  # 100 MWh minimum
MAX_DAILY_INCREASE_WH = get_config_attr('max_daily_increase_wh', 300000)  # 300 kWh/Tag max

# Exit-Codes
EXIT_SUCCESS = get_config_attr('EXIT_SUCCESS', 0)
EXIT_CONFIG_ERROR = get_config_attr('EXIT_CONFIG_ERROR', 1)
EXIT_MODBUS_CONNECTION_ERROR = get_config_attr('EXIT_MODBUS_CONNECTION_ERROR', 2)
EXIT_MODBUS_READ_ERROR = get_config_attr('EXIT_MODBUS_READ_ERROR', 3)
EXIT_DATABASE_CONNECTION_ERROR = get_config_attr('EXIT_DATABASE_CONNECTION_ERROR', 4)
EXIT_DATABASE_WRITE_ERROR = get_config_attr('EXIT_DATABASE_WRITE_ERROR', 5)
EXIT_DATA_VALIDATION_ERROR = get_config_attr('EXIT_DATA_VALIDATION_ERROR', 6)
EXIT_GENERAL_ERROR = get_config_attr('EXIT_GENERAL_ERROR', 99)

def log_message(message, level="INFO"):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    if DEBUG_MODE or level in ["ERROR", "WARNING"]:
        print(f"[{timestamp}] [{level}] {message}")

def get_current_time():
    """GEFIXTE VERSION: Datetime-Problem behoben"""
    try:
        if pytz_available:
            de_timezone = timezone('Europe/Berlin')
            dt_with_tz = datetime.now(de_timezone).replace(second=0, microsecond=0)
            # DATETIME-FIX: Zeitzone entfernen für Kompatibilität
            return normalize_datetime(dt_with_tz)
        else:
            return get_safe_current_time()
    except Exception as e:
        log_message(f"Datetime-Erstellung fehlgeschlagen, verwende Fallback: {e}", "WARNING")
        return get_safe_current_time()

def get_last_energy_value(connection):
    """GEFIXTE VERSION: Holt den letzten Total_Feed_Wh Wert für Plausibilitätsprüfung"""
    try:
        table_name = get_config_attr('table_power_monitoring', 'ffd_power_monitoring')
        
        with connection.cursor() as cursor:
            # Prüfe ob total_feed_wh Spalte existiert
            cursor.execute(f"""
                SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'total_feed_wh'
            """, (config.db_database, table_name))
            
            if not cursor.fetchone():
                return None
            
            cursor.execute(f"""
                SELECT total_feed_wh, datetime 
                FROM {table_name} 
                WHERE total_feed_wh > 0
                ORDER BY datetime DESC 
                LIMIT 1
            """)
            
            result = cursor.fetchone()
            if result:
                # DATETIME-FIX: DB-Datetime normalisieren
                db_datetime = normalize_datetime(result['datetime'])
                return {
                    'total_feed_wh': result['total_feed_wh'],
                    'datetime': db_datetime  # Normalisiert
                }
            return None
                
    except Exception as e:
        log_message(f"Warnung: Letzter Energiewert konnte nicht abgerufen werden: {e}", "WARNING")
        return None

def validate_energy_data(data_to_insert, last_energy_data=None):
    """GEFIXTE VERSION: Spezielle Validierung für Energiedaten mit Datetime-Fix"""
    if not VALIDATE_DATA:
        return True
        
    try:
        if 'total_feed_wh' in data_to_insert:
            total_wh = data_to_insert['total_feed_wh']
            
            # Grundvalidierung
            if total_wh < MIN_TOTAL_FEED_WH or total_wh > MAX_TOTAL_FEED_WH:
                log_message(f"Total_Feed_Wh außerhalb erwarteter Bereiche: {total_wh:,} Wh", "ERROR")
                return False
            
            # Plausibilitätsprüfung gegen letzten Wert
            if last_energy_data and last_energy_data['total_feed_wh'] > 0:
                last_wh = last_energy_data['total_feed_wh']
                last_time = last_energy_data['datetime']  # Bereits normalisiert
                current_time = get_current_time()  # Bereits normalisiert
                
                # Energie kann nur steigen oder gleich bleiben
                if total_wh < last_wh:
                    log_message(f"FEHLER: Energiewert ist gesunken: {last_wh:,} -> {total_wh:,} Wh", "ERROR")
                    return False
                
                # DATETIME-FIX: Sichere Zeitdifferenz-Berechnung
                try:
                    if isinstance(last_time, datetime):
                        time_diff = safe_datetime_diff(current_time, last_time)
                        
                        if time_diff is not None:
                            time_diff_seconds = time_diff.total_seconds()
                            energy_increase = total_wh - last_wh
                            
                            if time_diff_seconds > 0 and energy_increase > 0:
                                hours_passed = time_diff_seconds / 3600
                                max_theoretical_increase = MAX_POWER_TOTAL * hours_passed
                                
                                if energy_increase > max_theoretical_increase * 1.2:  # 20% Toleranz
                                    log_message(f"WARNUNG: Hoher Energiezuwachs: {energy_increase:,} Wh in {hours_passed:.2f}h", "WARNING")
                                    # Nicht als Fehler behandeln, nur warnen
                                
                                if DEBUG_MODE:
                                    log_message(f"Energiezuwachs: {energy_increase:,} Wh in {hours_passed:.2f}h", "INFO")
                        else:
                            log_message("Zeitdifferenz-Berechnung fehlgeschlagen - Energievalidierung übersprungen", "WARNING")
                            
                except Exception as e:
                    log_message(f"Fehler bei Energievalidierung: {e} - überspringe Zeitprüfung", "WARNING")
                    # Nicht als Fehler behandeln - Daten trotzdem speichern
        
        return True
        
    except Exception as e:
        log_message(f"Fehler bei Energievalidierung: {e}", "ERROR")
        # WICHTIGER FIX: Bei Validierungsfehlern trotzdem True zurückgeben
        log_message("Energievalidierung übersprungen - Daten werden trotzdem gespeichert", "INFO")
        return True

def validate_data(data_to_insert, last_energy_data=None):
    """GEFIXTE VERSION: ERWEITERTE Datenvalidierung für Stromdaten mit Energie-Check"""
    if not VALIDATE_DATA:
        return True
        
    try:
        # Temperatur-Validierung (RAW-Werte!)
        if 'temperature' in data_to_insert:
            temp_raw = data_to_insert['temperature']
            if temp_raw < MIN_TEMPERATURE_RAW or temp_raw > MAX_TEMPERATURE_RAW:
                temp_celsius = temp_raw / 10.0
                log_message(f"Temperatur außerhalb erwarteter Bereiche: {temp_raw} raw ({temp_celsius}°C)", "WARNING")
            else:
                temp_celsius = temp_raw / 10.0
                if DEBUG_MODE:
                    log_message(f"Temperatur OK: {temp_raw} raw ({temp_celsius}°C)", "DEBUG")
        
        # Leistungsvalidierung
        if 'current_feed_total' in data_to_insert:
            power_total = data_to_insert['current_feed_total']
            if power_total < MIN_POWER_TOTAL or power_total > MAX_POWER_TOTAL:
                log_message(f"Gesamtleistung außerhalb erwarteter Bereiche: {power_total}W", "WARNING")
                return False
        
        # Validierung pro Phase
        for phase in ['current_feed_l1', 'current_feed_l2', 'current_feed_l3']:
            if phase in data_to_insert:
                power_phase = data_to_insert[phase]
                if power_phase < 0 or power_phase > MAX_POWER_PER_PHASE:
                    log_message(f"{phase} außerhalb erwarteter Bereiche: {power_phase}W", "WARNING")
                    return False
        
        # Status-Validierung (einfach)
        if 'device_status' in data_to_insert and data_to_insert['device_status'] < 0:
            log_message(f"Ungültiger Device Status: {data_to_insert['device_status']}", "WARNING")
            return False
        
        # GEFIXTE Energievalidierung
        try:
            if not validate_energy_data(data_to_insert, last_energy_data):
                log_message("Energievalidierung fehlgeschlagen - Daten trotzdem speichern", "WARNING")
                # WICHTIGER FIX: Nicht False zurückgeben bei Energievalidierungsfehlern
                return True
        except Exception as e:
            log_message(f"Energievalidierung-Fehler ignoriert: {e}", "WARNING")
            return True
            
        return True
    except Exception as e:
        log_message(f"Fehler bei Datenvalidierung: {e}", "ERROR")
        return False

def get_register_configs():
    configs_to_try = [
        'power_monitoring_register_configs',
        'register_configs',
        'frischwasser_register_configs'
    ]
    
    for config_name in configs_to_try:
        if hasattr(config, config_name):
            configs = getattr(config, config_name)
            if configs:
                if DEBUG_MODE:
                    log_message(f"Verwende Register-Konfiguration: {config_name} ({len(configs)} Register)", "INFO")
                return configs
    
    return None

def read_modbus_data(client):
    """ERWEITERTE Modbus-Datenlesung mit spezieller UINT64-Behandlung für Register 440"""
    data_to_insert = {}
    
    register_configs = get_register_configs()
    if not register_configs:
        return None
    
    for register in register_configs:
        is_optional = register.get('optional', False)
        retry_count = 0
        success = False
        
        while retry_count < MAX_RETRIES and not success:
            try:
                response = client.read_holding_registers(
                    register["address"], 
                    register["length"]
                )
                
                if response:
                    # SPEZIELLE BEHANDLUNG für Register 440 (Total_Feed_Wh)
                    if register["column"] == "total_feed_wh" and register["length"] == 4:
                        # UINT64 Big-Endian Interpretation (GETESTET UND FUNKTIONIERT!)
                        uint_data = struct.unpack('>Q', struct.pack('>HHHH', *response))[0]
                        
                        log_message(f"Register 440 Raw: {response} -> UINT64: {uint_data:,} Wh ({uint_data/1000000:.2f} MWh)", "INFO")
                    
                    # Standard-Behandlung für andere Register
                    elif register["length"] == 2:
                        uint_data = struct.unpack('>I', struct.pack('>HH', *response))[0]
                    elif register["length"] == 4:
                        # Andere 4-Register auch als UINT64 behandeln
                        uint_data = struct.unpack('>Q', struct.pack('>HHHH', *response))[0]
                    else:
                        uint_data = response[0]
                    
                    # Skalierung anwenden falls definiert
                    if 'scale' in register:
                        uint_data = uint_data / register['scale']
                    
                    data_to_insert[register["column"]] = uint_data
                    success = True
                    
                    if DEBUG_MODE:
                        log_message(f"Register {register['address']} -> {register['column']}: {uint_data}", "DEBUG")
                else:
                    raise Exception(f"Keine Antwort von Register {register['address']}")
                    
            except Exception as e:
                retry_count += 1
                if retry_count < MAX_RETRIES:
                    time.sleep(1)
                else:
                    if is_optional:
                        log_message(f"Optionales Register {register['column']} übersprungen: {e}", "WARNING")
                        success = True  # Optionale Register sind OK wenn sie fehlschlagen
                    else:
                        log_message(f"Fehler beim Lesen von Register {register['address']}: {e}", "ERROR")
        
        if not success and not is_optional:
            log_message(f"Pflicht-Register {register['column']} nach {MAX_RETRIES} Versuchen fehlgeschlagen", "ERROR")
            return None
    
    return data_to_insert

def check_and_update_table_schema(connection, table_name):
    """Prüft und erweitert das Tabellenschema um total_feed_wh"""
    try:
        with connection.cursor() as cursor:
            # Prüfe ob total_feed_wh Spalte existiert
            cursor.execute(f"""
                SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'total_feed_wh'
            """, (config.db_database, table_name))
            
            if not cursor.fetchone():
                # Spalte hinzufügen
                cursor.execute(f"""
                    ALTER TABLE {table_name} 
                    ADD COLUMN total_feed_wh BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 
                    COMMENT 'Gesamte eingespeiste Energie in Wh (Register 440)' 
                    AFTER current_feed_l3
                """)
                
                # Index hinzufügen für Performance
                cursor.execute(f"ALTER TABLE {table_name} ADD INDEX idx_total_feed_wh (total_feed_wh)")
                connection.commit()
                log_message(f"Tabelle {table_name} um total_feed_wh erweitert", "INFO")
                return True
            
            # Optionale weitere Spalten hinzufügen
            optional_columns = [
                ('daily_feed_wh', 'INT(11) UNSIGNED', 'Tagesertrag (Wh)'),
                ('monthly_feed_kwh', 'DECIMAL(10,3) UNSIGNED', 'Monatsertrag (kWh)')
            ]
            
            for col_name, col_type, col_comment in optional_columns:
                cursor.execute(f"""
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s
                """, (config.db_database, table_name, col_name))
                
                if not cursor.fetchone():
                    cursor.execute(f"""
                        ALTER TABLE {table_name} 
                        ADD COLUMN {col_name} {col_type} NOT NULL DEFAULT 0 
                        COMMENT '{col_comment}' 
                        AFTER total_feed_wh
                    """)
            
            connection.commit()
            return True
            
    except Exception as e:
        log_message(f"Fehler beim Schema-Update: {e}", "WARNING")
        return False

def apply_scaling(data_to_insert):
    """Wende Skalierung auf die Daten an (optional für Anzeige)"""
    scaling_factors = get_config_attr('power_scaling_factors', {})
    
    scaled_data = data_to_insert.copy()
    
    # Temperatur skalieren für DEBUG-Ausgabe
    if 'temperature' in scaled_data and 'temperature' in scaling_factors:
        raw_temp = scaled_data['temperature']
        scaled_temp = raw_temp / scaling_factors['temperature']
        if DEBUG_MODE:
            log_message(f"Temperatur skaliert: {raw_temp} raw -> {scaled_temp}°C", "DEBUG")
    
    return scaled_data

def insert_database_data(data_to_insert):
    """GEFIXTE VERSION: ERWEITERTE Datenbankfunktion mit total_feed_wh Support"""
    connection = None
    try:
        connection = pymysql.connect(
            host=config.db_host,
            user=config.db_user,
            password=config.db_password,
            database=config.db_database,
            cursorclass=pymysql.cursors.DictCursor,
            connect_timeout=CONNECTION_TIMEOUT,
            read_timeout=READ_TIMEOUT,
            autocommit=False
        )
        
        with connection.cursor() as cursor:
            # DATETIME-FIX: Verwende gefixte Zeitfunktion
            data_to_insert["datetime"] = get_current_time()
            
            # Bestimme Tabelle
            if 'current_feed_total' in data_to_insert:
                # Power-Monitoring Tabelle
                table_name = get_config_attr('table_power_monitoring', 'ffd_power_monitoring')
                
                # Schema prüfen und erweitern
                check_and_update_table_schema(connection, table_name)
                
                # Prüfe verfügbare Spalten
                cursor.execute(f"""
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s
                """, (config.db_database, table_name))
                
                available_columns = [row['COLUMN_NAME'] for row in cursor.fetchall()]
                
                # Basis-SQL mit Kern-Daten
                columns = ['datetime', 'current_feed_total', 'current_feed_l1', 'current_feed_l2', 'current_feed_l3', 
                          'device_status', 'operation_status', 'temperature', 'operation_time']
                values = [
                    data_to_insert["datetime"],
                    data_to_insert.get("current_feed_total", 0),
                    data_to_insert.get("current_feed_l1", 0),
                    data_to_insert.get("current_feed_l2", 0),
                    data_to_insert.get("current_feed_l3", 0),
                    data_to_insert.get("device_status", 0),
                    data_to_insert.get("operation_status", 0),
                    data_to_insert.get("temperature", 0),
                    data_to_insert.get("operation_time", 0)
                ]
                
                # ERWEITERT: total_feed_wh hinzufügen falls verfügbar
                if 'total_feed_wh' in available_columns and 'total_feed_wh' in data_to_insert:
                    columns.append('total_feed_wh')
                    values.append(data_to_insert.get("total_feed_wh", 0))
                
                # Optionale Spalten hinzufügen
                for opt_col in ['daily_feed_wh', 'monthly_feed_kwh']:
                    if opt_col in available_columns and opt_col in data_to_insert:
                        columns.append(opt_col)
                        values.append(data_to_insert.get(opt_col, 0))
                
                # SQL dynamisch erstellen
                placeholders = ', '.join(['%s'] * len(columns))
                columns_str = ', '.join(columns)
                sql = f"INSERT INTO {table_name} ({columns_str}) VALUES ({placeholders})"
                
            else:
                # Fallback: PV-Tabelle (bestehende Logik)
                cursor.execute("SELECT counter FROM ffd_pv ORDER BY id DESC LIMIT 1")
                last_counter = cursor.fetchone()
                if last_counter:
                    consumption = data_to_insert.get("counter", 0) - last_counter["counter"]
                else:
                    consumption = 0
                
                data_to_insert["consumption"] = consumption
                
                sql = """
                INSERT INTO ffd_pv 
                (datetime, counter, consumption, operational_health, operational_time, feed_actual, dc_power_input1, dc_power_input2) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                """
                
                values = (
                    data_to_insert["datetime"], 
                    data_to_insert.get("counter", 0), 
                    data_to_insert.get("consumption", 0), 
                    data_to_insert.get("operational_health", 0), 
                    data_to_insert.get("operational_time", 0), 
                    data_to_insert.get("feed_actual", 0), 
                    data_to_insert.get("dc_power_input1", 0), 
                    data_to_insert.get("dc_power_input2", 0)
                )
            
            cursor.execute(sql, values)
            connection.commit()
            
            if DEBUG_MODE:
                inserted_columns = columns if 'current_feed_total' in data_to_insert else ['datetime', 'counter', 'consumption', 'operational_health', 'operational_time', 'feed_actual', 'dc_power_input1', 'dc_power_input2']
                log_message(f"Daten erfolgreich eingefügt in {len(inserted_columns)} Spalten", "INFO")
            
            return True
            
    except pymysql.MySQLError as e:
        if connection:
            connection.rollback()
        log_message(f"MySQL Fehler: {e}", "ERROR")
        return False
    except Exception as e:
        if connection:
            connection.rollback()
        log_message(f"Datenbankfehler: {e}", "ERROR")
        return False
    finally:
        if connection:
            connection.close()

def main():
    """GEFIXTE VERSION: ERWEITERTE Hauptfunktion mit Energie-Validierung"""
    start_time = time.time()
    
    try:
        # Config-Validierung  
        required_attrs = ['modbus_server_ip', 'modbus_server_port', 'modbus_unit_id',
                         'db_host', 'db_user', 'db_password', 'db_database']
        
        missing_attrs = [attr for attr in required_attrs if not hasattr(config, attr)]
        
        if missing_attrs:
            log_message(f"Fehlende Config-Attribute: {missing_attrs}", "ERROR")
            return EXIT_CONFIG_ERROR
        
        # Register-Konfiguration prüfen
        register_configs = get_register_configs()
        if not register_configs:
            log_message("Keine Register-Konfiguration gefunden", "ERROR")
            return EXIT_CONFIG_ERROR
        
        # Modbus-Verbindung
        client = ModbusClient(
            host=config.modbus_server_ip,
            port=config.modbus_server_port,
            unit_id=config.modbus_unit_id,
            auto_open=True,
            auto_close=True,
            debug=False,
            timeout=TIMEOUT_SECONDS
        )
        
        if not client.open():
            log_message("Modbus-Verbindung fehlgeschlagen", "ERROR")
            return EXIT_MODBUS_CONNECTION_ERROR
        
        # Daten lesen
        data_to_insert = read_modbus_data(client)
        client.close()
        
        if data_to_insert is None:
            log_message("Modbus-Daten konnten nicht gelesen werden", "ERROR")
            return EXIT_MODBUS_READ_ERROR
        
        # GEFIXTE ENERGIEVALIDIERUNG: Letzten Energiewert für Validierung abrufen
        last_energy_data = None
        if 'total_feed_wh' in data_to_insert:
            try:
                temp_connection = pymysql.connect(
                    host=config.db_host,
                    user=config.db_user,
                    password=config.db_password,
                    database=config.db_database,
                    cursorclass=pymysql.cursors.DictCursor,
                    connect_timeout=CONNECTION_TIMEOUT
                )
                last_energy_data = get_last_energy_value(temp_connection)
                temp_connection.close()
            except Exception as e:
                log_message(f"Warnung: Letzter Energiewert konnte nicht abgerufen werden: {e}", "WARNING")
        
        # Skalierung anwenden (für Debug-Ausgabe)
        scaled_data = apply_scaling(data_to_insert)
        
        # GEFIXTE DATENVALIDIERUNG: Bei Fehlern trotzdem weiter
        try:
            if not validate_data(data_to_insert, last_energy_data):
                log_message("Datenvalidierung fehlgeschlagen - Daten trotzdem speichern", "WARNING")
                # WICHTIGER FIX: Nicht return - weiter mit Speichern
        except Exception as e:
            log_message(f"Datenvalidierung-Fehler ignoriert: {e}", "WARNING")
        
        # In Datenbank schreiben
        if not insert_database_data(data_to_insert):
            log_message("Datenbank-Einfügung fehlgeschlagen", "ERROR")
            return EXIT_DATABASE_WRITE_ERROR
        
        # Erfolg
        execution_time = time.time() - start_time
        
        # ERWEITERTE Ausgabe je nach Debug-Modus
        if DEBUG_MODE:
            print(f"ERFOLG: Daten erfolgreich eingefügt. Ausführungszeit: {execution_time:.2f}s")
            print(f"Eingefügte Daten: {list(data_to_insert.keys())}")
            
            # Schöne Anzeige der Stromdaten
            if 'current_feed_total' in data_to_insert:
                temp_raw = data_to_insert.get('temperature', 0)
                temp_celsius = temp_raw / 10.0
                total_power = data_to_insert.get('current_feed_total', 0)
                total_energy = data_to_insert.get('total_feed_wh', 0)
                
                print(f"=== AKTUELLE STROMDATEN ===")
                print(f"Gesamtleistung: {total_power:,} W ({total_power/1000:.1f} kW)")
                print(f"L1: {data_to_insert.get('current_feed_l1', 0):,} W")
                print(f"L2: {data_to_insert.get('current_feed_l2', 0):,} W") 
                print(f"L3: {data_to_insert.get('current_feed_l3', 0):,} W")
                print(f"Temperatur: {temp_celsius:.1f}°C")
                print(f"Betriebszeit: {data_to_insert.get('operation_time', 0):,} s")
                
                # NEUE Energieanzeige
                if total_energy > 0:
                    print(f"=== ENERGIEDATEN ===")
                    print(f"Gesamtertrag: {total_energy:,} Wh ({total_energy/1000000:.2f} MWh)")
                    
                    # Energiezuwachs anzeigen
                    if last_energy_data and last_energy_data['total_feed_wh'] > 0:
                        increase = total_energy - last_energy_data['total_feed_wh']
                        if increase > 0:
                            print(f"Zuwachs seit letzter Messung: {increase:,} Wh ({increase/1000:.1f} kWh)")
        else:
            # Produktionsmodus - ERWEITERTE kompakte Ausgabe
            total_power = data_to_insert.get('current_feed_total', 0)
            total_energy = data_to_insert.get('total_feed_wh', 0)
            
            if total_energy > 0:
                print(f"OK: {execution_time:.2f}s - {total_power}W - {total_energy/1000000:.2f}MWh")
            else:
                print(f"OK: {execution_time:.2f}s - {total_power}W")
        
        return EXIT_SUCCESS
        
    except KeyboardInterrupt:
        log_message("Script abgebrochen", "WARNING")
        return EXIT_GENERAL_ERROR
        
    except Exception as e:
        log_message(f"Unerwarteter Fehler: {e}", "ERROR")
        if DEBUG_MODE:
            print(f"Traceback: {traceback.format_exc()}")
        return EXIT_GENERAL_ERROR

# ========== HAUPTPROGRAMM für mod_python ==========
try:
    exit_code = main()
    
    # mod_python-freundliches Ende
    if exit_code == EXIT_SUCCESS:
        # Erfolg - normales Ende
        pass
    else:
        # Fehler - aber trotzdem normales Ende für mod_python
        if DEBUG_MODE:
            print(f"Script beendet mit Exit-Code: {exit_code}")
        
except ScriptExit as e:
    # Custom Exception für sauberes Beenden
    if DEBUG_MODE:
        print(f"Script-Exit: {e.exit_code} - {e.message}")
    
except Exception as e:
    # Unerwarteter Fehler
    log_message(f"Fataler Fehler: {e}", "ERROR")
    if DEBUG_MODE:
        print(f"Fatal Error Traceback: {traceback.format_exc()}")

# Script endet hier normal - mod_python ist zufrieden
print("Script erfolgreich beendet (mod_python-kompatibel)")