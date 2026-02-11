<?php
/**
 * Freibad Dabringhausen - Dashboard Konfiguration
 * Konfigurationsdatei für Tagesprotokoll und Wasserqualität Dashboard
 * 
 * Version: 2.0.0
 * Erstellt: 01.07.2025
 */

// =============================================================================
// SYSTEM-INFORMATIONEN
// =============================================================================

/**
 * Aktuelle Version des Dashboard-Systems
 */
function getSystemVersion() {
    return '2.0.0';
}

/**
 * System-Informationen für Frontend
 */
function getSystemInfo() {
    return [
        'version' => getSystemVersion(),
        'php_version' => PHP_VERSION,
        'timezone' => date_default_timezone_get(),
        'created' => '2025-07-01',
        'description' => 'Freibad Dabringhausen Dashboard System'
    ];
}

// =============================================================================
// HAUPTKONFIGURATION
// =============================================================================

$config = [
    // Datenbank-Verbindung
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',     // ← Ihre originalen Werte
        'password' => 'REDACTED',        // ← Ihre originalen Werte
        'database' => 'svfd_schedule',     // ← Ihre originalen Werte
        'charset' => 'utf8',
        'tables' => [
            'tagesprotokoll' => 'Tagesprotokoll',
            'wasserqualitaet' => 'Wasserqualitaet'
        ]
    ],

    // =============================================================================
    // API KONFIGURATION
    // =============================================================================
    
    'api' => [
        // Erlaubte Zeiträume für Datenabfrage
        'time_intervals' => [
            '7d' => '7 DAY',
            '30d' => '30 DAY',
            '90d' => '90 DAY',    // 3 Monate
            '1y' => '365 DAY',    // 1 Jahr
            'all' => 'ALL'        // Alle Daten
        ],
        
        // Standard-Zeitraum falls nicht angegeben
        'default_range' => '7d',
        
        // Maximale Anzahl Datenpunkte pro Abfrage (Performance-Schutz)
        'max_data_points' => [
            'tagesprotokoll' => 1000,
            'wasserqualitaet' => 5000
        ],
        
        // Cache-Dauer für API-Antworten (Sekunden)
        'cache_duration' => 300, // 5 Minuten
        
        // Rate Limiting
        'rate_limit' => [
            'enabled' => true,
            'max_requests_per_minute' => 60
        ]
    ],

    // =============================================================================
    // DASHBOARD KONFIGURATION
    // =============================================================================
    
    'dashboard' => [
        // Automatische Aktualisierung (Millisekunden)
        'auto_refresh_interval' => 300000,  // 5 Minuten
        
        // Titel und Beschreibung
        'title' => 'Freibad Dabringhausen',
        'subtitle' => 'Monitoring Dashboard',
        
        // Anzeigeoptionen
        'show_charts' => true,
        'show_statistics' => true,
        'enable_export' => false, // Für zukünftige Erweiterungen
        
        // Dezimalstellen für Anzeige
        'decimal_places' => [
            'temperatur' => 1,       // 0.1 °C
            'ph_wert' => 2,         // 0.01 pH
            'chlor' => 2,           // 0.01 mg/l
            'redox' => 0,           // 1 mV
            'zaehlerstand' => 0     // Ganze Zahlen
        ],
        
        // Chart-Farben
        'chart_colors' => [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'success' => '#2ecc71',
            'warning' => '#f39c12',
            'danger' => '#e74c3c',
            'info' => '#3498db'
        ]
    ],

    // =============================================================================
    // TAGESPROTOKOLL KONFIGURATION
    // =============================================================================
    
    'tagesprotokoll' => [
        // Spalten-Mapping
        'columns' => [
            'datum' => 'Datum',
            'besucher' => 'Tagesbesucherzahl',
            'lufttemperatur' => 'Lufttemperatur',
            'temp_mzb' => 'Temperatur_MZB',
            'temp_nsb' => 'Temperatur_NSB',
            'temp_kkb' => 'Temperatur_KKB',
            'zaehler_wasser' => 'Zaehlerstand_Wasserleitungsnetz',
            'zaehler_abwasser' => 'Zaehlerstand_Abwasser',
            'wetter_sonnig' => 'Wetter_S',
            'wetter_heiter' => 'Wetter_H',
            'wetter_bewoelkt' => 'Wetter_B',
            'wetter_regen' => 'Wetter_R',
            'wetter_gewitter' => 'Wetter_G',
            'unterzeichner' => 'Protokollunterzeichner',
            'bemerkungen' => 'Bemerkungen'
        ],
        
        // Wetter-Kategorien für Charts
        'wetter_labels' => [
            'sonnig' => '☀️ Sonnig',
            'heiter' => '⛅ Heiter', 
            'bewoelkt' => '☁️ Bewölkt',
            'regen' => '🌧️ Regen',
            'gewitter' => '⛈️ Gewitter'
        ],
        
        // Validierungsregeln
        'validation' => [
            'min_besucher' => 0,
            'max_besucher' => 5000,
            'min_temperatur' => -10,
            'max_temperatur' => 50,
            'min_zaehlerstand' => 0
        ]
    ],

    // =============================================================================
    // WASSERQUALITÄT KONFIGURATION
    // =============================================================================
    
    'wasserqualitaet' => [
        // Spalten-Mapping
        'columns' => [
            'datum' => 'Datum',
            'uhrzeit' => 'Uhrzeit',
            'becken' => 'Becken',
            'cl_frei' => 'Cl_frei',
            'cl_gesamt' => 'Cl_gesamt',
            'ph_wert' => 'pH_Wert',
            'redox_wert' => 'Redox_Wert',
            'wasserhaerte' => 'Wasserhaerte'
        ],
        
        // Becken-Definitionen
        'becken' => [
            'MZB' => [
                'name' => 'Mehrzweckbecken',
                'color' => '#3498db',
                'icon' => '🏊‍♂️'
            ],
            'NSB' => [
                'name' => 'Nichtschwimmerbecken', 
                'color' => '#2ecc71',
                'icon' => '🧒'
            ],
            'KKB' => [
                'name' => 'Kleinkinderbecken',
                'color' => '#e74c3c', 
                'icon' => '👶'
            ]
        ],
        
        // Grenzwerte für Validierung und Alarme
        'grenzwerte' => [
            'ph' => [
                'min' => 6.5,
                'max' => 7.6,
                'optimal_min' => 7.0,
                'optimal_max' => 7.4
            ],
            'chlor_frei' => [
                'min' => 0.3,
                'max' => 0.6,
                'critical_max' => 1.0
            ],
            'redox' => [
                'min' => 750,
                'max' => 850,
                'optimal_min' => 780,
                'optimal_max' => 820
            ]
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
        
        // CORS-Einstellungen
        'cors_origins' => ['*'], // In Produktion spezifischer setzen
        
        // Fehler-Logging
        'log_errors' => true,
        'log_file' => '/var/log/freibad_dashboard.log',
        
        // SQL-Injection-Schutz
        'enable_prepared_statements' => true
    ],

    // =============================================================================
    // PERFORMANCE-KONFIGURATION
    // =============================================================================
    
    'performance' => [
        // Cache-Einstellungen
        'enable_cache' => true,
        'cache_directory' => '/tmp/freibad_cache',
        
        // Datenbankoptimierung
        'use_indexes' => true,
        'limit_queries' => true,
        
        // Komprimierung
        'enable_gzip' => true
    ],

    // =============================================================================
    // WARTUNG UND DEBUGGING
    // =============================================================================
    
    'maintenance' => [
        // Debug-Modus (mehr Logs, Fehlerdetails)
        'debug_mode' => false,
        
        // Wartungs-Modus
        'maintenance_mode' => false,
        'maintenance_message' => 'Dashboard wird gewartet. Bitte versuchen Sie es später erneut.',
        
        // Datenbereinigung
        'cleanup_old_cache' => true,
        'cache_retention_hours' => 24
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
 * Prüft ob Wartungs-Modus aktiviert ist
 */
function isMaintenanceMode() {
    return getConfig('maintenance.maintenance_mode', false);
}

/**
 * Gibt alle Zeitintervalle zurück
 */
function getTimeIntervals() {
    return getConfig('api.time_intervals', []);
}

/**
 * Gibt Datenbanktabellen zurück
 */
function getDatabaseTables() {
    return getConfig('database.tables', []);
}

/**
 * Formatiert einen Wert gemäß Konfiguration
 */
function formatValue($type, $value) {
    $decimals = getConfig("dashboard.decimal_places.{$type}", 2);
    return number_format($value, $decimals, ',', '.');
}

/**
 * Gibt Chart-Farben zurück
 */
function getChartColors() {
    return getConfig('dashboard.chart_colors', []);
}

/**
 * Gibt Becken-Konfiguration zurück
 */
function getBeckenConfig($becken = null) {
    $becken_config = getConfig('wasserqualitaet.becken', []);
    
    if ($becken) {
        return $becken_config[$becken] ?? null;
    }
    
    return $becken_config;
}

/**
 * Gibt Grenzwerte für Wasserqualität zurück
 */
function getGrenzwerte($parameter = null) {
    $grenzwerte = getConfig('wasserqualitaet.grenzwerte', []);
    
    if ($parameter) {
        return $grenzwerte[$parameter] ?? null;
    }
    
    return $grenzwerte;
}

/**
 * Validiert IP-Adresse gegen Whitelist
 */
function isIpAllowed($ip) {
    $allowed_ips = getConfig('security.allowed_ips', []);
    
    if (empty($allowed_ips)) {
        return true; // Alle IPs erlaubt wenn Whitelist leer
    }
    
    return in_array($ip, $allowed_ips);
}

/**
 * Logging-Funktion
 */
function logMessage($message, $level = 'INFO') {
    if (!getConfig('security.log_errors', false)) {
        return;
    }
    
    $log_file = getConfig('security.log_file', '/tmp/freibad_dashboard.log');
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

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
            logMessage("WARNUNG: Fehlende Konfiguration für '{$key}'", 'WARNING');
        }
    }
    
    // Tabellen-Existenz prüfen (optional)
    try {
        $dsn = "mysql:host=" . getConfig('database.host') . 
               ";dbname=" . getConfig('database.database') . 
               ";charset=" . getConfig('database.charset');
        
        $pdo = new PDO($dsn, getConfig('database.username'), getConfig('database.password'));
        
        foreach (getDatabaseTables() as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                logMessage("WARNUNG: Tabelle '{$table}' nicht gefunden", 'WARNING');
            }
        }
    } catch (Exception $e) {
        logMessage("Datenbankverbindung fehlgeschlagen: " . $e->getMessage(), 'ERROR');
    }
}

?>