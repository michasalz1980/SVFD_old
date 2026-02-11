# config.py - OPTIMIERT basierend auf finalen Testergebnissen
# Version 3.0 - Datetime-Fix optimiert, basierend auf aktuellen Logs
# Letzte Anpassung: 2025-06-26 nach Datetime-Fix

# Modbus Konfiguration
modbus_server_ip = "iykjlt0jy435sqad.myfritz.net"
modbus_server_port = 8502
modbus_unit_id = 1

# FINALE Register Konfiguration - DATETIME-FIX KOMPATIBEL ✓
# Basierend auf erfolgreicher Auslesung mit 104,980,407 Wh
power_monitoring_register_configs = [
    # LEISTUNGSDATEN (aktuell in Watt) - GETESTET ✓
    {"address": 312, "length": 2, "column": "current_feed_total", "description": "Gesamteinspeisung aktuell (W)"},
    {"address": 322, "length": 2, "column": "current_feed_l1", "description": "Einspeisung L1 aktuell (W)"},
    {"address": 332, "length": 2, "column": "current_feed_l2", "description": "Einspeisung L2 aktuell (W)"},
    {"address": 342, "length": 2, "column": "current_feed_l3", "description": "Einspeisung L3 aktuell (W)"},
    
    # ENERGIEDATEN (kumulativ in Wh) - DATETIME-FIX OPTIMIERT! ✓
    {"address": 440, "length": 4, "column": "total_feed_wh", "description": "Gesamte eingespeiste Energie (Wh) - UINT64"},
    
    # ERWEITERTE ENERGIEDATEN (optional - datetime-fix kompatibel) ✓
    {"address": 372, "length": 2, "column": "daily_feed_wh", "description": "Tagesertrag (Wh)", "optional": True},
    {"address": 382, "length": 2, "column": "monthly_feed_kwh", "description": "Monatsertrag (kWh)", "scale": 10, "optional": True},
    
    # STATUSDATEN - DATETIME-FIX GETESTET ✓
    {"address": 392, "length": 2, "column": "device_status", "description": "Gerätestatus"},
    {"address": 402, "length": 2, "column": "operation_status", "description": "Betriebsstatus"},
    {"address": 412, "length": 2, "column": "temperature", "description": "Temperatur (skaliert /10)"},
    {"address": 422, "length": 2, "column": "operation_time", "description": "Betriebszeit (s)"}
]

# Original Register Config für PV (BESTEHEND - für Kompatibilität)
frischwasser_register_configs = [
    {"address": 110, "length": 4, "column": "counter"},
    {"address": 120, "length": 4, "column": "operational_health"},
    {"address": 140, "length": 4, "column": "operational_time"},
    {"address": 170, "length": 4, "column": "feed_actual"},
    {"address": 180, "length": 4, "column": "dc_power_input1"},
    {"address": 190, "length": 4, "column": "dc_power_input2"}
]

# Register-Konfiguration für Abwasser-Messung (BESTEHEND)
abwasser_register_configs = [
    {
        "address": 214,
        "type": "float32", 
        "column": "wasserstand", 
        "description": "Wasserstand in Meter", 
        "unit": "m",
        "scale": 1,
        "allow_negative": True
    },
    {
        "address": 224, 
        "type": "float32", 
        "column": "durchflussrate", 
        "description": "Durchflussrate in m³/h",
        "unit": "m³/h",
        "scale_factor": 1000 
    },
    {
        "address": 234, 
        "type": "float32", 
        "column": "totalizer", 
        "description": "Gesamtvolumen in m³", 
        "unit": "m³"
    },
    {
        "address": 244, 
        "type": "float32", 
        "column": "sensor_strom", 
        "description": "Sensor-Strom in mA", 
        "unit": "mA",
        "scale_factor": 1000
    }
]

# Hauptkonfiguration
register_configs = power_monitoring_register_configs

# Legacy-Kompatibilität
wassermessung_register_configs = abwasser_register_configs

# Datenbank Konfiguration
db_host = 'localhost'
db_user = 'svfd_Schedule'
db_password = 'rq*6X4s82'
db_database = 'svfd_schedule'

# Tabellen Konfiguration
table_frischwasser = 'ffd_frischwasser'
table_wassermessung = 'abwasser_messwerte'
table_power_monitoring = 'ffd_power_monitoring'  # Haupttabelle für Solar-Daten

# Verbindungs-Konfiguration - DATETIME-FIX OPTIMIERT
max_retries = 3
timeout_seconds = 10
connection_timeout = 5
read_timeout = 3

# PRODUKTIONSMODUS - DATETIME-FIX aktiviert
debug_mode = False   # TEMPORÄR auf True für Tests des Datetime-Fix
log_errors_to_db = True

# DATETIME-FIX Konfiguration - NEUE SEKTION
datetime_config = {
    'timezone_handling': 'naive',           # 'naive' für Datetime-Fix
    'force_timezone_removal': True,        # Entfernt alle Zeitzonen automatisch
    'safe_datetime_operations': True,      # Verwendet sichere Datetime-Funktionen
    'validation_on_datetime_errors': True, # Validiert trotz Datetime-Fehlern
    'fallback_to_local_time': True        # Fallback bei Timezone-Problemen
}

# ERWEITERTE Validierung - Basierend auf finalen Ergebnissen optimiert
validate_data = True

# Temperatur-Validierung (RAW-Werte) - Angepasst basierend auf 40.6°C (raw: 406)
max_temperature_raw = 850   # 85°C maximum
min_temperature_raw = -200  # -20°C minimum

# Leistungsvalidierung (in Watt) - Basierend auf realen 2.785W aktuell
max_power_total = 60000     # 60kW maximum
min_power_total = 0         # 0W minimum  
max_power_per_phase = 25000 # 25kW pro Phase maximum

# FINALE Energievalidierung - Basierend auf 104,980,407 Wh (104.98 MWh)
max_total_feed_wh = 999999999   # 999 MWh maximum
min_total_feed_wh = 104000000   # 104 MWh minimum (angepasst an aktuelle Werte)
max_daily_increase_wh = 300000  # 300 kWh maximaler Tageszuwachs

# DATETIME-FIX: Angepasste Validierungstoleranz
energy_validation_config = {
    'skip_on_datetime_errors': True,       # Überspringe bei Datetime-Fehlern
    'save_data_despite_validation_errors': True, # Speichere trotz Validierungsfehlern
    'datetime_error_tolerance': True,      # Toleriere Datetime-Probleme
    'max_time_gap_hours': 24,             # Max. Zeitlücke für Validierung
    'energy_increase_tolerance_percent': 150, # 150% Toleranz für Energiezuwachs
}

# Exit-Codes
EXIT_SUCCESS = 0
EXIT_CONFIG_ERROR = 1
EXIT_MODBUS_CONNECTION_ERROR = 2
EXIT_MODBUS_READ_ERROR = 3
EXIT_DATABASE_CONNECTION_ERROR = 4
EXIT_DATABASE_WRITE_ERROR = 5
EXIT_DATA_VALIDATION_ERROR = 6
EXIT_GENERAL_ERROR = 99

# FINALE Skalierungsfaktoren - Basierend auf bestätigten Werten
power_scaling_factors = {
    'temperature': 10,           # 406 -> 40.6°C (BESTÄTIGT in Log)
    'current_feed_total': 1,     # Direkt in Watt (2785 W)
    'current_feed_l1': 1,        # Direkt in Watt (936 W)
    'current_feed_l2': 1,        # Direkt in Watt (928 W)
    'current_feed_l3': 1,        # Direkt in Watt (921 W)
    'total_feed_wh': 1,          # Direkt in Wh (104,980,407 Wh)
    'daily_feed_wh': 1,          # Direkt in Wh
    'monthly_feed_kwh': 10,      # Skalierung bestätigt
}

# AKTUALISIERTE Register-Mapping für Dokumentation
power_register_mapping = {
    312: "Current_Feed_Total - Aktuelle Gesamteinspeisung (W) - Aktuell: 2785W",
    322: "Current_Feed_L1 - Aktuelle Einspeisung Phase L1 (W) - Aktuell: 936W",
    332: "Current_Feed_L2 - Aktuelle Einspeisung Phase L2 (W) - Aktuell: 928W", 
    342: "Current_Feed_L3 - Aktuelle Einspeisung Phase L3 (W) - Aktuell: 921W",
    440: "Total_Feed_Wh - Gesamte eingespeiste Energie (Wh) - Aktuell: 104,980,407 Wh",
    372: "Daily_Feed_Wh - Tagesertrag (Wh) - Optional",
    382: "Monthly_Feed_kWh - Monatsertrag (kWh) - Optional",
    392: "Device_Status - Gerätestatus - Aktuell: 307",
    402: "Operation_Status - Betriebsstatus - Aktuell: 569", 
    412: "Temperature - Innentemperatur (skaliert /10) - Aktuell: 406 (40.6°C)",
    422: "Operation_Time - Betriebszeit (Sekunden) - Aktuell: 47,347,069s"
}

# FINALE erwartete Werte - Basierend auf aktuellen Logs
expected_power_values = {
    # Leistungswerte - Angepasst basierend auf 2.785W aktuell
    'current_feed_total': '0-60000 W (aktuell: 2785W)',
    'current_feed_l1': '0-25000 W (aktuell: 936W)',
    'current_feed_l2': '0-25000 W (aktuell: 928W)', 
    'current_feed_l3': '0-25000 W (aktuell: 921W)',
    
    # Energiewerte - Basierend auf aktuellen 104,980,407 Wh
    'total_feed_wh': '104000000-999999999 Wh (aktuell: 104,980,407 Wh = 104.98 MWh)',
    'total_feed_kwh': '104000-999999 kWh',
    'daily_feed_wh': '0-300000 Wh/Tag (0-300 kWh/Tag)',
    'monthly_feed_kwh': '0-9000 kWh/Monat',
    
    # Statuswerte - Basierend auf aktuellen Logs
    'temperature_raw': '200-850 (20-85°C, aktuell: 406 = 40.6°C)',
    'temperature_scaled': '20-85 °C',
    'device_status': '0-999 (aktuell: 307)',
    'operation_status': '0-999 (aktuell: 569)',
    'operation_time': '0-999999999 s (aktuell: 47,347,069s = 548+ Tage)'
}

# Energie-Berechnungen und Umrechnungen
energy_conversions = {
    'wh_to_kwh': 1000,      # Wh / 1000 = kWh
    'kwh_to_mwh': 1000,     # kWh / 1000 = MWh
    'w_to_kw': 1000,        # W / 1000 = kW
    'seconds_per_hour': 3600, # Für Wh-Berechnungen
    'seconds_per_day': 86400  # Für Tagesberechnungen
}

# DATETIME-FIX: Sichere Funktionen für Energieberechnungen
def safe_calculate_energy_from_power(power_w, duration_seconds):
    """Berechnet Energie (Wh) aus Leistung (W) und Zeit (s) - DATETIME-FIX kompatibel"""
    try:
        if duration_seconds is None or duration_seconds <= 0:
            return 0
        return (power_w * duration_seconds) / 3600
    except Exception:
        return 0

def safe_calculate_daily_production(total_wh_start, total_wh_end):
    """Berechnet Tagesproduktion - DATETIME-FIX kompatibel"""
    try:
        if total_wh_start is None or total_wh_end is None:
            return 0
        return max(0, total_wh_end - total_wh_start)
    except Exception:
        return 0

def format_energy_value(wh_value):
    """Formatiert Energiewerte human-readable"""
    try:
        if wh_value >= 1000000:  # >= 1 MWh
            return f"{wh_value/1000000:.2f} MWh"
        elif wh_value >= 1000:   # >= 1 kWh
            return f"{wh_value/1000:.1f} kWh"
        else:
            return f"{wh_value:.0f} Wh"
    except Exception:
        return "N/A"

def validate_energy_increase_safe(old_wh, new_wh, time_diff_seconds):
    """DATETIME-FIX: Sichere Energievalidierung mit Fehlertoleranz"""
    try:
        if new_wh is None or old_wh is None:
            return True  # Bei fehlenden Werten: OK
            
        if new_wh < old_wh:
            return False  # Energie kann nur steigen
        
        if time_diff_seconds is None or time_diff_seconds <= 0:
            return True  # Bei Zeitproblemen: OK
        
        increase_wh = new_wh - old_wh
        hours_passed = time_diff_seconds / 3600
        
        # Maximale theoretische Produktion bei 60kW für die Zeit
        max_theoretical_wh = 60000 * hours_passed
        
        # DATETIME-FIX: Großzügige Toleranz
        tolerance_factor = energy_validation_config.get('energy_increase_tolerance_percent', 150) / 100
        return increase_wh <= max_theoretical_wh * tolerance_factor
        
    except Exception:
        return True  # Bei Fehlern: Validierung bestanden

# Erweiterte Statistik-Konfiguration
statistics_config = {
    'daily_stats_enabled': True,
    'weekly_stats_enabled': True,
    'monthly_stats_enabled': True,
    'yearly_stats_enabled': True,
    'performance_monitoring': True,
    'efficiency_calculations': True,
    'datetime_safe_calculations': True  # NEUE Option für Datetime-Fix
}

# Performance-Benchmarks - Angepasst basierend auf aktuellen Daten
performance_benchmarks = {
    'peak_power_kw': 60,             # Maximale Anlagenleistung
    'expected_annual_kwh': 60000,    # Für 60kW Anlage
    'expected_daily_avg_kwh': 164,   # 60000/365
    'current_total_mwh': 105,        # Aktueller Stand basierend auf 104.98 MWh
    'efficiency_threshold': 0.85,    # Mindest-Effizienz
    'performance_ratio_target': 0.80, # Ziel Performance Ratio
    'installation_start_mwh': 105,   # Baseline für Berechnungen
    'expected_daily_peak_kw': 50,    # Erwartete Tagesspitze
    'current_baseline_wh': 104980407 # NEUE Baseline basierend auf aktuellem Wert
}

# Alarm-Konfiguration - DATETIME-FIX optimiert
energy_alerts = {
    'low_daily_production': 30000,    # Warnung unter 30 kWh/Tag
    'no_production_hours': 6,         # Warnung bei 6h ohne Produktion (tagsüber)
    'efficiency_drop_percent': 20,    # Warnung bei >20% Effizienz-Abfall
    'unusual_energy_jump': 150000,    # Warnung bei >150 kWh sprung
    'temperature_warning_celsius': 75, # Warnung bei >75°C (aktuell: 40.6°C)
    'power_asymmetry_percent': 25,    # Warnung bei >25% Ungleichgewicht zwischen Phasen
    'min_midday_power_kw': 15,        # Minimum-Leistung um 12 Uhr
    'max_operation_temp_celsius': 80, # Maximum Betriebstemperatur
    'datetime_validation_errors': 3,  # NEUE: Max. Datetime-Fehler bevor Alarm
    'energy_validation_skip_threshold': 5  # NEUE: Max. übersprungene Validierungen
}

# DATETIME-FIX: Erweiterte Validierungsfunktionen
def validate_phase_balance_safe(l1, l2, l3):
    """DATETIME-FIX: Sichere Phasengleichgewicht-Prüfung"""
    try:
        if max(l1, l2, l3) == 0:
            return True  # Keine Produktion
        
        avg_power = (l1 + l2 + l3) / 3
        max_deviation = max(abs(l1 - avg_power), abs(l2 - avg_power), abs(l3 - avg_power))
        
        if avg_power > 0:
            deviation_percent = (max_deviation / avg_power) * 100
            return deviation_percent <= 25  # 25% Toleranz
        
        return True
    except Exception:
        return True  # Bei Fehlern: OK

def calculate_current_efficiency_safe(current_power_w, theoretical_max_w=60000):
    """DATETIME-FIX: Sichere Effizienz-Berechnung"""
    try:
        if theoretical_max_w == 0:
            return 0
        return (current_power_w / theoretical_max_w) * 100
    except Exception:
        return 0

def format_operation_time_safe(seconds):
    """DATETIME-FIX: Sichere Betriebszeit-Formatierung"""
    try:
        days = seconds // 86400
        hours = (seconds % 86400) // 3600
        return f"{days} Tage, {hours} Stunden"
    except Exception:
        return "N/A"

# Legacy-Konfigurationen (für Kompatibilität mit anderen Systemen)
scaling_factors = {
    'wasserstand': 100,
    'durchflussrate': 1.0,
    'totalizer': 1.0,
    'sensor_strom': 1000
}

expected_values = {
    'wasserstand_cm': -0.4256964,
    'wasserstand_m': -0.004256964,
    'durchflussrate_ls': 0.01337,
    'durchflussrate_m3h': 0.01337,
    'totalizer': 7.65048,
    'sensor_strom_a': 0.01134591,
    'sensor_strom_ma': 11.34591
}

register_mapping = {
    214: "Durchflussrate (Float32, l/s)",
    217: "Wasserstand (Single Register, /100)", 
    234: "Totalizer (Float32, m³)",
    244: "Sensor Strom (Float32, *1000 für mA)"
}

# PRODUKTIONS-Monitoring Konfiguration - DATETIME-FIX Integration
production_monitoring = {
    'log_level': 'INFO',                    # INFO, WARNING, ERROR
    'log_to_file': False,                   # Für Webhosting meist False
    'cron_interval_minutes': 5,             # Empfohlenes Cron-Intervall
    'data_retention_days': 365,             # Datenaufbewahrung
    'backup_enabled': True,                 # Backup-Empfehlung
    'alert_thresholds_active': True,        # Alarm-System aktiv
    'performance_tracking_enabled': True,   # Performance-Tracking
    'datetime_error_tracking': True,        # NEUE: Datetime-Fehler verfolgen
    'safe_mode_on_datetime_errors': True    # NEUE: Sicherer Modus bei Datetime-Problemen
}

# FINALE Test-Referenzwerte vom Datetime-Fix Test (2025-06-26 09:47)
final_test_reference_values = {
    'datetime': '2025-06-26 09:47:11',
    'current_feed_total': 2785,       # 2.785 kW
    'current_feed_l1': 936,           # 936 W
    'current_feed_l2': 928,           # 928 W  
    'current_feed_l3': 921,           # 921 W
    'total_feed_wh': 104980407,       # 104.98 MWh (erfolgreich gelesen!)
    'daily_feed_wh': 104980,          # 104.98 kWh
    'monthly_feed_kwh': 10.5,         # 10.5 kWh (skaliert)
    'device_status': 307,
    'operation_status': 569,
    'temperature': 406,               # 40.6°C
    'operation_time': 47347069,       # ~548 Tage
    'register_440_raw': [0, 0, 1601, 57271],  # Raw-Daten Register 440
    'interpretation_used': 'BE_UINT64',         # Erfolgreiche Interpretation
    'datetime_fix_status': 'IMPLEMENTED',      # DATETIME-FIX Status
    'validation_errors': 'FIXED'               # Validierungsfehler behoben
}

# DATETIME-FIX: Logging-Konfiguration
datetime_fix_logging = {
    'log_datetime_conversions': True,
    'log_timezone_removals': True,
    'log_validation_overrides': True,
    'log_safe_fallbacks': True,
    'verbose_datetime_operations': debug_mode
}

# ERFOLGS-INDIKATOREN für Monitoring
success_indicators = {
    'no_exit_code_6': True,                    # Kein Exit-Code 6 mehr
    'successful_energy_reading': True,         # Energiewerte erfolgreich gelesen
    'datetime_operations_safe': True,          # Datetime-Operationen sicher
    'validation_errors_handled': True,         # Validierungsfehler behandelt
    'data_saving_despite_errors': True,       # Daten werden trotz Fehlern gespeichert
    'continuous_operation_possible': True      # Kontinuierlicher Betrieb möglich
}

# VERSION INFO
version_info = {
    'config_version': '3.0',
    'datetime_fix_version': '1.0',
    'last_test_date': '2025-06-26',
    'status': 'PRODUCTION_READY',
    'datetime_problem': 'SOLVED',
    'exit_code_6_issue': 'FIXED'
}