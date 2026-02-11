<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Konfiguration laden
require_once 'power-config.php';

// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', isDebugMode() ? 1 : 0);

try {
    // Rate Limiting (einfache Implementierung)
    $max_requests = getConfig('security.max_requests_per_minute', 60);
    
    // IP-Whitelist prüfen
    $allowed_ips = getConfig('security.allowed_ips', []);
    if (!empty($allowed_ips) && !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
        throw new Exception('Zugriff nicht erlaubt');
    }
    
    // Zeitraum aus GET-Parameter oder action für Tabelle
    $range = $_GET['range'] ?? getConfig('api.default_range', '1h');
    $action = $_GET['action'] ?? 'dashboard';
    
    // Zeitintervalle aus Konfiguration
    $time_intervals = getTimeIntervals();
    
    if ($action !== 'table' && $action !== 'export' && !isset($time_intervals[$range])) {
        throw new Exception('Ungültiger Zeitraum');
    }
    
    // Datenbankverbindung aus Konfiguration
    $db_config = getConfig('database');
    $table = $db_config['table'];
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Spezielle Behandlung für Export-Anfragen
    if ($action === 'export') {
        handleExportRequest($pdo, $db_config);
        return;
    }
    
    // Spezielle Behandlung für Tabellendaten
    if ($action === 'table') {
        handleTableRequest($pdo, $db_config);
        return;
    }
    
    $interval = $time_intervals[$range];
    
    // Aktuelle Werte (neuester Eintrag)
    $stmt_current = $pdo->prepare("
        SELECT 
            current_feed_total,
            current_feed_l1,
            current_feed_l2,
            current_feed_l3,
            device_status,
            operation_status,
            temperature,
            operation_time,
            total_feed_wh,
            monthly_feed_kwh,
            daily_feed_wh,
            datetime
        FROM {$table}
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt_current->execute();
    $current = $stmt_current->fetch();
    
    // Historische Daten
    $max_points_config = getConfig('api.max_data_points', []);
    $max_points = isset($max_points_config[$range]) ? $max_points_config[$range] : 1000;
    
    // Intelligente Datenpunkt-Reduzierung für längere Zeiträume
    $sample_interval = '';
    switch ($range) {
        case '1h':
        case '6h':
        case '24h':
            $sample_interval = ''; // Alle Datenpunkte
            break;
        case '7d':
            $sample_interval = 'AND MINUTE(datetime) % 5 = 0'; // Alle 5 Minuten
            break;
        case '30d':
            $sample_interval = 'AND MINUTE(datetime) % 15 = 0'; // Alle 15 Minuten
            break;
        case '1y':
            $sample_interval = 'AND MINUTE(datetime) = 0 AND SECOND(datetime) = 0'; // Stündlich
            break;
    }
    
    $stmt_history = $pdo->prepare("
        SELECT 
            current_feed_total,
            current_feed_l1,
            current_feed_l2,
            current_feed_l3,
            device_status,
            operation_status,
            temperature,
            operation_time,
            total_feed_wh,
            monthly_feed_kwh,
            daily_feed_wh,
            datetime
        FROM {$table}
        WHERE datetime >= DATE_SUB(NOW(), INTERVAL {$interval})
        {$sample_interval}
        ORDER BY datetime ASC
        LIMIT {$max_points}
    ");
    $stmt_history->execute();
    $history = $stmt_history->fetchAll();
    
    // Statistiken berechnen
    $stats = [];
    if (!empty($history)) {
        $total_power_values = array_column($history, 'current_feed_total');
        $l1_values = array_column($history, 'current_feed_l1');
        $l2_values = array_column($history, 'current_feed_l2');
        $l3_values = array_column($history, 'current_feed_l3');
        $temp_values = array_map(function($t) { return $t / 10.0; }, array_column($history, 'temperature'));
        
        // Neue Energie-Statistiken
        $daily_values = array_column($history, 'daily_feed_wh');
        $monthly_values = array_column($history, 'monthly_feed_kwh');
        
        $stats = [
            'total_power_avg' => array_sum($total_power_values) / count($total_power_values),
            'total_power_max' => max($total_power_values),
            'total_power_min' => min($total_power_values),
            'l1_avg' => array_sum($l1_values) / count($l1_values),
            'l2_avg' => array_sum($l2_values) / count($l2_values),
            'l3_avg' => array_sum($l3_values) / count($l3_values),
            'temperature_avg' => array_sum($temp_values) / count($temp_values),
            'temperature_max' => max($temp_values),
            'temperature_min' => min($temp_values),
            'daily_energy_max' => max($daily_values),
            'monthly_energy_max' => max($monthly_values),
            'data_points' => count($history)
        ];
    }
    
    // Systemstatus prüfen
    $stmt_status = $pdo->prepare("
        SELECT 
            datetime
        FROM {$table}
        ORDER BY id DESC 
        LIMIT 10
    ");
    $stmt_status->execute();
    $recent_status = $stmt_status->fetchAll();
    
    $system_health = [
        'status' => 'OK',
        'error_rate' => 0,
        'last_error' => null,
        'max_error_rate' => 0
    ];
    
    // Datenalter prüfen und für Frontend aufbereiten
    $last_update_info = null;
    if ($current) {
        $last_update = new DateTime($current['datetime']);
        $now = new DateTime();
        $data_age_minutes = ($now->getTimestamp() - $last_update->getTimestamp()) / 60;
        $max_age = getConfig('alerts.system.max_data_age_minutes', 10);
        $attention_age = getConfig('alerts.system.attention_age_minutes', 5);
        
        $last_update_info = [
            'datetime' => $current['datetime'],
            'formatted' => $last_update->format('d.m.Y, H:i:s'),
            'age_minutes' => round($data_age_minutes, 1),
            'age_seconds' => ($now->getTimestamp() - $last_update->getTimestamp()),
            'is_stale' => $data_age_minutes > $max_age,
            'needs_attention' => $data_age_minutes > $attention_age,
            'max_age_minutes' => $max_age,
            'attention_age_minutes' => $attention_age
        ];
        
        if ($data_age_minutes > $max_age) {
            $system_health['status'] = 'WARNING';
            $system_health['data_age_warning'] = true;
            $system_health['data_age_minutes'] = round($data_age_minutes, 1);
        }
    }
    
    // Aktuelle Werte gegen Schwellenwerte prüfen
    $active_alerts = [];
    if ($current) {
        $active_alerts = checkAlerts($current);
    }
    
    // Antwort zusammenstellen
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'range' => $range,
        'current' => $current,
        'history' => $history,
        'stats' => $stats,
        'system_health' => $system_health,
        'active_alerts' => $active_alerts,
        'last_update' => $last_update_info,
        'system_info' => getSystemInfo(),
        'config' => [
            'units' => getConfig('units'),
            'decimal_places' => getConfig('dashboard.decimal_places'),
            'auto_refresh_interval' => getConfig('dashboard.auto_refresh_interval'),
            'charts' => getConfig('charts')
        ]
    ];
    
    // JSON ausgeben
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Datenbankfehler
    http_response_code(500);
    $error_message = isDebugMode() ? $e->getMessage() : 'Datenbankfehler';
    
    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    // Fehler loggen
    if (getConfig('security.log_errors', true)) {
        error_log("Power Dashboard DB Error: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Allgemeiner Fehler
    http_response_code(400);
    $error_message = isDebugMode() ? $e->getMessage() : 'Fehler beim Verarbeiten der Anfrage';
    
    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    // Fehler loggen
    if (getConfig('security.log_errors', true)) {
        error_log("Power Dashboard Error: " . $e->getMessage());
    }
}

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

/**
 * Prüft aktuelle Werte gegen Alarm-Schwellenwerte
 */
function checkAlerts($current) {
    $alerts = [];
    
    // Gesamtleistung prüfen
    $total_power = (float) $current['current_feed_total'];
    $power_thresholds = getAlertThresholds('power');
    
    if ($total_power >= $power_thresholds['critical_high']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'power',
            'message' => '🚨 Kritische Gesamtleistung: Sehr hoch (' . formatPower($total_power) . ')',
            'value' => $total_power,
            'threshold' => $power_thresholds['critical_high']
        ];
    } elseif ($total_power >= $power_thresholds['warning_high']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'power',
            'message' => '⚡ Hohe Gesamtleistung (' . formatPower($total_power) . ')',
            'value' => $total_power,
            'threshold' => $power_thresholds['warning_high']
        ];
    } elseif ($total_power <= $power_thresholds['warning_low']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'power',
            'message' => '⚠️ Geringe Stromerzeugung (' . formatPower($total_power) . ')',
            'value' => $total_power,
            'threshold' => $power_thresholds['warning_low']
        ];
    }
    
    // Temperatur prüfen (skaliert)
    $temp_raw = (float) $current['temperature'];
    $temp_celsius = $temp_raw / 10.0;
    $temp_thresholds = getAlertThresholds('temperature');
    
    if ($temp_celsius >= $temp_thresholds['critical_high']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'temperature',
            'message' => '🌡️ Kritische Temperatur: Sehr hoch (' . number_format($temp_celsius, 1) . '°C)',
            'value' => $temp_celsius,
            'threshold' => $temp_thresholds['critical_high']
        ];
    } elseif ($temp_celsius >= $temp_thresholds['warning_high']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'temperature',
            'message' => '🔥 Hohe Temperatur (' . number_format($temp_celsius, 1) . '°C)',
            'value' => $temp_celsius,
            'threshold' => $temp_thresholds['warning_high']
        ];
    }
    
    // Energie-Alarme prüfen
    $daily_wh = (float) ($current['daily_feed_wh'] ?? 0);
    $monthly_kwh = (float) ($current['monthly_feed_kwh'] ?? 0);
    $energy_thresholds = getAlertThresholds('energy');
    
    if ($daily_wh > 0 && $daily_wh < $energy_thresholds['daily_minimum_wh']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'energy',
            'message' => '📉 Niedrige Tagesproduktion: ' . formatEnergyValue($daily_wh) . ' kWh',
            'value' => $daily_wh,
            'threshold' => $energy_thresholds['daily_minimum_wh']
        ];
    }
    
    if ($monthly_kwh > 0 && $monthly_kwh < $energy_thresholds['monthly_minimum_kwh']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'energy',
            'message' => '📊 Niedrige Monatsproduktion: ' . number_format($monthly_kwh, 1) . ' kWh',
            'value' => $monthly_kwh,
            'threshold' => $energy_thresholds['monthly_minimum_kwh']
        ];
    }
    
    // Phasen-Unbalance prüfen
    $l1 = (float) $current['current_feed_l1'];
    $l2 = (float) $current['current_feed_l2'];
    $l3 = (float) $current['current_feed_l3'];
    
    if ($total_power > 1000) { // Nur bei relevanter Leistung prüfen
        $avg_phase = ($l1 + $l2 + $l3) / 3;
        if ($avg_phase > 0) {
            $max_deviation = max(abs($l1 - $avg_phase), abs($l2 - $avg_phase), abs($l3 - $avg_phase));
            $max_deviation_percent = ($max_deviation / $avg_phase) * 100;
            
            $balance_threshold = getAlertThresholds('phase_balance');
            if ($max_deviation_percent > $balance_threshold['warning_percent']) {
                $alerts[] = [
                    'type' => 'warning',
                    'sensor' => 'phase_balance',
                    'message' => '⚖️ Phasen-Unbalance detektiert (' . number_format($max_deviation_percent, 1) . '%)',
                    'value' => $max_deviation_percent,
                    'threshold' => $balance_threshold['warning_percent']
                ];
            }
        }
    }
    
    return $alerts;
}

/**
 * Formatiert Leistungswerte
 */
function formatPower($watts) {
    if ($watts >= 1000) {
        return number_format($watts / 1000, 1) . ' kW';
    }
    return number_format($watts, 0) . ' W';
}

/**
 * Behandelt Export-Anfragen
 */
function handleExportRequest($pdo, $db_config) {
    try {
        $table = $db_config['table'];
        
        // Parameter für Export
        $limit = min(50000, intval($_GET['limit'] ?? 10000)); // Max 50.000 Datensätze
        $format = $_GET['format'] ?? 'csv';
        
        if ($format !== 'csv') {
            throw new Exception('Nur CSV-Format wird unterstützt');
        }
        
        // Alle Daten für Export laden (chronologisch sortiert)
        $exportStmt = $pdo->prepare("
            SELECT 
                datetime,
                current_feed_total,
                current_feed_l1,
                current_feed_l2,
                current_feed_l3,
                device_status,
                operation_status,
                temperature,
                operation_time,
                total_feed_wh,
                monthly_feed_kwh,
                daily_feed_wh
            FROM {$table} 
            ORDER BY datetime DESC
            LIMIT {$limit}
        ");
        $exportStmt->execute();
        $exportData = $exportStmt->fetchAll();
        
        // CSV-Headers setzen
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stromdaten_vollstaendig_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        
        // CSV-Inhalt ausgeben
        $output = fopen('php://output', 'w');
        
        // BOM für korrekte UTF-8 Darstellung in Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header-Zeile
        fputcsv($output, [
            'Datum/Zeit',
            'Gesamtleistung (W)',
            'Phase L1 (W)',
            'Phase L2 (W)',
            'Phase L3 (W)',
            'Temperatur (°C)',
            'Gerätestatus',
            'Betriebsstatus',
            'Betriebszeit (s)',
            'Gesamtenergie (kWh)',
            'Monatsertrag (kWh)',
            'Tagesertrag (kWh)'
        ], ';');
        
        // Datenzeilen
        foreach ($exportData as $row) {
            $date = new DateTime($row['datetime']);
            $temp_celsius = floatval($row['temperature'] ?? 0) / 10.0;
            $total_energy_kwh = floatval($row['total_feed_wh'] ?? 0) / 1000.0;
            $daily_energy_kwh = floatval($row['daily_feed_wh'] ?? 0) / 1000.0;
            
            fputcsv($output, [
                $date->format('d.m.Y H:i:s'),
                number_format(floatval($row['current_feed_total'] ?? 0), 0, ',', ''),
                number_format(floatval($row['current_feed_l1'] ?? 0), 0, ',', ''),
                number_format(floatval($row['current_feed_l2'] ?? 0), 0, ',', ''),
                number_format(floatval($row['current_feed_l3'] ?? 0), 0, ',', ''),
                number_format($temp_celsius, 1, ',', ''),
                intval($row['device_status'] ?? 0),
                intval($row['operation_status'] ?? 0),
                number_format(floatval($row['operation_time'] ?? 0), 0, ',', ''),
                number_format($total_energy_kwh, 3, ',', ''),
                number_format(floatval($row['monthly_feed_kwh'] ?? 0), 3, ',', ''),
                number_format($daily_energy_kwh, 3, ',', '')
            ], ';');
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'action' => 'export',
            'error' => isDebugMode() ? $e->getMessage() : 'Fehler beim Export',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Behandelt Tabellen-Anfragen mit Pagination
 */
function handleTableRequest($pdo, $db_config) {
    try {
        $table = $db_config['table'];
        
        // Parameter aus GET-Request
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(10, min(1000, intval($_GET['limit'] ?? 25))); // Zwischen 10 und 1000
        $sortColumn = intval($_GET['sort'] ?? 0);
        $sortDirection = $_GET['direction'] === 'asc' ? 'ASC' : 'DESC';
        
        // Sortier-Spalten mapping
        $sortColumns = [
            0 => 'datetime',
            1 => 'current_feed_total',
            2 => 'device_status', 
            3 => 'temperature',
            4 => 'daily_feed_wh'
        ];
        
        $orderBy = $sortColumns[$sortColumn] ?? 'datetime';
        $offset = ($page - 1) * $limit;
        
        // Gesamtanzahl der Einträge ermitteln
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM {$table}");
        $countStmt->execute();
        $totalEntries = $countStmt->fetch()['total'];
        $totalPages = ceil($totalEntries / $limit);
        
        // Tabellendaten abrufen
        $dataStmt = $pdo->prepare("
            SELECT 
                datetime,
                current_feed_total,
                current_feed_l1,
                current_feed_l2,
                current_feed_l3,
                device_status,
                operation_status,
                temperature,
                operation_time,
                total_feed_wh,
                monthly_feed_kwh,
                daily_feed_wh
            FROM {$table} 
            ORDER BY {$orderBy} {$sortDirection}
            LIMIT {$limit} OFFSET {$offset}
        ");
        $dataStmt->execute();
        $data = $dataStmt->fetchAll();
        
        // Antwort für Tabelle
        $response = [
            'success' => true,
            'action' => 'table',
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_entries' => $totalEntries,
                'entries_per_page' => $limit,
                'sort_column' => $sortColumn,
                'sort_direction' => strtolower($sortDirection)
            ],
            'total_pages' => $totalPages, // Für Backward-Kompatibilität
            'total_entries' => $totalEntries, // Für Backward-Kompatibilität
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'action' => 'table',
            'error' => isDebugMode() ? $e->getMessage() : 'Fehler beim Laden der Tabellendaten',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>