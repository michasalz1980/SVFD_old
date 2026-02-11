<?php
/**
 * Freibad Dabringhausen - Frischwasser Monitoring System
 * Konfigurationsdatei für Frischwasser-Monitoring
 * 
 * Version: 1.3.0
 * Erstellt: 30.06.2025
 */

// =============================================================================
// HAUPTKONFIGURATION
// =============================================================================

$config = [
    // Datenbank-Verbindung (Gleiche wie Abwasser)
    'database' => [
        'host' => 'localhost',
        'username' => 'svfd_Schedule',
        'password' => 'REDACTED',
        'database' => 'svfd_schedule',
        'charset' => 'utf8',
        'table_frischwasser' => 'ffd_frischwasser'  // Frischwasser Tabelle
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
            '30d' => '30 DAY',
            '1y' => '365 DAY'
        ],
        
        // Standard-Zeitraum falls nicht angegeben
        'default_range' => '24h',  // 24h für Frischwasser sinnvoller
        
        // Maximale Anzahl Datenpunkte pro Zeitraum
        'max_data_points' => [
            '1h' => 240,      // 15-Min-Intervalle
            '6h' => 360,      // 6h * 4 (alle 15 Min)
            '24h' => 96,      // 24h * 4 (alle 15 Min)
            '7d' => 168,      // 7 Tage * 24h (stündlich)
            '30d' => 120,     // 30 Tage * 4 (alle 6h)
            '1y' => 365       // 365 Tage (täglich)
        ],
        
        // Cache-Dauer für API-Antworten (Sekunden)
        'cache_duration' => 60  // 1 Minute für Frischwasser
    ],

    // =============================================================================
    // DASHBOARD KONFIGURATION
    // =============================================================================
    
    'dashboard' => [
        // Automatische Aktualisierung (Millisekunden)
        'auto_refresh_interval' => 60000,  // 1 Minute für Frischwasser
        
        // Titel und Beschreibung
        'title' => 'Freibad Dabringhausen',
        'subtitle' => 'Frischwasser-Monitoring System',
        
        // Anzeigeoptionen
        'show_charts' => true,
        'show_alerts' => true,
        'show_statistics' => true,
        'show_data_table' => true,
        
        // Dezimalstellen für Anzeige
        'decimal_places' => [
            'counter_m3' => 3,          // 0.001 m³
            'consumption_l' => 1,       // 0.1 L
            'consumption_m3' => 3,      // 0.001 m³
            'flow_lmin' => 1,           // 0.1 l/min
            'daily_m3' => 2,            // 0.01 m³
            'weekly_m3' => 2            // 0.01 m³
        ],
        
        // Tabellen-Konfiguration
        'data_table' => [
            'default_entries_per_page' => 25,
            'max_entries_per_page' => 1000,
            'available_page_sizes' => [10, 25, 50, 100],
            'default_sort_column' => 0,    // 0 = datetime
            'default_sort_direction' => 'desc',
            'enable_export' => true,
            'export_formats' => ['csv'],
            'max_export_rows' => 50000
        ]
    ],

    // =============================================================================
    // ALARM- UND WARNSCHWELLENWERTE
    // =============================================================================
    
    'alerts' => [
        // Frischwasser-spezifische Alarme
        'frischwasser' => [
            'high_hourly_consumption' => 1000,     // Warnung bei > 1000 L/h
            'critical_hourly_consumption' => 2000,  // Kritisch bei > 2000 L/h
            'high_daily_consumption' => 15,         // Warnung bei > 15 m³/Tag
            'critical_daily_consumption' => 25,     // Kritisch bei > 25 m³/Tag
            'high_weekly_consumption' => 75,        // Warnung bei > 75 m³/Woche
            'flow_threshold_lmin' => 50,            // Warnung bei > 50 l/min
            'zero_consumption_hours' => 4,          // Warnung bei 4h ohne Verbrauch (tagsüber)
            'efficiency_threshold' => 60           // Warnung bei < 60% Effizienz
        ],
        
        // System-Gesundheit (angepasst für Frischwasser)
        'system' => [
            'max_error_rate' => 10,
            'max_data_age_minutes' => 30,       // 30 Min für Frischwasser
            'attention_age_minutes' => 15,      // Achtung ab 15 Minuten
            'consecutive_errors' => 3,
            'show_relative_time' => true
        ]
    ],

    // =============================================================================
    // EINHEITEN UND FORMATIERUNG
    // =============================================================================
    
    'units' => [
        'counter_m3' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter',
            'conversion_factor' => 1000     // Liter zu m³
        ],
        'consumption_l' => [
            'symbol' => 'L',
            'name' => 'Liter',
            'conversion_factor' => 1
        ],
        'consumption_m3' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter',
            'conversion_factor' => 1000
        ],
        'flow_lmin' => [
            'symbol' => 'l/min',
            'name' => 'Liter pro Minute',
            'conversion_factor' => 1
        ],
        'daily_m3' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter pro Tag',
            'conversion_factor' => 1
        ],
        'weekly_m3' => [
            'symbol' => 'm³',
            'name' => 'Kubikmeter pro Woche',
            'conversion_factor' => 1
        ]
    ],

    // =============================================================================
    // CHART-KONFIGURATION
    // =============================================================================
    
    'charts' => [
        'consumption' => [
            'color' => '#3498db',
            'background' => 'rgba(52, 152, 219, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Verbrauch (L)',
            'min_value' => 0
        ],
        'counter' => [
            'color' => '#2ecc71',
            'background' => 'rgba(46, 204, 113, 0.1)',
            'show_zero_line' => false,
            'y_axis_title' => 'Zählerstand (m³)'
        ],
        'daily_consumption' => [
            'color' => '#e74c3c',
            'background' => 'rgba(231, 76, 60, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Tagesverbrauch (m³)',
            'chart_type' => 'bar'
        ],
        'flow_pattern' => [
            'color' => '#f39c12',
            'background' => 'rgba(243, 156, 18, 0.1)',
            'show_zero_line' => true,
            'y_axis_title' => 'Durchfluss (l/min)',
            'chart_type' => 'line'
        ]
    ],

    // =============================================================================
    // ERWARTETE WERTE (FÜR VALIDIERUNG)
    // =============================================================================
    
    'expected_values' => [
        'counter_m3' => [
            'normal_min' => 1000,      // Ab 1000 m³
            'normal_max' => 2000,      // Bis 2000 m³
            'typical' => 1236          // Typischer Wert
        ],
        'consumption_l' => [
            'normal_min' => 0,
            'normal_max' => 500,       // Max 500L pro 15-Min-Intervall
            'typical' => 150           // Typisch 150L pro 15-Min-Intervall
        ],
        'daily_consumption_m3' => [
            'normal_min' => 0,
            'normal_max' => 20,        // Max 20 m³ pro Tag
            'typical' => 8             // Typisch 8 m³ pro Tag
        ],
        'flow_lmin' => [
            'normal_min' => 0,
            'normal_max' => 30,        // Max 30 l/min
            'typical' => 10            // Typisch 10 l/min
        ]
    ],

    // =============================================================================
    // BETRIEBSZEITEN (FREIBAD-SPEZIFISCH)
    // =============================================================================
    
    'operation_hours' => [
        'season_start' => '05-01',      // 1. Mai
        'season_end' => '09-30',        // 30. September
        'daily_open' => '09:00',        // Öffnung 9:00
        'daily_close' => '20:00',       // Schließung 20:00
        'peak_hours' => [
            'start' => '11:00',
            'end' => '18:00'
        ],
        'maintenance_hours' => [
            'start' => '07:00',
            'end' => '09:00'
        ]
    ],

    // =============================================================================
    // SICHERHEITS-KONFIGURATION
    // =============================================================================
    
    'security' => [
        // IP-Whitelist (leer = alle erlaubt)
        'allowed_ips' => [],
        
        // Rate Limiting
        'max_requests_per_minute' => 60,
        
        // SQL-Injection-Schutz
        'enable_prepared_statements' => true,
        
        // CORS-Einstellungen
        'cors_origins' => ['*'],
        
        // Fehler-Logging
        'log_errors' => true,
        'log_file' => '/var/log/frischwasser_dashboard.log'
    ],

    // =============================================================================
    // BERICHTSKONFIGURATION
    // =============================================================================
    
    'reports' => [
        'daily_summary' => [
            'enabled' => true,
            'email_recipients' => [],
            'send_time' => '22:00'
        ],
        'weekly_summary' => [
            'enabled' => true,
            'email_recipients' => [],
            'send_day' => 'monday',
            'send_time' => '08:00'
        ],
        'monthly_summary' => [
            'enabled' => true,
            'email_recipients' => [],
            'send_day' => 1,
            'send_time' => '09:00'
        ]
    ],

    // =============================================================================
    // EFFIZIENZ-BERECHNUNG
    // =============================================================================
    
    'efficiency' => [
        'calculation_method' => 'daily_average',
        'baseline_consumption' => [
            'maintenance' => 0.5,       // 0.5 m³ für Wartung
            'base_operations' => 2.0,   // 2.0 m³ Grundbetrieb
            'per_visitor_estimate' => 0.05  // 50L pro Besucher
        ],
        'efficiency_targets' => [
            'excellent' => 90,          // > 90% Effizienz
            'good' => 75,              // > 75% Effizienz
            'acceptable' => 60,         // > 60% Effizienz
            'poor' => 45               // < 45% Effizienz
        ]
    ],

    // =============================================================================
    // WARTUNG UND DEBUGGING
    // =============================================================================
    
    'maintenance' => [
        // Debug-Modus
        'debug_mode' => false,
        
        // Datenbereinigung
        'cleanup_old_data_days' => 730,  // 2 Jahre für Frischwasser
        
        // Wartungs-Fenster
        'maintenance_hours' => [2, 3],
        
        // Backup-Konfiguration
        'backup_enabled' => true,
        'backup_retention_days' => 90
    ]
];

// =============================================================================
// HILFSFUNKTIONEN (GLEICH WIE ABWASSER)
// =============================================================================

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

function isDebugMode() {
    return getConfig('maintenance.debug_mode', false);
}

function getTimeIntervals() {
    return getConfig('api.time_intervals', []);
}

function getAlertThresholds($sensor) {
    return getConfig("alerts.{$sensor}", []);
}

function formatValue($sensor, $value) {
    $decimals = getConfig("dashboard.decimal_places.{$sensor}", 2);
    return number_format($value, $decimals, ',', '.');
}

function getUnit($sensor) {
    return getConfig("units.{$sensor}.symbol", '');
}

/**
 * Prüft ob sich das Freibad in der Betriebszeit befindet
 */
function isOperationHours() {
    $now = new DateTime();
    $currentTime = $now->format('H:i');
    $currentDate = $now->format('m-d');
    
    $seasonStart = getConfig('operation_hours.season_start');
    $seasonEnd = getConfig('operation_hours.season_end');
    $dailyOpen = getConfig('operation_hours.daily_open');
    $dailyClose = getConfig('operation_hours.daily_close');
    
    // Saison prüfen
    if ($currentDate < $seasonStart || $currentDate > $seasonEnd) {
        return false;
    }
    
    // Tageszeit prüfen
    if ($currentTime < $dailyOpen || $currentTime > $dailyClose) {
        return false;
    }
    
    return true;
}

/**
 * Prüft ob sich das Freibad in der Spitzenzeit befindet
 */
function isPeakHours() {
    if (!isOperationHours()) {
        return false;
    }
    
    $now = new DateTime();
    $currentTime = $now->format('H:i');
    
    $peakStart = getConfig('operation_hours.peak_hours.start');
    $peakEnd = getConfig('operation_hours.peak_hours.end');
    
    return ($currentTime >= $peakStart && $currentTime <= $peakEnd);
}

/**
 * Berechnet die Effizienz basierend auf Verbrauch und geschätzten Besuchern
 */
function calculateEfficiency($consumption_m3, $estimated_visitors = null) {
    $baseline = getConfig('efficiency.baseline_consumption');
    $baseConsumption = $baseline['maintenance'] + $baseline['base_operations'];
    
    if ($estimated_visitors !== null) {
        $expectedConsumption = $baseConsumption + ($estimated_visitors * $baseline['per_visitor_estimate']);
        $efficiency = min(100, ($expectedConsumption / max($consumption_m3, 0.1)) * 100);
        return round($efficiency, 1);
    }
    
    // Fallback ohne Besucherzahlen
    $averageVisitors = 100; // Annahme: 100 Besucher pro Tag
    $expectedConsumption = $baseConsumption + ($averageVisitors * $baseline['per_visitor_estimate']);
    $efficiency = min(100, ($expectedConsumption / max($consumption_m3, 0.1)) * 100);
    return round($efficiency, 1);
}

// Konfiguration validieren
if (isDebugMode()) {
    $required_keys = [
        'database.host',
        'database.username', 
        'database.password',
        'database.database',
        'database.table_frischwasser'
    ];
    
    foreach ($required_keys as $key) {
        if (!getConfig($key)) {
            error_log("WARNUNG: Fehlende Frischwasser-Konfiguration für '{$key}'");
        }
    }
}

?>