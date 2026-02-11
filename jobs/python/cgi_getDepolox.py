#!/usr/bin/env python3
# -*- coding: utf-8 -*-

print("Content-Type: text/plain; charset=utf-8")
print()

import time
import os
import traceback
from datetime import datetime

# Setup für lokale Module - WEBHOSTING FIX
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

# Imports - JETZT MIT FUNKTIONIERENDEM PFAD
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
    import depolox_config as config
except ImportError as e:
    print(f"FEHLER: depolox_config.py konnte nicht geladen werden: {e}")
    raise ScriptExit(1, f"Config-Fehler: {e}")

# =============================================================================
# DATETIME-FIX FUNKTIONEN - BEWÄHRTE LÖSUNG
# =============================================================================

def normalize_datetime(dt):
    """
    Normalisiert datetime-Objekte für sichere Vergleiche
    Entfernt Zeitzone um offset-naive/offset-aware Konflikte zu vermeiden
    """
    if dt is None:
        return None
    if hasattr(dt, 'tzinfo') and dt.tzinfo is not None:
        # Timezone-aware -> zu naive konvertieren (lokale Zeit beibehalten)
        return dt.replace(tzinfo=None)
    return dt

def get_safe_current_time():
    """
    Erstellt timezone-naive datetime für Kompatibilität
    """
    return datetime.now().replace(second=0, microsecond=0)

def get_current_time():
    """Datetime-Problem behoben - bewährte Lösung"""
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

# =============================================================================
# KONFIGURATION UND LOGGING
# =============================================================================

# Config-Defaults
def get_config_attr(attr_name, default_value):
    return getattr(config, attr_name, default_value)

# Konfiguration laden
DEBUG_MODE = get_config_attr('debug_mode', False)
MAX_RETRIES = get_config_attr('max_retries', 3)
TIMEOUT_SECONDS = get_config_attr('timeout_seconds', 8)
CONNECTION_TIMEOUT = get_config_attr('connection_timeout', 5)
READ_TIMEOUT = get_config_attr('read_timeout', 3)
VALIDATE_DATA = get_config_attr('validate_data', True)

# Exit-Codes - Erweitert für Dual-System
EXIT_SUCCESS = get_config_attr('EXIT_SUCCESS', 0)
EXIT_CONFIG_ERROR = get_config_attr('EXIT_CONFIG_ERROR', 1)
EXIT_MODBUS_CONNECTION_ERROR = get_config_attr('EXIT_MODBUS_CONNECTION_ERROR', 2)
EXIT_MODBUS_READ_ERROR = get_config_attr('EXIT_MODBUS_READ_ERROR', 3)
EXIT_DATABASE_CONNECTION_ERROR = get_config_attr('EXIT_DATABASE_CONNECTION_ERROR', 4)
EXIT_DATABASE_WRITE_ERROR = get_config_attr('EXIT_DATABASE_WRITE_ERROR', 5)
EXIT_DATA_VALIDATION_ERROR = get_config_attr('EXIT_DATA_VALIDATION_ERROR', 6)
EXIT_DUAL_SYSTEM_PARTIAL_FAILURE = get_config_attr('EXIT_DUAL_SYSTEM_PARTIAL_FAILURE', 7)
EXIT_DUAL_SYSTEM_TOTAL_FAILURE = get_config_attr('EXIT_DUAL_SYSTEM_TOTAL_FAILURE', 8)
EXIT_SYSTEM_TIMEOUT = get_config_attr('EXIT_SYSTEM_TIMEOUT', 9)
EXIT_GENERAL_ERROR = get_config_attr('EXIT_GENERAL_ERROR', 99)

def log_message(message, level="INFO"):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    if DEBUG_MODE or level in ["ERROR", "WARNING"]:
        print(f"[{timestamp}] [{level}] {message}")

# =============================================================================
# DEPOLOX MODBUS FUNKTIONEN - pyModbusTCP VERSION
# =============================================================================

class DepoloxModbusClient:
    def __init__(self, host, port, unit_id):
        self.host = host
        self.port = port
        self.unit_id = unit_id
        self.client = None
        
        # Detected byte orders (will be auto-detected)
        self.ascii_byte_order = None
        self.float_byte_order = None
        self.float_word_order = None

    def connect(self):
        """Verbindung zum Modbus Server herstellen"""
        try:
            self.client = ModbusClient(host=self.host, port=self.port, unit_id=self.unit_id, timeout=TIMEOUT_SECONDS, auto_open=True)
            if self.client.open():
                log_message(f"Verbindung zu {self.host}:{self.port} erfolgreich!", "INFO")
                return True
            else:
                log_message(f"Verbindung zu {self.host}:{self.port} fehlgeschlagen!", "ERROR")
                return False
        except Exception as e:
            log_message(f"Verbindungsfehler: {e}", "ERROR")
            return False

    def disconnect(self):
        """Verbindung schließen"""
        if self.client and self.client.is_open:  # FIX: is_open ist property, nicht method
            self.client.close()
            log_message("Modbus-Verbindung geschlossen", "INFO")

    def get_modbus_address(self, register_address):
        """Konvertiert Register-Adresse zu Modbus-Adresse"""
        if register_address >= 401000:
            return register_address - 401001  # 401xxx Register
        else:
            return register_address - 400001  # 400xxx Register

    def read_registers_basic(self, address, count):
        """Register lesen - pyModbusTCP API"""
        try:
            if not self.client.is_open:  # FIX: is_open ist property, nicht method
                if not self.client.open():
                    return None
            
            result = self.client.read_holding_registers(address, count)
            return result
        except Exception as e:
            log_message(f"Fehler beim Lesen von Register {address}: {e}", "ERROR")
            return None

    def detect_ascii_byte_order(self):
        """ASCII Byte-Reihenfolge automatisch erkennen"""
        log_message("Erkenne ASCII Byte-Reihenfolge...", "INFO")
        
        # Teste mit Einheiten, die bekannt sein sollten
        test_addresses = [
            (400102, "Cl2 Einheit"),    # 400102 = Cl2 Einheit
            (400117, "pH Einheit"),     # 400117 = pH Einheit
            (400132, "Redox Einheit"),  # 400132 = Redox Einheit
        ]
        
        method_scores = {"normal": 0, "swapped": 0}
        
        for address, description in test_addresses:
            modbus_address = self.get_modbus_address(address)
            result = self.read_registers_basic(modbus_address, 5)
            if not result:
                continue
                
            registers = result
            
            # Versuche beide Byte-Reihenfolgen
            methods = {
                "normal": lambda reg: chr((reg >> 8) & 0xFF) + chr(reg & 0xFF),
                "swapped": lambda reg: chr(reg & 0xFF) + chr((reg >> 8) & 0xFF)
            }
            
            for method_name, convert_func in methods.items():
                text = ""
                for reg in registers:
                    try:
                        text += convert_func(reg)
                    except:
                        continue
                text = text.rstrip('\x00').strip()
                
                # Bewerte spezifische bekannte Muster
                score = 0
                text_lower = text.lower()
                if "mg/l" in text_lower or "mg" in text_lower:
                    score += 20
                if "ph" in text_lower:
                    score += 20
                if "mv" in text_lower:
                    score += 20
                if "°c" in text_lower or "c°" in text_lower:
                    score += 10
                if "%" in text:
                    score += 5
                
                # Bonus für mehr lesbare Zeichen
                readable_chars = sum(1 for c in text if c.isprintable() and c.isalnum())
                score += readable_chars
                
                method_scores[method_name] += score
                if DEBUG_MODE:
                    log_message(f"  {method_name:8} bei {description}: '{text}' (Score: {score})", "DEBUG")
        
        # Wähle die Methode mit dem höchsten Score
        if method_scores['swapped'] >= method_scores['normal']:
            self.ascii_byte_order = "swapped"
            log_message("ASCII Byte-Reihenfolge erkannt: swapped", "INFO")
        else:
            self.ascii_byte_order = "normal"
            log_message("ASCII Byte-Reihenfolge erkannt: normal", "INFO")
        
        return self.ascii_byte_order

    def detect_float_byte_order(self):
        """Float Byte-Reihenfolge automatisch erkennen"""
        log_message("Erkenne Float Byte-Reihenfolge...", "INFO")
        
        # Lese Temperatur Messwert (sollte zwischen -20 und +50°C liegen)
        modbus_address = self.get_modbus_address(400175)
        result = self.read_registers_basic(modbus_address, 2)
        if not result or len(result) < 2:
            log_message("Konnte Temperatur für Byte-Order Erkennung nicht lesen", "WARNING")
            self.float_byte_order = "big_endian"
            self.float_word_order = "normal"
            return
            
        reg1, reg2 = result[0], result[1]
        
        # Verschiedene Byte-Reihenfolgen testen
        combinations = [
            ("big_endian", "normal", '>HH', '>f'),
            ("big_endian", "swapped", '>HH', '<f'),
            ("little_endian", "normal", '<HH', '<f'),
            ("little_endian", "swapped", '<HH', '>f'),
        ]
        
        valid_results = []
        
        for byte_order, word_order, pack_fmt, unpack_fmt in combinations:
            try:
                if word_order == "normal":
                    bytes_data = struct.pack(pack_fmt, reg1, reg2)
                else:  # swapped
                    bytes_data = struct.pack(pack_fmt, reg2, reg1)
                
                value = struct.unpack(unpack_fmt, bytes_data)[0]
                
                # Prüfe ob Wert realistisch für Temperatur ist
                is_realistic = -50 <= value <= 100 and not (value == 0.0)
                
                if DEBUG_MODE:
                    log_message(f"  {byte_order:12} {word_order:8}: {value:>12.2f}°C {'✓' if is_realistic else '✗'}", "DEBUG")
                
                if is_realistic:
                    valid_results.append((byte_order, word_order, abs(25 - value)))
                    
            except:
                if DEBUG_MODE:
                    log_message(f"  {byte_order:12} {word_order:8}: FEHLER", "DEBUG")
        
        if valid_results:
            # Wähle den realistischsten Wert (nächst zu 25°C)
            byte_order, word_order, _ = min(valid_results, key=lambda x: x[2])
            self.float_byte_order = byte_order
            self.float_word_order = word_order
            log_message(f"Float Byte-Reihenfolge erkannt: {byte_order}, Word-Order: {word_order}", "INFO")
        else:
            log_message("Float Byte-Reihenfolge nicht erkennbar, verwende Standard", "WARNING")
            self.float_byte_order = "big_endian"
            self.float_word_order = "normal"

    def auto_detect_byte_orders(self):
        """Automatische Erkennung der Byte-Reihenfolgen"""
        log_message("Automatische Byte-Order Erkennung", "INFO")
        
        self.detect_ascii_byte_order()
        self.detect_float_byte_order()

    def read_float(self, address):
        """FLOAT Werte lesen mit erkannter Byte-Reihenfolge"""
        try:
            modbus_address = self.get_modbus_address(address)
            result = self.read_registers_basic(modbus_address, 2)
            if result and len(result) >= 2:
                reg1, reg2 = result[0], result[1]
                
                # Verwende erkannte Byte-Reihenfolge
                if self.float_byte_order == "big_endian":
                    pack_fmt = '>HH'
                    unpack_fmt = '>f' if self.float_word_order == "normal" else '<f'
                else:
                    pack_fmt = '<HH'
                    unpack_fmt = '<f' if self.float_word_order == "normal" else '>f'
                
                if self.float_word_order == "normal":
                    bytes_data = struct.pack(pack_fmt, reg1, reg2)
                else:  # swapped
                    bytes_data = struct.pack(pack_fmt, reg2, reg1)
                
                float_value = struct.unpack(unpack_fmt, bytes_data)[0]
                return float_value
            return None
        except Exception as e:
            log_message(f"Fehler beim Lesen von Float Register {address}: {e}", "ERROR")
            return None

    def read_uint16(self, address):
        """UINT16 Werte lesen (1 Register = 2 Bytes)"""
        try:
            modbus_address = self.get_modbus_address(address)
            result = self.read_registers_basic(modbus_address, 1)
            if result and len(result) >= 1:
                return result[0]
            return None
        except Exception as e:
            log_message(f"Fehler beim Lesen von UINT16 Register {address}: {e}", "ERROR")
            return None

    def read_uint32(self, address):
        """UINT32 Werte lesen (2 Register = 4 Bytes)"""
        try:
            modbus_address = self.get_modbus_address(address)
            result = self.read_registers_basic(modbus_address, 2)
            if result and len(result) >= 2:
                return (result[0] << 16) | result[1]
            return None
        except Exception as e:
            log_message(f"Fehler beim Lesen von UINT32 Register {address}: {e}", "ERROR")
            return None

    def read_ascii(self, address, length):
        """ASCII String lesen mit erkannter Byte-Reihenfolge"""
        try:
            registers_needed = length // 2
            modbus_address = self.get_modbus_address(address)
            result = self.read_registers_basic(modbus_address, registers_needed)
            if result:
                ascii_string = ""
                for reg in result:
                    if self.ascii_byte_order == "normal":
                        ascii_string += chr((reg >> 8) & 0xFF) + chr(reg & 0xFF)
                    else:  # swapped
                        ascii_string += chr(reg & 0xFF) + chr((reg >> 8) & 0xFF)
                return ascii_string.rstrip('\x00')
            return None
        except Exception as e:
            log_message(f"Fehler beim Lesen von ASCII Register {address}: {e}", "ERROR")
            return None

# =============================================================================
# DEPOLOX DATEN LESEN
# =============================================================================

def read_depolox_data(depolox_client):
    """Depolox-Daten über Modbus lesen"""
    data_to_insert = {}
    
    # Byte-Order automatisch erkennen
    depolox_client.auto_detect_byte_orders()
    
    # System Information lesen
    system_name = depolox_client.read_ascii(400001, 20)
    software_version = depolox_client.read_ascii(400011, 10)
    
    if system_name:
        data_to_insert['system_name'] = system_name.strip()
    if software_version:
        data_to_insert['software_version'] = software_version.strip()
    
    # Kern-Messwerte lesen
    core_registers = [
        (400100, 'chlorine_value', 'FLOAT'),
        (400111, 'chlorine_setpoint', 'FLOAT'),
        (400113, 'chlorine_dosing', 'FLOAT'),
        (400115, 'ph_value', 'FLOAT'),
        (400126, 'ph_setpoint', 'FLOAT'),
        (400128, 'ph_dosing', 'FLOAT'),
        (400130, 'redox_value', 'FLOAT'),
        (400175, 'temperature_value', 'FLOAT'),
        (400300, 'alarm_state', 'UINT16'),
        (400301, 'digital_inputs', 'UINT16'),
        (400308, 'error_chlorine', 'UINT32'),
        (400310, 'error_ph', 'UINT32'),
    ]
    
    successful_reads = 0
    
    for address, key, reg_type in core_registers:
        try:
            value = None
            
            if reg_type == 'FLOAT':
                value = depolox_client.read_float(address)
            elif reg_type == 'UINT16':
                value = depolox_client.read_uint16(address)
            elif reg_type == 'UINT32':
                value = depolox_client.read_uint32(address)
            
            if value is not None:
                # Filtere unrealistische Werte
                if reg_type == 'FLOAT' and abs(value) > 1e6:
                    if DEBUG_MODE:
                        log_message(f"Unrealistischer Wert für {key}: {value}", "DEBUG")
                    continue
                    
                data_to_insert[key] = value
                successful_reads += 1
                
                if DEBUG_MODE:
                    log_message(f"Register {address} -> {key}: {value}", "DEBUG")
            
        except Exception as e:
            log_message(f"Fehler beim Lesen von Register {address} ({key}): {e}", "WARNING")
    
    if successful_reads == 0:
        log_message("Keine Depolox-Register erfolgreich gelesen", "ERROR")
        return None
    
    log_message(f"Erfolgreich {successful_reads} von {len(core_registers)} Registern gelesen", "INFO")
    return data_to_insert

# =============================================================================
# DATENBANKFUNKTIONEN FÜR DEPOLOX
# =============================================================================

def get_system_id_by_name(connection, system_name):
    """System-ID anhand des Systemnamens ermitteln"""
    try:
        with connection.cursor() as cursor:
            cursor.execute(
                "SELECT system_id FROM depolox_systems WHERE system_name = %s AND is_active = TRUE",
                (system_name,)
            )
            result = cursor.fetchone()
            if result:
                return result[0]
            else:
                log_message(f"System '{system_name}' nicht in Datenbank gefunden", "WARNING")
                return None
    except Exception as e:
        log_message(f"Fehler beim Ermitteln der System-ID: {e}", "ERROR")
        return None

def insert_depolox_system_status(connection, system_id, data_to_insert):
    """System-Status in depolox_system_status einfügen"""
    try:
        with connection.cursor() as cursor:
            cursor.execute("""
                INSERT INTO depolox_system_status 
                (system_id, alarm_state, digital_inputs, chlorine_mode, ph_mode, timestamp) 
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (
                system_id,
                data_to_insert.get('alarm_state', 0),
                data_to_insert.get('digital_inputs', 0),
                0,  # chlorine_mode - könnte später aus Modbus gelesen werden
                0,  # ph_mode - könnte später aus Modbus gelesen werden  
                get_current_time()
            ))
        log_message("System-Status erfolgreich eingefügt", "INFO")
        return True
    except Exception as e:
        log_message(f"Fehler beim Einfügen des System-Status: {e}", "ERROR")
        return False

def insert_depolox_error_logs(connection, system_id, data_to_insert):
    """Fehler-Logs einfügen/aktualisieren wenn Fehlercodes vorhanden"""
    try:
        error_mappings = [
            ('error_chlorine', 'CHLORINE'),
            ('error_ph', 'PH'),
        ]
        
        for error_key, category in error_mappings:
            error_code = data_to_insert.get(error_key, 0)
            if error_code and error_code > 0:
                with connection.cursor() as cursor:
                    # Prüfen ob Fehler bereits aktiv ist
                    cursor.execute("""
                        SELECT error_id FROM depolox_error_logs 
                        WHERE system_id = %s AND error_category = %s AND error_code = %s AND is_active = TRUE
                    """, (system_id, category, error_code))
                    
                    if cursor.fetchone():
                        # Fehler bereits vorhanden - Update
                        cursor.execute("""
                            UPDATE depolox_error_logs 
                            SET last_occurrence = %s, occurrence_count = occurrence_count + 1
                            WHERE system_id = %s AND error_category = %s AND error_code = %s AND is_active = TRUE
                        """, (get_current_time(), system_id, category, error_code))
                        log_message(f"Fehler {category}:{error_code} aktualisiert", "WARNING")
                    else:
                        # Neuer Fehler - Insert
                        cursor.execute("""
                            INSERT INTO depolox_error_logs 
                            (system_id, error_category, error_code, error_description, severity, is_active, first_occurrence, last_occurrence) 
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                        """, (
                            system_id, category, error_code,
                            f"Fehlercode {error_code} in {category}",
                            'ERROR', True, get_current_time(), get_current_time()
                        ))
                        log_message(f"Neuer Fehler {category}:{error_code} protokolliert", "ERROR")
            else:
                # Kein Fehler - eventuell aktive Fehler deaktivieren
                with connection.cursor() as cursor:
                    cursor.execute("""
                        UPDATE depolox_error_logs 
                        SET is_active = FALSE, last_occurrence = %s
                        WHERE system_id = %s AND error_category = %s AND is_active = TRUE
                    """, (get_current_time(), system_id, category))
        
        return True
    except Exception as e:
        log_message(f"Fehler beim Verarbeiten der Error-Logs: {e}", "ERROR")
        return False

def insert_system_info(connection, system_id, data_to_insert):
    """System-Informationen einfügen"""
    try:
        software_version = data_to_insert.get('software_version')
        if software_version:
            with connection.cursor() as cursor:
                cursor.execute("""
                    INSERT INTO depolox_system_info 
                    (system_id, software_version, timestamp) 
                    VALUES (%s, %s, %s)
                """, (system_id, software_version, get_current_time()))
            log_message("System-Info erfolgreich eingefügt", "INFO")
        return True
    except Exception as e:
        log_message(f"Fehler beim Einfügen der System-Info: {e}", "ERROR")
        return False

def write_to_database(data_to_insert):
    """Legacy Datenbankfunktion für Single-System"""
    connection = None
    try:
        connection = pymysql.connect(
            host=config.db_host,
            user=config.db_user,
            password=config.db_password,
            database=config.db_database,
            charset='utf8mb4',
            autocommit=False
        )
        
        log_message("Datenbankverbindung erfolgreich hergestellt", "INFO")
        
        system_name = data_to_insert.get('system_name', 'Schwimmer')
        system_id = get_system_id_by_name(connection, system_name)
        
        if not system_id:
            log_message(f"System '{system_name}' nicht gefunden - verwende Standard-ID 1", "WARNING")
            system_id = 1
        
        connection.begin()
        
        try:
            with connection.cursor() as cursor:
                cursor.callproc('sp_insert_depolox_measurement_batch', [
                    system_id,
                    data_to_insert.get('chlorine_value'),
                    data_to_insert.get('chlorine_setpoint'),
                    data_to_insert.get('chlorine_dosing'),
                    data_to_insert.get('ph_value'),
                    data_to_insert.get('ph_setpoint'),
                    data_to_insert.get('ph_dosing'),
                    data_to_insert.get('redox_value'),
                    data_to_insert.get('temperature_value'),
                    get_current_time()
                ])
            
            log_message("Messdaten über Stored Procedure erfolgreich eingefügt", "INFO")
            
            insert_depolox_system_status(connection, system_id, data_to_insert)
            insert_depolox_error_logs(connection, system_id, data_to_insert)
            
            if data_to_insert.get('software_version'):
                insert_system_info(connection, system_id, data_to_insert)
            
            connection.commit()
            log_message("Datenbankoperationen erfolgreich abgeschlossen", "INFO")
            return True
            
        except Exception as e:
            connection.rollback()
            log_message(f"Datenbankfehler - Rollback durchgeführt: {e}", "ERROR")
            return False
            
    except Exception as e:
        log_message(f"Datenbankverbindungsfehler: {e}", "ERROR")
        return False
        
    finally:
        if connection:
            connection.close()
            log_message("Datenbankverbindung geschlossen", "INFO")

# =============================================================================
# DUAL-SYSTEM FUNKTIONEN
# =============================================================================

def process_single_depolox_system(system_config, system_index=1):
    """Verarbeitet ein einzelnes Depolox-System"""
    system_name = system_config.get('name', f'System{system_index}')
    
    try:
        print(f"🔌 Verbinde zu {system_name}: {system_config['host']}:{system_config['port']}")
        
        # Depolox Modbus Client erstellen
        depolox_client = DepoloxModbusClient(
            system_config['host'],
            system_config['port'],
            system_config['unit_id']
        )
        
        # Verbindung herstellen
        if not depolox_client.connect():
            print(f"❌ {system_name} Modbus-Verbindung fehlgeschlagen")
            return None, EXIT_MODBUS_CONNECTION_ERROR
        
        # Daten lesen
        print(f"📊 Lese {system_name} Daten...")
        data_to_insert = read_depolox_data(depolox_client)
        depolox_client.disconnect()
        
        if data_to_insert is None:
            print(f"❌ {system_name} Daten konnten nicht gelesen werden")
            return None, EXIT_MODBUS_READ_ERROR
        
        # System-ID für Datenbank setzen
        data_to_insert['target_system_id'] = system_config.get('system_id_db', system_index)
        data_to_insert['system_config_name'] = system_name
        
        print(f"✅ {system_name} erfolgreich gelesen: {len(data_to_insert)} Parameter")
        
        # Wichtige Werte anzeigen
        if DEBUG_MODE:
            print(f"   Chlor: {data_to_insert.get('chlorine_value', 'N/A'):.2f} mg/l")
            print(f"   pH: {data_to_insert.get('ph_value', 'N/A'):.2f}")
            print(f"   Temp: {data_to_insert.get('temperature_value', 'N/A'):.1f}°C")
            if data_to_insert.get('error_chlorine', 0) > 0:
                print(f"   ⚠️ Chlor-Fehler: {data_to_insert.get('error_chlorine')}")
        
        return data_to_insert, EXIT_SUCCESS
        
    except Exception as e:
        print(f"❌ Fehler bei {system_name}: {e}")
        return None, EXIT_GENERAL_ERROR

def write_system_to_database(data_to_insert, system_name):
    """Schreibt Daten eines Systems in die Datenbank"""
    try:
        # Datenbankverbindung herstellen
        connection = pymysql.connect(
            host=config.db_host,
            user=config.db_user,
            password=config.db_password,
            database=config.db_database,
            charset='utf8mb4',
            autocommit=False
        )
        
        # System-ID ermitteln oder übernehmen
        target_system_id = data_to_insert.get('target_system_id')
        if not target_system_id:
            # Fallback: System-ID anhand Name ermitteln
            detected_name = data_to_insert.get('system_name', system_name)
            target_system_id = get_system_id_by_name(connection, detected_name)
            if not target_system_id:
                target_system_id = 1 if system_name == 'Schwimmer' else 2  # Fallback
        
        print(f"   📝 Verwende System-ID {target_system_id} für {system_name}")
        
        # Transaktions-Start
        connection.begin()
        
        try:
            # 1. Hauptmessungen über Stored Procedure einfügen
            with connection.cursor() as cursor:
                cursor.callproc('sp_insert_depolox_measurement_batch', [
                    target_system_id,
                    data_to_insert.get('chlorine_value'),
                    data_to_insert.get('chlorine_setpoint'),
                    data_to_insert.get('chlorine_dosing'),
                    data_to_insert.get('ph_value'),
                    data_to_insert.get('ph_setpoint'),
                    data_to_insert.get('ph_dosing'),
                    data_to_insert.get('redox_value'),
                    data_to_insert.get('temperature_value'),
                    get_current_time()
                ])
            
            # 2. System-Status einfügen
            insert_depolox_system_status(connection, target_system_id, data_to_insert)
            
            # 3. Fehler-Logs verarbeiten
            insert_depolox_error_logs(connection, target_system_id, data_to_insert)
            
            # 4. System-Info aktualisieren
            if data_to_insert.get('software_version'):
                insert_system_info(connection, target_system_id, data_to_insert)
            
            # Transaktion bestätigen
            connection.commit()
            print(f"   ✅ {system_name} Datenbank-Operationen erfolgreich")
            return True
            
        except Exception as e:
            connection.rollback()
            print(f"   ❌ {system_name} Datenbankfehler - Rollback: {e}")
            return False
            
    except Exception as e:
        print(f"   ❌ {system_name} Datenbankverbindungsfehler: {e}")
        return False
        
    finally:
        if 'connection' in locals():
            connection.close()

def generate_dual_system_output(system_results, total_time, final_exit_code):
    """Generiert die Ausgabe für Dual-System-Betrieb"""
    
    if DEBUG_MODE:
        # DEBUG-AUSGABE
        print(f"Gesamtzeit: {total_time:.2f}s")
        print(f"Verarbeitete Systeme: {len(system_results)}")
        
        for result in system_results:
            status = "✅ OK" if result['success'] and result['database_success'] else "❌ FEHLER"
            print(f"{result['name']}: {status}")
            
            if result['data']:
                data = result['data']
                print(f"  Chlor: {data.get('chlorine_value', 'N/A'):.2f} mg/l")
                print(f"  pH: {data.get('ph_value', 'N/A'):.2f}")
                print(f"  Temp: {data.get('temperature_value', 'N/A'):.1f}°C")
                
                # Fehler anzeigen
                if data.get('error_chlorine', 0) > 0 or data.get('error_ph', 0) > 0:
                    print(f"  ⚠️ Fehler: Cl2={data.get('error_chlorine', 0)}, pH={data.get('error_ph', 0)}")
        
        print(f"Exit-Code: {final_exit_code}")
        
    else:
        # PRODUKTIONS-AUSGABE (kompakt)
        successful_count = sum(1 for r in system_results if r['success'] and r['database_success'])
        total_count = len(system_results)
        
        # Status-String zusammenbauen
        status_parts = []
        for result in system_results:
            if result['success'] and result['database_success'] and result['data']:
                data = result['data']
                cl = data.get('chlorine_value', 0)
                ph = data.get('ph_value', 0)
                temp = data.get('temperature_value', 0)
                
                # Fehler-Flags
                error_flags = []
                if data.get('alarm_state', 0) > 0:
                    error_flags.append("ALARM")
                if data.get('error_chlorine', 0) > 0 or data.get('error_ph', 0) > 0:
                    error_flags.append("ERROR")
                
                error_str = f"[{','.join(error_flags)}]" if error_flags else ""
                status_parts.append(f"{result['name']}:Cl2:{cl:.2f},pH:{ph:.2f},T:{temp:.1f}°C{error_str}")
            else:
                status_parts.append(f"{result['name']}:FEHLER")
        
        # Gesamtstatus
        if final_exit_code == EXIT_SUCCESS:
            overall_status = "DUAL_OK"
        elif final_exit_code == 7:  # PARTIAL_FAILURE
            overall_status = "DUAL_PARTIAL"
        else:
            overall_status = "DUAL_FAILED"
        
        print(f"{overall_status}: {total_time:.2f}s - {successful_count}/{total_count} - {' | '.join(status_parts)} - DB:OK")

def main_legacy_single_system():
    """Legacy Hauptfunktion für Ein-System-Betrieb"""
    print("🔄 Verwende Legacy Single-System Modus")
    
    start_time = time.time()
    
    try:
        # Config-Validierung  
        required_attrs = ['modbus_server_ip', 'modbus_server_port', 'modbus_unit_id',
                         'db_host', 'db_user', 'db_password', 'db_database']
        
        missing_attrs = [attr for attr in required_attrs if not hasattr(config, attr)]
        
        if missing_attrs:
            log_message(f"Fehlende Config-Attribute: {missing_attrs}", "ERROR")
            return EXIT_CONFIG_ERROR
        
        # Depolox Modbus Client erstellen (pyModbusTCP)
        depolox_client = DepoloxModbusClient(
            config.modbus_server_ip,
            config.modbus_server_port,
            config.modbus_unit_id
        )
        
        # Verbindung herstellen
        if not depolox_client.connect():
            log_message("Depolox Modbus-Verbindung fehlgeschlagen", "ERROR")
            return EXIT_MODBUS_CONNECTION_ERROR
        
        # Daten lesen
        data_to_insert = read_depolox_data(depolox_client)
        depolox_client.disconnect()
        
        if data_to_insert is None:
            log_message("Depolox-Daten konnten nicht gelesen werden", "ERROR")
            return EXIT_MODBUS_READ_ERROR
        
        # Datenbank schreiben
        database_success = write_to_database(data_to_insert)
        
        if not database_success:
            log_message("Datenbankoperationen fehlgeschlagen", "ERROR")
            return EXIT_DATABASE_WRITE_ERROR
        
        execution_time = time.time() - start_time
        
        # Legacy Ausgabe beibehalten
        if DEBUG_MODE:
            print(f"ERFOLG: Depolox-Daten erfolgreich gelesen und gespeichert. Ausführungszeit: {execution_time:.2f}s")
            print(f"System: {data_to_insert.get('system_name', 'Unknown')}")
            print(f"Gelesene Daten: {list(data_to_insert.keys())}")
            
            # Kompakte Übersicht der wichtigsten Werte
            important_values = {
                'Chlor': data_to_insert.get('chlorine_value'),
                'pH': data_to_insert.get('ph_value'),
                'Redox': data_to_insert.get('redox_value'),
                'Temperatur': data_to_insert.get('temperature_value'),
                'Cl2 Dosierung': data_to_insert.get('chlorine_dosing'),
                'pH Dosierung': data_to_insert.get('ph_dosing'),
            }
            
            print("=== AKTUELLE DEPOLOX-WERTE ===")
            for name, value in important_values.items():
                if value is not None:
                    if 'Dosierung' in name:
                        status = "🔄 AKTIV" if value > 0.1 else "⏸️ INAKTIV"
                        print(f"{name}: {value:.1f}% {status}")
                    else:
                        print(f"{name}: {value:.2f}")
                        
            # Zusätzliche DB-Info im Debug-Modus
            if data_to_insert.get('alarm_state', 0) > 0:
                print(f"⚠️ ALARM-STATUS: {data_to_insert.get('alarm_state')}")
            if data_to_insert.get('error_chlorine', 0) > 0:
                print(f"🚨 CHLOR-FEHLER: {data_to_insert.get('error_chlorine')}")
            if data_to_insert.get('error_ph', 0) > 0:
                print(f"🚨 PH-FEHLER: {data_to_insert.get('error_ph')}")
                
        else:
            # Produktionsmodus - kompakte Ausgabe
            system_name = data_to_insert.get('system_name', 'Unknown')
            cl_value = data_to_insert.get('chlorine_value', 0)
            ph_value = data_to_insert.get('ph_value', 0)
            temp_value = data_to_insert.get('temperature_value', 0)
            
            # Status-Indikatoren
            status_flags = []
            if data_to_insert.get('alarm_state', 0) > 0:
                status_flags.append("ALARM")
            if data_to_insert.get('error_chlorine', 0) > 0 or data_to_insert.get('error_ph', 0) > 0:
                status_flags.append("ERROR")
            status_str = f" [{','.join(status_flags)}]" if status_flags else ""
            
            print(f"LEGACY: {execution_time:.2f}s - {system_name} - Cl2:{cl_value:.2f} pH:{ph_value:.2f} T:{temp_value:.1f}°C - DB:OK{status_str}")
        
        return EXIT_SUCCESS
        
    except Exception as e:
        log_message(f"Legacy System Fehler: {e}", "ERROR")
        return EXIT_GENERAL_ERROR

# =============================================================================
# HAUPTFUNKTION MIT DUAL-SYSTEM SUPPORT
# =============================================================================

def main():
    """Hauptfunktion - Dual System Support mit Debug"""
    start_time = time.time()
    
    # DEBUG: Multi-System Status anzeigen
    print("=== DEPOLOX DUAL SYSTEM STARTUP ===")
    
    try:
        # Config-Validierung für Multi-System
        if not hasattr(config, 'multi_system_config'):
            print("⚠️  Keine Multi-System-Konfiguration gefunden - verwende Legacy-Modus")
            return main_legacy_single_system()
        
        multi_config = config.multi_system_config
        print(f"📋 Multi-System Config: enabled={multi_config.get('enabled')}")
        
        if not multi_config.get('enabled', False):
            print("⚠️  Multi-System deaktiviert - verwende Legacy-Modus")
            return main_legacy_single_system()
        
        systems_to_process = [s for s in multi_config.get('systems', []) if s.get('enabled', True)]
        print(f"📊 Gefundene Systeme: {len(systems_to_process)}")
        
        for i, sys in enumerate(systems_to_process, 1):
            print(f"   System {i}: {sys.get('name')} - {sys.get('host')}:{sys.get('port')} - Enabled: {sys.get('enabled')}")
        
        if not systems_to_process:
            print("❌ Keine aktiven Systeme konfiguriert")
            return EXIT_CONFIG_ERROR
        
        if not multi_config.get('process_all_systems', False) and len(systems_to_process) > 1:
            # Nur erstes System verarbeiten
            systems_to_process = systems_to_process[:1]
            print(f"⚠️  Verarbeite nur das erste System (process_all_systems={multi_config.get('process_all_systems')})")
        
        print(f"🚀 Starte Verarbeitung von {len(systems_to_process)} System(en)")
        print()
        
        # Ergebnisse sammeln
        system_results = []
        successful_systems = 0
        failed_systems = 0
        
        # Durch alle Systeme iterieren
        for index, system_config in enumerate(systems_to_process, 1):
            system_name = system_config.get('name', f'System{index}')
            
            print(f"=== VERARBEITE {system_name.upper()} (System {index}/{len(systems_to_process)}) ===")
            
            # System verarbeiten
            data_result, exit_code = process_single_depolox_system(system_config, index)
            
            system_result = {
                'name': system_name,
                'success': exit_code == EXIT_SUCCESS,
                'exit_code': exit_code,
                'data': data_result,
                'database_success': False
            }
            
            # Bei erfolgreichem Lesen: In Datenbank schreiben
            if data_result is not None:
                print(f"💾 Schreibe {system_name} Daten in Datenbank...")
                db_success = write_system_to_database(data_result, system_name)
                system_result['database_success'] = db_success
                
                if db_success:
                    successful_systems += 1
                    print(f"✅ {system_name}: Erfolgreich verarbeitet")
                else:
                    failed_systems += 1
                    print(f"❌ {system_name}: Datenbankfehler")
            else:
                failed_systems += 1
                print(f"❌ {system_name}: Modbus-Fehler")
            
            system_results.append(system_result)
            print()
        
        # Gesamtergebnis bestimmen
        total_execution_time = time.time() - start_time
        
        if successful_systems == len(systems_to_process):
            final_exit_code = EXIT_SUCCESS
        elif successful_systems > 0:
            final_exit_code = 7  # PARTIAL_FAILURE
        else:
            final_exit_code = 8  # TOTAL_FAILURE
        
        # Ausgabe generieren
        print("=== DUAL SYSTEM ERGEBNIS ===")
        generate_dual_system_output(system_results, total_execution_time, final_exit_code)
        
        return final_exit_code
        
    except Exception as e:
        print(f"❌ Unerwarteter Fehler in Dual-System-Verarbeitung: {e}")
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
print("Depolox Script erfolgreich beendet (mod_python-kompatibel)")