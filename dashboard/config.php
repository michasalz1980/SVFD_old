<?php
/**
 * Freibad Dabringhausen - Abwasser Monitoring System
 * Konfigurationsdatei
 * 
 * Version: 1.0.0
 * Erstellt: 22.06.2025
 */

// =============================================================================
// SYSTEM-INFORMATIONEN
// =============================================================================

/**
 * Aktuelle Version des Systems
 */
function getSystemVersion() {
    $versionFile = __DIR__ . '/VERSION';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return '1.0.0'; // Fallback
}

/**
 * Erstes Messdatum ermitteln und cachen
 */
function getFirstMeasurementDate() {
    static $cachedDate = null;
    
    if ($cachedDate !== null) {
        return $cachedDate;
    }
    
    // Versuche aus Cache-Datei zu lesen
    $cacheFile = __DIR__ . '/first_measurement.cache';
    if (file_exists($cacheFile)) {
        $cachedDate = trim(file_get_contents($cacheFile));
        return $cachedDate;
    }
    
    // Aus Datenbank ermitteln
    try {
        $db_config = getConfig('database');
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']}";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $stmt = $pdo->prepare("
            SELECT MIN(timestamp) as first_date 
            FROM {$db_config['table']} 
            WHERE modbus_status = 'OK'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['first_date']) {
            $firstDate = $result['first_date'];
            
            // In Cache-Datei speichern
            file_put_contents($cacheFile, $firstDate);
            $cachedDate = $firstDate;
            
            return $firstDate;
        }
    } catch (Exception $e) {
        error_log("Fehler beim Ermitteln des ersten Messdatums: " . $e->getMessage());
    }
    
    // Fallback: Aktuelles Datum
    $fallbackDate = date('Y-m-d H:i:s');
    file_put_contents($cacheFile, $fallbackDate);
    $cachedDate = $fallbackDate;
    
    return $fallbackDate;
}

/**
 * System-Informationen für Frontend
 */
function getSystemInfo() {
    $firstDate = getFirstMeasurementDate();
    $firstDateTime = new DateTime($firstDate);
    $now = new DateTime();
    $interval = $now->diff($firstDateTime);
    
    return [
        'version' => getSystemVersion(),
        'first_measurement' => [
            'raw' => $firstDate,
            'formatted' => $firstDateTime->format('d.m.Y, H:i'),
            'days_since' => $interval->days,
            'years' => $interval->y,
            'months' => $interval->m,
            'days' => $interval->d
        ],
        'installation_info' => sprintf(
            'System läuft seit %d Tagen (%s)',
            $interval->days,
            $firstDateTime->format('d.m.Y')
        )
    ];
}

// =============================================================================
// HAUPTKONFIGURATION
// =============================================================================

$config = [
    // Datenbank-Verbindung (IHRE ORIGINALEN WERTE)
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',     // ← Ihre originalen Werte
        'password' => 'REDACTED',        // ← Ihre originalen Werte
        'database' => 'svfd_schedule',     // ← Ihre originalen Werte
        'charset' => 'utf8',              // ← Geändert von utf8mb4 zu utf8
        'table' => 'abwasser_messwerte'
    ],

    // =============================================================================
    // API KONFIGURATION
    // =============================================================================
    
    'api' => [
        // Erlaubte Zeiträume für Datenabfrage
        'time_intervals' => [
            '1h' => '1 HOUR',
            '6h' => '6 HOUR', 
            '24h' => '24 HOUR',
            '7d' => '7 DAY',
            '30d' => '30 DAY',    // 1 Monat
            '1y' => '365 DAY'     // 1 Jahr
        ],
        
        // Standard-Zeitraum falls nicht angegeben
        'default_range' => '1h',
        
        // Maximale Anzahl Datenpunkte pro Zeitraum (Performance-Optimierung)
        'max_data_points' => [
            '1h' => 1000,     // Alle Datenpunkte
            '6h' => 1000,     // Alle Datenpunkte  
            '24h' => 1000,    // Alle Datenpunkte
            '7d' => 2016,     // Alle 5 Min (7*24*12)
            '30d' => 2880,    // Alle 15 Min (30*24*4) 
            '1y' => 8760      // Stündlich (365*24)
        ],
        
        // Daten-Sampling für bessere Performance
        'sampling_intervals' => [
            '1h' => 'NONE',           // Alle Datenpunkte
            '6h' => 'NONE',           // Alle Datenpunkte
            '24h' => 'NONE',          // Alle Datenpunkte  
            '7d' => '5_MINUTE',       // Alle 5 Minuten
            '30d' => '15_MINUTE',     // Alle 15 Minuten
            '1y' => 'HOURLY'          // Stündlich
        ],
        
        // Cache-Dauer für API-Antworten (Sekunden)
        'cache_duration' => 30
    ],

    // =============================================================================
    // DASHBOARD KONFIGURATION
    // =============================================================================
    
    'dashboard' => [
        // Automatische Aktualisierung (Millisekunden)
        'auto_refresh_interval' => 30000,  // 30 Sekunden
        
        // Titel und Beschreibung
        'title' => 'Freibad Dabringhausen',
        'subtitle' => 'Abwasser-Monitoring System',
        
        // Anzeigeoptionen
        'show_charts' => true,
        'show_alerts' => true,
        'show_statistics' => true,
        'show_data_table' => true,
        
        // Dezimalstellen für Anzeige
        'decimal_places' => [
            'wasserstand' => 1,         // 0.1 cm (geändert von 3 für Meter)
            'durchflussrate' => 3,      // 0.001 l/s
            'totalizer' => 2,           // 0.01 m³
            'sensor_strom' => 1,        // 0.1 mA
            'consumption' => 3          // 0.001 m³
        ],
        
        // Tabellen-Konfiguration
        'data_table' => [
            'default_entries_per_page' => 25,
            'max_entries_per_page' => 1000,
            'available_page_sizes' => [10, 25, 50, 100],
            'default_sort_column' => 0,    // 0 = timestamp
            'default_sort_direction' => 'desc', // Neueste zuerst
            'enable_export' => true,
            'export_formats' => ['csv'],
            'max_export_rows' => 10000
        ]
    ],

    // =============================================================================
    // ALARM- UND WARNSCHWELLENWERTE
    // =============================================================================
    
    'alerts' => [
        // Wasserstand-Warnungen (in Zentimeter - Daten bereits in cm)
        'wasserstand' => [
            'critical_low' => -15.0,    // Kritisch niedrig (cm)
            'warning_low' => -10.0,     // Warnung niedrig (cm)
            'warning_high' => 50.0,     // Warnung hoch (cm)
            'critical_high' => 100.0    // Kritisch hoch (cm)
        ],
        
        // Durchfluss-Warnungen
        'durchflussrate' => [
            'warning_high' => 1.0,      // Warnung bei > 1.0 l/s
            'critical_high' => 2.0,     // Kritisch bei > 2.0 l/s
            'warning_low' => 0.0,       // Warnung bei <= 0 l/s (kein Fluss)
            'max_change_rate' => 0.5    // Max. Änderung pro Minute (l/s)
        ],
        
        // Sensor-Strom-Warnungen (4-20mA Standard)
        'sensor_strom' => [
            'critical_low' => 3.5,      // Kritisch < 3.5 mA
            'warning_low' => 4.0,       // Warnung < 4 mA
            'warning_high' => 20.0,     // Warnung > 20 mA
            'critical_high' => 22.0     // Kritisch > 22 mA
        ],
        
        // Totalizer-Warnungen
        'totalizer' => [
            'negative_consumption' => true,  // Warnung bei negativem Verbrauch
            'high_consumption_rate' => 10.0  // Warnung bei > 10 m³/h Verbrauch
        ],
        
        // System-Gesundheit
        'system' => [
            'max_error_rate' => 10,         // Max. 10% Fehlerrate
            'max_data_age_minutes' => 5,    // Daten älter als 5 Min = Warnung
            'attention_age_minutes' => 2,   // Achtung ab 2 Minuten
            'consecutive_errors' => 3,      // Warnung nach 3 aufeinanderfolgenden Fehlern
            'show_relative_time' => true    // Zeige "vor X Minuten" statt nur Timestamp
        ]
    ],

    // =============================================================================
    // EINHEITEN UND FORMATIERUNG
    // =============================================================================
    
    'units' => [
        'wasserstand' => [
            'symbol' => 'cm',              // Geändert von 'm' zu 'cm'
            'name' => 'Zentimeter',        // Geändert von 'Meter' zu 'Zentimeter'
            'conversion_factor' => 100     // Faktor für Umrechnung von Meter zu cm
        ],
        'durchflussrate' => [
            'symbol' => 'l/s',
            'name' => 'Liter pro Sekunde', 
            'conversion_factor' => 1
        ],
        'totalizer' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter',
            'conversion_factor' => 1
        ],
        'sensor_strom' => [
            'symbol' => 'mA',
            'name' => 'Milliampere',
            'conversion_factor' => 1
        ],
        'consumption' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter',
            'conversion_factor' => 1
        ]
    ],

    // =============================================================================
    // CHART-KONFIGURATION
    // =============================================================================
    
    'charts' => [
        'wasserstand' => [
            'color' => '#3498db',
            'background' => 'rgba(52, 152, 219, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Wasserstand (cm)',    // Geändert von (m) zu (cm)
            'allow_negative' => true
        ],
        'durchflussrate' => [
            'color' => '#2ecc71',
            'background' => 'rgba(46, 204, 113, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Durchfluss (l/s)',
            'min_value' => 0
        ],
        'totalizer' => [
            'color' => '#9b59b6',
            'background' => 'rgba(155, 89, 182, 0.1)',
            'show_zero_line' => false,
            'y_axis_title' => 'Volumen (m³)'
        ],
        'consumption' => [
            'color' => '#e67e22',
            'background' => 'rgba(230, 126, 34, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Verbrauch (m³)'
        ],
        'sensor_strom' => [
            'color' => '#f39c12',
            'background' => 'rgba(243, 156, 18, 0.1)',
            'show_zero_line' => false,
            'y_axis_title' => 'Strom (mA)',
            'min_value' => 0,
            'max_value' => 25
        ]
    ],

    // =============================================================================
    // ERWARTETE WERTE (FÜR VALIDIERUNG)
    // =============================================================================
    
    'expected_values' => [
        // Normale Betriebswerte (für Kalibrierung) - Werte bereits in cm
        'wasserstand' => [
            'normal_min' => -50.0,         // -50 cm
            'normal_max' => 50.0,          // 50 cm
            'typical' => -0.4257           // -0.4257 cm
        ],
        'durchflussrate' => [
            'normal_min' => 0.0,
            'normal_max' => 1.0,
            'typical' => 0.01337
        ],
        'totalizer' => [
            'normal_min' => 0.0,
            'normal_max' => 99999.0,
            'typical' => 7.65048
        ],
        'sensor_strom' => [
            'normal_min' => 4.0,
            'normal_max' => 20.0,
            'typical' => 11.345
        ]
    ],

    // =============================================================================
    // SICHERHEITS-KONFIGURATION
    // =============================================================================
    
    'security' => [
        // IP-Whitelist (leer = alle erlaubt)
        'allowed_ips' => [
            // '192.168.1.100',
            // '10.0.0.50'
        ],
        
        // Rate Limiting
        'max_requests_per_minute' => 60,
        
        // SQL-Injection-Schutz
        'enable_prepared_statements' => true,
        
        // CORS-Einstellungen
        'cors_origins' => ['*'], // In Produktion spezifischer setzen
        
        // Fehler-Logging
        'log_errors' => true,
        'log_file' => '/var/log/abwasser_dashboard.log'
    ],

    // =============================================================================
    // REGISTER-MAPPING (DOKUMENTATION)
    // =============================================================================
    
    'modbus_registers' => [
        'wasserstand' => [
            'address' => 224,
            'type' => 'single',
            'description' => 'Wasserstand in Zentimeter',
            'scale' => 100
        ],
        'durchflussrate' => [
            'address' => 214,
            'type' => 'float32',
            'description' => 'Durchflussrate in l/s'
        ],
        'totalizer' => [
            'address' => 234,
            'type' => 'float32',
            'description' => 'Gesamtvolumen in m³'
        ],
        'sensor_strom' => [
            'address' => 244,
            'type' => 'float32',
            'description' => 'Sensor-Strom in mA',
            'scale_factor' => 1000
        ]
    ],

    // =============================================================================
    // WARTUNG UND DEBUGGING
    // =============================================================================
    
    'maintenance' => [
        // Debug-Modus (mehr Logs, Fehlerdetails)
        'debug_mode' => false,
        
        // Datenbereinigung
        'cleanup_old_data_days' => 365,  // Daten älter als 1 Jahr löschen
        
        // Wartungs-Fenster
        'maintenance_hours' => [2, 3],   // 02:00-03:59 Uhr
        
        // Backup-Konfiguration
        'backup_enabled' => true,
        'backup_retention_days' => 30
    ]
];

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

/**
 * Gibt einen Konfigurationswert zurück
 */
function getConfig($key, $default = null) {
    global $config;
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Prüft ob Debug-Modus aktiviert ist
 */
function isDebugMode() {
    return getConfig('maintenance.debug_mode', false);
}

/**
 * Gibt alle Zeitintervalle zurück
 */
function getTimeIntervals() {
    return getConfig('api.time_intervals', []);
}

/**
 * Gibt Alarm-Schwellenwerte für einen Sensor zurück
 */
function getAlertThresholds($sensor) {
    return getConfig("alerts.{$sensor}", []);
}

/**
 * Formatiert einen Wert gemäß Konfiguration
 */
function formatValue($sensor, $value) {
    $decimals = getConfig("dashboard.decimal_places.{$sensor}", 2);
    return number_format($value, $decimals, ',', '.');
}

/**
 * Gibt die Einheit für einen Sensor zurück
 */
function getUnit($sensor) {
    return getConfig("units.{$sensor}.symbol", '');
}

// Erstes Messdatum einmalig ermitteln (Cache erstellen)
getFirstMeasurementDate();

// Konfiguration validieren
if (isDebugMode()) {
    // Grundlegende Konfigurationsprüfung
    $required_keys = [
        'database.host',
        'database.username', 
        'database.password',
        'database.database'
    ];
    
    foreach ($required_keys as $key) {
        if (!getConfig($key)) {
            error_log("WARNUNG: Fehlende Konfiguration für '{$key}'");
        }
    }
}

?>