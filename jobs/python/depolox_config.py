# depolox_config.py - Konfiguration für Depolox Pool Monitoring
# Version 1.3 - Dual System Support mit kompletter Datenbankintegration
# Optimiert für Webhosting mit zwei Depolox-Systemen in svfd_schedule Database

# ====================================================================
# DUAL DEPOLOX SYSTEM KONFIGURATION
# ====================================================================

# System 1: Schwimmer (bereits funktioniert)
modbus_server_ip = "iykjlt0jy435sqad.myfritz.net"
modbus_server_port = 8101
modbus_unit_id = 1

# System 2: Zweites Depolox-System
modbus_server_ip_system2 = "iykjlt0jy435sqad.myfritz.net"
modbus_server_port_system2 = 8102  # Anderer Port für zweites System
modbus_unit_id_system2 = 1

# ====================================================================
# DATENBANK KONFIGURATION - svfd_schedule
# ====================================================================

# Hauptdatenbank-Verbindung
db_host = 'localhost'
db_user = 'svfd_Schedule'
db_password = 'rq*6X4s82'
db_database = 'svfd_schedule'  # Bestehende Datenbank verwenden

# Erweiterte Datenbank-Konfiguration
db_config = {
    'charset': 'utf8mb4',
    'autocommit': False,
    'connect_timeout': 10,
    'read_timeout': 30,
    'write_timeout': 30,
    'use_unicode': True,
    'sql_mode': 'TRADITIONAL',
    'init_command': "SET SESSION sql_mode='TRADITIONAL'",
    'cursorclass': 'pymysql.cursors.DictCursor'
}

# ====================================================================
# MULTI-SYSTEM KONFIGURATION - AKTIVIERT FÜR BEIDE SYSTEME
# ====================================================================

multi_system_config = {
    'enabled': True,                     # ✅ Multi-System Support aktiviert
    'systems': [
        {
            'name': 'Schwimmer',
            'host': 'iykjlt0jy435sqad.myfritz.net',
            'port': 8101,
            'unit_id': 1,
            'location': 'Schwimmbecken Hauptbecken',
            'priority': 1,
            'system_id_db': 1,              # ID in depolox_systems Tabelle
            'enabled': True,                # System 1 aktiv
            'description': 'Hauptpool System - Depolox E 700 P'
        },
        {
            'name': 'System2',              # ✅ Zweites System aktiviert
            'host': 'iykjlt0jy435sqad.myfritz.net',
            'port': 8102,                   # Separater Port
            'unit_id': 1,
            'location': 'Schwimmbecken Kinderbecken',
            'priority': 2,
            'system_id_db': 2,              # Separate System-ID
            'enabled': True,                # ✅ System 2 aktiviert
            'description': 'Kinderbereich System - Depolox E 700 P'
        }
    ],
    'process_all_systems': True,         # ✅ BEIDE Systeme parallel verarbeiten
    'fail_on_any_system_error': False,   # Weiter wenn ein System ausfällt
    'log_per_system': True,              # Separates Logging pro System
    'parallel_processing': False,        # Sequenziell verarbeiten (sicherer)
    'timeout_per_system': 20,            # 20 Sekunden pro System
    'retry_failed_systems': True,        # Fehlgeschlagene Systeme erneut versuchen
    'retry_count': 1,                    # Ein Wiederholungsversuch
    'continue_on_partial_failure': True  # Weitermachen wenn nur ein System fehlschlägt
}

# ====================================================================
# MODBUS VERBINDUNGSKONFIGURATION
# ====================================================================

# Verbindungs-Parameter (optimiert für zwei Systeme)
max_retries = 2                      # Reduziert für Dual-System
timeout_seconds = 8                  # Timeout pro System
connection_timeout = 5               # Verbindungs-Timeout
read_timeout = 3                     # Lese-Timeout
socket_timeout = 10                  # Socket-Timeout

# Byte-Order Detection
byte_order_config = {
    'auto_detect_ascii': True,       # ASCII Byte-Order automatisch erkennen
    'auto_detect_float': True,       # Float Byte-Order automatisch erkennen
    'fallback_ascii_order': 'swapped',   # Fallback wenn Detection fehlschlägt
    'fallback_float_order': 'big_endian',
    'fallback_word_order': 'normal'
}

# ====================================================================
# DEBUG UND LOGGING KONFIGURATION
# ====================================================================

# Debug-Modi
debug_mode = True                   # Hauptdebug-Modus (für Produktion auf False)
dual_system_debug = True          # Extra Debug für Dual-System-Setup
database_debug = False              # Datenbank-Debug
modbus_debug = False                # Modbus-Debug

# Logging-Konfiguration
log_errors_to_db = True             # Fehler in Datenbank protokollieren
log_successful_operations = True    # Erfolgreiche Operationen loggen
log_performance_metrics = True      # Performance-Metriken sammeln

# Erweiterte Logging-Optionen
logging_config = {
    'log_system_separation': True,      # Klar zwischen Systemen trennen
    'log_system_performance': True,     # Performance pro System
    'log_system_failures': True,       # Fehlschläge detailliert loggen
    'compact_dual_output': True,       # Kompakte Ausgabe für beide Systeme
    'show_system_status': True,        # Status beider Systeme anzeigen
    'timestamp_format': '%Y-%m-%d %H:%M:%S',
    'include_microseconds': False,
    'log_level_production': 'INFO',    # Produktions-Log-Level
    'log_level_debug': 'DEBUG'         # Debug-Log-Level
}

# ====================================================================
# DEPOLOX REGISTER MAPPING - FÜR BEIDE SYSTEME GLEICH
# ====================================================================

depolox_register_map = {
    # System Information
    400001: {"name": "Systemname", "type": "ASCII", "length": 20, "access": "R", "category": "system"},
    400011: {"name": "Software Version", "type": "ASCII", "length": 10, "access": "R", "category": "system"},
    400016: {"name": "Aktuelles Datum", "type": "ASCII", "length": 10, "access": "R", "category": "system"},
    400021: {"name": "Aktuelle Uhrzeit", "type": "ASCII", "length": 6, "access": "R", "category": "system"},
    
    # Chlor Messwerte (Kern-Parameter)
    400100: {"name": "Cl2 Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "mg/l", "category": "measurement", "priority": "high"},
    400102: {"name": "Cl2 Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    400111: {"name": "Cl2 Sollwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "mg/l", "category": "setpoint", "priority": "high"},
    400113: {"name": "Cl2 Dosierleistung", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "dosing", "priority": "high"},
    
    # pH Messwerte (Kern-Parameter)
    400115: {"name": "pH Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "", "category": "measurement", "priority": "high"},
    400117: {"name": "pH Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    400126: {"name": "pH Sollwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "", "category": "setpoint", "priority": "high"},
    400128: {"name": "pH Dosierleistung", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "dosing", "priority": "high"},
    
    # Redox Messwerte
    400130: {"name": "Redox Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "mV", "category": "measurement", "priority": "medium"},
    400132: {"name": "Redox Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    
    # Gesamtchlor Messwerte
    400145: {"name": "Gesamtchlor Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "mg/l", "category": "measurement", "priority": "medium"},
    400147: {"name": "Gesamtchlor Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    400156: {"name": "Gesamtchlor Sollwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "mg/l", "category": "setpoint"},
    400158: {"name": "Gesamtchlor Dosierleistung", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "dosing"},
    
    # Leitfähigkeit Messwerte
    400160: {"name": "Leitfähigkeit Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "µS/cm", "category": "measurement", "priority": "low"},
    400162: {"name": "Leitfähigkeit Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    400171: {"name": "Leitfähigkeit Sollwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "µS/cm", "category": "setpoint"},
    400173: {"name": "Leitfähigkeit Dosierleistung", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "dosing"},
    
    # Temperatur Messwerte
    400175: {"name": "Temperatur Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "°C", "category": "measurement", "priority": "high"},
    400177: {"name": "Temperatur Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    
    # Flockung
    400190: {"name": "Flockung akt. Dosierleistung", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "dosing", "priority": "low"},
    
    # Umwälzung (mA-Eingang 1)
    400205: {"name": "Umwälzung Messwert", "type": "FLOAT", "length": 4, "access": "R", "unit": "%", "category": "measurement", "priority": "medium"},
    400207: {"name": "Umwälzung Einheit", "type": "ASCII", "length": 10, "access": "R", "category": "unit"},
    
    # Status und Alarme (Kritisch für Monitoring)
    400300: {"name": "Alarmzustände", "type": "UINT16", "length": 2, "access": "R", "category": "status", "priority": "critical"},
    400301: {"name": "Digital Eingänge", "type": "UINT16", "length": 2, "access": "R", "category": "status", "priority": "medium"},
    400302: {"name": "Relais Ausgänge K1-K8", "type": "UINT16", "length": 2, "access": "R", "category": "status", "priority": "low"},
    400303: {"name": "Relais Ausgänge K21-K24", "type": "UINT16", "length": 2, "access": "R", "category": "status", "priority": "low"},
    400304: {"name": "Betriebsart Chlorregler", "type": "UINT16", "length": 2, "access": "R", "category": "mode", "priority": "medium"},
    400305: {"name": "Betriebsart pH-Regler", "type": "UINT16", "length": 2, "access": "R", "category": "mode", "priority": "medium"},
    400306: {"name": "Betriebsart Gesamtchlor-Regler", "type": "UINT16", "length": 2, "access": "R", "category": "mode", "priority": "low"},
    400307: {"name": "Betriebsart Leitfähigkeits-Regler", "type": "UINT16", "length": 2, "access": "R", "category": "mode", "priority": "low"},
    
    # Fehlercodes (Kritisch für Alarme)
    400308: {"name": "Fehlercode Chlor", "type": "UINT32", "length": 4, "access": "R", "category": "error", "priority": "critical"},
    400310: {"name": "Fehlercode pH", "type": "UINT32", "length": 4, "access": "R", "category": "error", "priority": "critical"},
    400312: {"name": "Fehlercode Redox", "type": "UINT32", "length": 4, "access": "R", "category": "error", "priority": "medium"},
    400314: {"name": "Fehlercode Gesamtchlor", "type": "UINT32", "length": 4, "access": "R", "category": "error", "priority": "medium"},
    400316: {"name": "Fehlercode Leitfähigkeit", "type": "UINT32", "length": 4, "access": "R", "category": "error", "priority": "low"},
}

# ====================================================================
# DATENVALIDIERUNG - POOL-SPEZIFISCHE GRENZWERTE
# ====================================================================

# Datenvalidierung aktivieren
validate_data = True
strict_validation = False           # Strenge Validierung (verwirft ungültige Werte)
log_validation_errors = True       # Validierungsfehler protokollieren

# pH-Wert Validierung (kritisch für Pool-Sicherheit)
ph_min_value = 6.0                 # Absolute Untergrenze
ph_max_value = 8.5                 # Absolute Obergrenze
ph_optimal_min = 7.0               # Optimaler Bereich Untergrenze
ph_optimal_max = 7.6               # Optimaler Bereich Obergrenze
ph_critical_low = 6.5              # Kritisch niedrig (Alarm)
ph_critical_high = 8.0             # Kritisch hoch (Alarm)

# Chlor-Validierung (Desinfektions-Werte)
chlorine_min_value = 0.0           # Minimum erlaubt
chlorine_max_value = 5.0           # Maximum erlaubt (sehr hoch)
chlorine_optimal_min = 0.3         # Optimaler Bereich Untergrenze
chlorine_optimal_max = 0.7         # Optimaler Bereich Obergrenze
chlorine_critical_low = 0.2        # Kritisch niedrig (Alarm)
chlorine_critical_high = 2.0       # Kritisch hoch (Alarm)

# Redox-Validierung (Wasserqualität)
redox_min_value = 600              # Minimum mV (schlecht)
redox_max_value = 900              # Maximum mV
redox_optimal_min = 720            # Optimaler Bereich Untergrenze
redox_optimal_max = 780            # Optimaler Bereich Obergrenze
redox_critical_low = 650           # Kritisch niedrig (Alarm)

# Temperatur-Validierung
temperature_min_value = 10.0       # Minimum Pooltemperatur °C
temperature_max_value = 40.0       # Maximum Pooltemperatur °C
temperature_optimal_min = 22.0     # Komfort Untergrenze
temperature_optimal_max = 26.0     # Komfort Obergrenze
temperature_freeze_warning = 4.0   # Frostwarnung
temperature_too_hot = 32.0         # Zu heiß Warnung

# Dosierleistung-Validierung
dosing_min_value = 0.0             # Minimum Dosierung %
dosing_max_value = 100.0           # Maximum Dosierung %
dosing_high_threshold = 50.0       # Warnung bei hoher Dosierung
dosing_continuous_threshold = 5.0  # Warnung bei kontinuierlicher Dosierung
dosing_stuck_threshold = 80.0      # Dosierung "hängt" Warnung

# Leitfähigkeit-Validierung
conductivity_min_value = 200       # Minimum µS/cm
conductivity_max_value = 2000      # Maximum µS/cm
conductivity_optimal_min = 500     # Optimal Untergrenze
conductivity_optimal_max = 800     # Optimal Obergrenze

# ====================================================================
# EXIT-CODES - ERWEITERT FÜR DUAL-SYSTEM
# ====================================================================

EXIT_SUCCESS = 0
EXIT_CONFIG_ERROR = 1
EXIT_MODBUS_CONNECTION_ERROR = 2
EXIT_MODBUS_READ_ERROR = 3
EXIT_DATABASE_CONNECTION_ERROR = 4
EXIT_DATABASE_WRITE_ERROR = 5
EXIT_DATA_VALIDATION_ERROR = 6
EXIT_DUAL_SYSTEM_PARTIAL_FAILURE = 7    # Ein System fehlgeschlagen
EXIT_DUAL_SYSTEM_TOTAL_FAILURE = 8      # Beide Systeme fehlgeschlagen
EXIT_SYSTEM_TIMEOUT = 9                 # System-Timeout
EXIT_GENERAL_ERROR = 99

# ====================================================================
# DEPOLOX FEHLERCODES UND BETRIEBSMODI
# ====================================================================

# Pool-Betriebsmodi Dekodierung
controller_modes = {
    0: "Hand",
    1: "Automatik",
    2: "Regler Aus",
    3: "Adaption läuft",
    4: "autom. Stellmotorkalibrierung läuft",
    5: "Regler Stopp (Yout=0%)",
    6: "Regler einfrieren (Yout=Yout)",
    7: "Regler Yout=100%",
    8: "Regler Yout=2xYout",
    9: "Stellrad am Stellmotor entriegelt",
    10: "Stellmotor Poti Fehler",
    11: "Eco Mode Umschaltung",
    12: "Hochklorung aktiv",
    13: "Regler Standby",
    14: "Cedox Regler aktiv",
    15: "Stoßchlorung aktiv"
}

# Depolox Fehlercodes Mapping
error_code_meanings = {
    0: "Kein Fehler",
    1: "Nullpunkt Kalibrierung",
    2: "DPD Kalibrierung",
    3: "pH7 Kalibrierung",
    4: "pH4 Kalibrierung",
    5: "Kalibrierfehler z.B. Redox",
    6: "Offset Kalibrierung",
    7: "Zellenfehler",
    8: "Werkskalibrierung Fehler",
    9: "Messwert unter dem Messbereich",
    10: "Messwert über dem Messbereich",
    11: "Sollwertfehler",
    12: "Grenzwertfehler",
    13: "HOCL Fehler (Cl2++)",
    14: "Gesamtchlor Zelle Kommunikationsfehler",
    15: "Overflow (max. Dosierzeit)",
    16: "Adaption Fehler",
    17: "CAN Kommunikation",
    18: "Temperatur Fehler",
    20: "kein Messwasser",
    21: "Stellmotor Fehler",
    22: "Stellmotor Kalibriert Fehler",
    23: "Bürdefehler mA-Ausgang 1",
    24: "Bürdefehler mA-Ausgang 2",
    25: "Bürdefehler mA-Ausgang 3",
    26: "Bürdefehler mA-Ausgang 4",
    27: "Stellglied stetig Fehler",
    28: "Flockung Fehler",
    29: "Hochklorung Fehler",
    30: "Analog Hardware Fehler",
    31: "Speicherfehler (SD/Eeprom)"
}

# Schweregrad der Fehlercodes
error_severity_map = {
    0: "INFO",          # Kein Fehler
    7: "CRITICAL",      # Zellenfehler
    15: "CRITICAL",     # Overflow
    16: "ERROR",        # Adaption Fehler
    18: "ERROR",        # Temperatur Fehler
    20: "CRITICAL",     # Kein Messwasser
    21: "ERROR",        # Stellmotor Fehler
    30: "CRITICAL",     # Hardware Fehler
    31: "CRITICAL"      # Speicherfehler
}

# Digital Eingänge Bedeutung
digital_input_meanings = {
    0: "DI 1",
    1: "DI 2",
    2: "DI 3",
    3: "Messwasser STOP"
}

# ====================================================================
# PERFORMANCE UND TIMING KONFIGURATION
# ====================================================================

# CronJob Optimierungen für Dual-System
cronjob_config = {
    'execution_timeout_seconds': 45,    # 45 Sekunden für beide Systeme
    'timeout_per_system': 20,           # 20 Sekunden pro System
    'retry_on_timeout': False,          # Kein Retry bei Timeout
    'log_to_stdout_only': True,         # Nur stdout für CronJob-Log
    'compress_output': True,            # Kompakte Ausgabe
    'skip_debug_info': True,            # Kein Debug bei CronJob
    'emergency_exit_on_error': False,   # Nicht bei erstem Fehler beenden
    'max_execution_time': 40            # Hard-Limit für Gesamtausführung
}

# Performance-Monitoring
performance_config = {
    'measure_execution_time': True,         # Ausführungszeit messen
    'log_slow_operations': True,            # Langsame Operationen loggen
    'slow_operation_threshold_seconds': 15, # Schwellenwert für langsame Ops
    'monitor_memory_usage': False,          # Memory-Monitoring aus
    'track_success_rate': True,             # Erfolgsrate verfolgen
    'track_per_system_performance': True,   # Performance pro System
    'alert_on_repeated_failures': True,     # Alarm bei wiederholten Fehlern
    'max_consecutive_failures': 3,          # Max. aufeinanderfolgende Fehler
    'performance_history_days': 7           # Performance-Historie aufbewahren
}

# ====================================================================
# POOL-SPEZIFISCHE ALARM-KONFIGURATION
# ====================================================================

# Pool-Alarm-Schwellenwerte
pool_alerts = {
    'ph_critical_low': 6.5,            # Kritischer pH-Wert (Alarm)
    'ph_critical_high': 8.0,           # Kritischer pH-Wert (Alarm)
    'chlorine_critical_low': 0.2,      # Kritischer Chlor-Wert (Alarm)
    'chlorine_critical_high': 2.0,     # Kritischer Chlor-Wert (Alarm)
    'redox_critical_low': 650,         # Kritischer Redox-Wert (Alarm)
    'temperature_freeze_warning': 4.0,  # Frostwarnung
    'temperature_too_hot': 32.0,       # Zu heiß Warnung
    'dosing_stuck_threshold': 80.0,    # Dosierung "hängt" Warnung
    'no_data_timeout_minutes': 15,     # Alarm bei fehlenden Daten
    'error_code_any': True,            # Alarm bei jedem Fehlercode > 0
    'system_offline_minutes': 10,      # System offline Warnung
    'dual_system_offline_critical': True  # Kritisch wenn beide Systeme offline
}

# Alarm-Konfiguration für Datenbank
alarm_config = {
    'enable_database_alarms': True,        # Alarme in Datenbank protokollieren
    'alarm_cooldown_minutes': 30,          # Mindestabstand zwischen gleichen Alarmen
    'critical_alarm_immediate': True,      # Kritische Alarme sofort protokollieren
    'auto_resolve_alarms': True,           # Alarme automatisch auflösen
    'alarm_history_days': 30,              # Alarm-Historie aufbewahren
    'enable_email_alerts': False,          # E-Mail-Benachrichtigungen (noch nicht implementiert)
    'enable_sms_alerts': False             # SMS-Benachrichtigungen (noch nicht implementiert)
}

# ====================================================================
# STATUS-INDIKATOREN FÜR DASHBOARD
# ====================================================================

# Wasserqualitäts-Bewertung
status_indicators = {
    'water_quality_excellent': {
        'ph_min': 7.2, 'ph_max': 7.4,
        'chlorine_min': 0.5, 'chlorine_max': 0.7,
        'redox_min': 750, 'redox_max': 780,
        'temperature_min': 23.0, 'temperature_max': 26.0
    },
    'water_quality_good': {
        'ph_min': 7.0, 'ph_max': 7.6,
        'chlorine_min': 0.3, 'chlorine_max': 1.0,
        'redox_min': 720, 'redox_max': 800,
        'temperature_min': 20.0, 'temperature_max': 30.0
    },
    'water_quality_acceptable': {
        'ph_min': 6.8, 'ph_max': 7.8,
        'chlorine_min': 0.2, 'chlorine_max': 1.5,
        'redox_min': 680, 'redox_max': 820,
        'temperature_min': 15.0, 'temperature_max': 35.0
    }
}

# System-Status-Bewertung
system_status_levels = {
    'optimal': 'Alle Werte im optimalen Bereich',
    'good': 'Werte im akzeptablen Bereich',
    'warning': 'Einige Werte außerhalb optimal',
    'critical': 'Kritische Werte - Sofortmaßnahmen erforderlich',
    'offline': 'System nicht erreichbar',
    'error': 'Systemfehler aktiv'
}

# ====================================================================
# DATETIME UND TIMEZONE KONFIGURATION
# ====================================================================

# Datetime-Fix Konfiguration
datetime_config = {
    'timezone_handling': 'naive',           # 'naive' für Kompatibilität
    'force_timezone_removal': True,        # Entfernt alle Zeitzonen automatisch
    'safe_datetime_operations': True,      # Verwendet sichere Datetime-Funktionen
    'validation_on_datetime_errors': True, # Validiert trotz Datetime-Fehlern
    'fallback_to_local_time': True,        # Fallback bei Timezone-Problemen
    'default_timezone': 'Europe/Berlin',   # Standard-Zeitzone
    'use_utc_in_database': False           # Lokale Zeit in Datenbank verwenden
}

# ====================================================================
# WEBHOSTING UND DEPLOYMENT KONFIGURATION
# ====================================================================

# Webhosting-spezifische Konfiguration
webhosting_config = {
    'use_sys_exit': False,               # Kein sys.exit() für mod_python
    'buffer_output': True,               # Output-Buffering
    'minimal_imports': True,             # Minimale Imports für Performance
    'fast_fail_on_connection': True,     # Schnell fehlschlagen bei Verbindungsproblemen
    'reduced_logging': True,             # Reduziertes Logging
    'cgi_compatible': True,              # CGI-Header kompatibel
    'apache_mod_python': True,           # Optimiert für mod_python
    'max_script_runtime': 45             # Maximale Laufzeit in Sekunden
}

# Datenbank-spezifische Optimierungen
database_config = {
    'use_connection_pooling': False,        # Kein Pooling für CronJob
    'batch_insert_size': 50,               # Batch-Größe für Inserts
    'transaction_isolation': 'READ_COMMITTED',  # Isolation-Level
    'auto_reconnect': True,                # Auto-Reconnect bei DB-Problemen
    'query_timeout': 15,                   # Query-Timeout
    'connection_charset': 'utf8mb4',       # Charset für Umlaute
    'enable_stored_procedures': True,      # Stored Procedures verwenden
    'log_database_operations': True,       # Datenbankoperationen loggen
    'retry_failed_writes': True,           # Fehlgeschlagene Schreibvorgänge wiederholen
    'retry_count': 2,                      # Anzahl Wiederholungsversuche
    'connection_pool_size': 1,             # Einzelverbindung für CronJob
    'optimize_for_read': False             # Optimiert für Schreibvorgänge
}

# ====================================================================
# ERWARTETE WERTE UND REFERENZEN
# ====================================================================

# Erwartete Werte basierend auf Ihren Systemen
expected_values = {
    'schwimmer_system': {
        'system_name': 'Schwimmer',
        'software_version': 'V:2.06',
        'typical_chlorine': 0.60,
        'typical_ph': 7.20,
        'typical_redox': 750,
        'typical_temperature': 23.0
    },
    'system2': {
        'system_name': 'System2',
        'software_version': 'V:2.06',  # Anpassen wenn bekannt
        'typical_chlorine': 0.65,      # Anpassen wenn bekannt
        'typical_ph': 7.25,            # Anpassen wenn bekannt
        'typical_redox': 760,          # Anpassen wenn bekannt
        'typical_temperature': 24.0    # Anpassen wenn bekannt
    },
    'common_ranges': {
        'chlorine_range': '0.00-2.00 mg/l',
        'ph_range': '6.5-8.0',
        'redox_range': '600-850 mV',
        'temperature_range': '15-35 °C',
        'dosing_range': '0-100 %'
    },
    'normal_operation_modes': [0, 1, 2, 13]  # Hand, Auto, Aus, Standby
}

# ====================================================================
# FORMATIERUNG UND ANZEIGE
# ====================================================================

# Display-Konfiguration
display_config = {
    'decimal_places_ph': 2,              # pH auf 2 Stellen
    'decimal_places_chlorine': 2,        # Chlor auf 2 Stellen
    'decimal_places_redox': 0,           # Redox ganze Zahlen
    'decimal_places_temperature': 1,     # Temperatur auf 1 Stelle
    'decimal_places_dosing': 1,          # Dosierung auf 1 Stelle
    'use_german_locale': True,           # Deutsche Zahlenformate
    'date_format': '%Y-%m-%d %H:%M:%S',  # Datum-Format
    'compact_output': True,              # Kompakte Ausgabe für CronJob
    'show_units': True,                  # Einheiten anzeigen
    'color_coding': False,               # Farbkodierung aus für CronJob
    'use_emojis': True,                  # Emojis für Status-Anzeigen
    'max_line_length': 120               # Maximale Zeilenlänge
}

# ====================================================================
# DATENBANK-SCHEMA MAPPING
# ====================================================================

# Schema-Mapping für svfd_schedule Datenbank
database_schema_mapping = {
    'main_table': 'depolox_measurements',
    'systems_table': 'depolox_systems',
    'status_table': 'depolox_system_status',
    'error_logs_table': 'depolox_error_logs',
    'system_info_table': 'depolox_system_info',
    'measurement_types_table': 'depolox_measurement_types',
    'latest_view': 'v_latest_depolox_measurements',
    'health_view': 'v_depolox_system_health',
    'stored_procedure': 'sp_insert_depolox_measurement_batch'
}

# ====================================================================
# QUALITY ASSURANCE UND MONITORING
# ====================================================================

# Quality Assurance Checks
qa_checks = {
    'validate_modbus_connection': True,   # Modbus-Verbindung vor DB-Ops prüfen
    'validate_data_before_insert': True,  # Daten vor DB-Insert validieren
    'check_database_schema': False,       # Schema-Check aus für Performance
    'verify_stored_procedures': False,    # Stored Procedure Existenz prüfen
    'test_database_connectivity': True,   # DB-Konnektivität testen
    'log_validation_errors': True,       # Validierungsfehler protokollieren
    'cross_system_validation': True,     # Werte zwischen Systemen vergleichen
    'detect_anomalies': True             # Anomalieerkennung aktivieren
}

# Monitoring und Alerting
monitoring_config = {
    'enable_performance_monitoring': True,     # Performance-Metriken sammeln
    'track_database_performance': True,        # DB-Performance überwachen
    'log_execution_times': True,               # Ausführungszeiten loggen
    'alert_on_slow_operations': True,          # Warnung bei langsamen Ops
    'slow_operation_threshold_seconds': 20,    # Schwellenwert langsame Ops
    'enable_health_checks': True,              # Gesundheitschecks aktivieren
    'health_check_frequency_minutes': 60,      # Gesundheitscheck-Frequenz
    'dual_system_comparison': True,            # Systeme miteinander vergleichen
    'anomaly_detection_threshold': 0.5,        # Schwellenwert Anomalieerkennung
    'trend_analysis_enabled': True             # Trend-Analyse aktivieren
}

# ====================================================================
# VERSION UND METADATA
# ====================================================================

# Version und Metadata
version_info = {
    'config_version': '1.3',
    'script_version': '1.3',
    'target_system': 'Dual Depolox Pool E 700 P Systems',
    'deployment': 'Webhosting CronJob - Dual System with Full Database Integration',
    'creation_date': '2025-07-05',
    'last_updated': '2025-07-06',
    'based_on': 'Bewährte Solar-Script Architektur mit Dual-System-Erweiterung',
    'status': 'PRODUCTION_READY_DUAL_SYSTEM',
    'tested_with': 'Schwimmer System + System2',
    'database_schema': 'depolox_* tables in svfd_schedule',
    'cron_schedule': '*/5 * * * *',  # Alle 5 Minuten
    'database_integration': 'Full Integration with Stored Procedures + Error Tracking',
    'dual_system_support': True,
    'max_systems_supported': 2,
    'modbus_library': 'pyModbusTCP',
    'database_library': 'PyMySQL',
    'python_version_min': '3.6',
    'features': [
        'Dual Depolox System Support',
        'Automatic Byte-Order Detection', 
        'Database Integration with Stored Procedures',
        'Error Tracking and Alarm Management',
        'Performance Monitoring',
        'Water Quality Analysis',
        'System Health Monitoring'
    ]
}

# Lizenz und Urheberrecht
license_info = {
    'license': 'Proprietary',
    'copyright': '2025',
    'author': 'Pool Management System',
    'contact': 'system@pool-management.local',
    'usage_restrictions': 'For authorized pool monitoring systems only'
}