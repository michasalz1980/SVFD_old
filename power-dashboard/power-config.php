<?php
/**
 * Freibad Dabringhausen - Stromdaten Monitoring System
 * Konfigurationsdatei - Produktions-Version
 * 
 * Version: 1.1.3
 * Erstellt: 24.06.2025
 * Update: 26.06.2025 - Energiewerte hinzugefügt, Gerätestatus optimiert, berechneter Monatsertrag
 */

// =============================================================================
// SYSTEM-INFORMATIONEN
// =============================================================================

/**
 * Aktuelle Version des Systems
 */
function getSystemVersion() {
    return '1.1.3';
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
    $cacheFile = __DIR__ . '/first_power_measurement.cache';
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
            SELECT MIN(datetime) as first_date 
            FROM {$db_config['table']}
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['first_date']) {
            $firstDate = $result['first_date'];
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
    // Datenbank-Verbindung
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',
        'password' => 'rq*6X4s82',
        'database' => 'svfd_schedule',
        'charset' => 'utf8mb4',
        'table' => 'ffd_power_monitoring'
    ],

    // API KONFIGURATION
    'api' => [
        'time_intervals' => [
            '1h' => '1 HOUR',
            '6h' => '6 HOUR', 
            '24h' => '24 HOUR',
            '7d' => '7 DAY',
            '30d' => '30 DAY',
            '1y' => '365 DAY'
        ],
        'default_range' => '1h',
        'max_data_points' => [
            '1h' => 1000,
            '6h' => 1000,
            '24h' => 1000,
            '7d' => 2016,
            '30d' => 2880,
            '1y' => 8760
        ]
    ],

    // DASHBOARD KONFIGURATION
    'dashboard' => [
        'auto_refresh_interval' => 30000,  // 30 Sekunden
        'title' => 'Freibad Dabringhausen',
        'subtitle' => 'Stromdaten-Monitoring System',
        'decimal_places' => [
            'current_feed_total' => 0,
            'current_feed_l1' => 0,
            'current_feed_l2' => 0,
            'current_feed_l3' => 0,
            'temperature' => 1,
            'device_status' => 0,
            'operation_status' => 0,
            'total_feed_wh' => 0,
            'monthly_feed_kwh' => 3,
            'daily_feed_wh' => 0
        ]
    ],

    // ALARM- UND WARNSCHWELLENWERTE
    'alerts' => [
        'power' => [
            'warning_low' => 100,           // Warnung unter 100W
            'warning_high' => 40000,        // Warnung über 40kW
            'critical_high' => 50000        // Kritisch über 50kW
        ],
        'temperature' => [
            'warning_high' => 60.0,         // Warnung über 60°C
            'critical_high' => 80.0,        // Kritisch über 80°C
            'warning_low' => -10.0,         // Warnung unter -10°C
            'critical_low' => -20.0         // Kritisch unter -20°C
        ],
        'phase_balance' => [
            'warning_percent' => 15.0,      // Warnung bei >15% Unbalance
            'critical_percent' => 25.0      // Kritisch bei >25% Unbalance
        ],
        'system' => [
            'max_error_rate' => 5,          // Max. 5% Fehlerrate
            'max_data_age_minutes' => 10,   // Daten älter als 10 Min = Warnung
            'attention_age_minutes' => 5    // Achtung ab 5 Minuten
        ],
        'energy' => [
            'daily_minimum_wh' => 1000,     // Warnung bei <1kWh täglich
            'monthly_minimum_kwh' => 50.0   // Warnung bei <50kWh monatlich
        ]
    ],

    // EINHEITEN UND FORMATIERUNG
    'units' => [
        'current_feed_total' => ['symbol' => 'W', 'name' => 'Watt'],
        'current_feed_l1' => ['symbol' => 'W', 'name' => 'Watt'],
        'current_feed_l2' => ['symbol' => 'W', 'name' => 'Watt'],
        'current_feed_l3' => ['symbol' => 'W', 'name' => 'Watt'],
        'temperature' => ['symbol' => '°C', 'name' => 'Grad Celsius'],
        'device_status' => ['symbol' => '', 'name' => 'Status'],
        'operation_status' => ['symbol' => '', 'name' => 'Betriebsstatus'],
        'total_feed_wh' => ['symbol' => 'kWh', 'name' => 'Kilowattstunden'],
        'monthly_feed_kwh' => ['symbol' => 'kWh', 'name' => 'Kilowattstunden'],
        'daily_feed_wh' => ['symbol' => 'kWh', 'name' => 'Kilowattstunden']
    ],

    // CHART-KONFIGURATION
    'charts' => [
        'current_feed_total' => [
            'color' => '#667eea',
            'background' => 'rgba(102, 126, 234, 0.1)',
            'y_axis_title' => 'Gesamtleistung (W)'
        ],
        'current_feed_l1' => [
            'color' => '#3498db',
            'background' => 'rgba(52, 152, 219, 0.1)',
            'y_axis_title' => 'Leistung L1 (W)'
        ],
        'current_feed_l2' => [
            'color' => '#2ecc71',
            'background' => 'rgba(46, 204, 113, 0.1)',
            'y_axis_title' => 'Leistung L2 (W)'
        ],
        'current_feed_l3' => [
            'color' => '#e74c3c',
            'background' => 'rgba(231, 76, 60, 0.1)',
            'y_axis_title' => 'Leistung L3 (W)'
        ],
        'temperature' => [
            'color' => '#9b59b6',
            'background' => 'rgba(155, 89, 182, 0.1)',
            'y_axis_title' => 'Temperatur (°C)'
        ],
        'energy_production' => [
            'color' => '#f39c12',
            'background' => 'rgba(243, 156, 18, 0.1)',
            'y_axis_title' => 'Energie-Produktion'
        ]
    ],

    // SICHERHEITS-KONFIGURATION
    'security' => [
        'allowed_ips' => [],               // Leer = alle erlaubt
        'max_requests_per_minute' => 120,
        'log_errors' => true
    ],

    // WARTUNG UND DEBUGGING
    'maintenance' => [
        'debug_mode' => false              // Für Produktion auf false
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

/**
 * Formatiert Energiewerte (Wh -> kWh)
 */
function formatEnergyValue($wh_value, $decimals = 1) {
    $kwh = $wh_value / 1000.0;
    return number_format($kwh, $decimals, ',', '.');
}

// Erstes Messdatum einmalig ermitteln (Cache erstellen)
getFirstMeasurementDate();

?>