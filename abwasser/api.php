<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Konfiguration laden
require_once 'config.php';

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
    
    // Datenbankverbindung aus Konfiguration - FRÜHER DEFINIERT
    $db_config = getConfig('database');
    $table = $db_config['table'];
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Spezielle Behandlung für Export-Anfragen - NEUE FUNKTION
    if ($action === 'export') {
        handleExportRequest($pdo, $db_config);
        return;
    }
    
    // Spezielle Behandlung für Tabellendaten - NACH $pdo Definition
    if ($action === 'table') {
        handleTableRequest($pdo, $db_config);
        return;
    }
    
    $interval = $time_intervals[$range];
    
    // Aktuelle Werte (neuester Eintrag)
    $stmt_current = $pdo->prepare("
        SELECT 
            wasserstand,
            durchflussrate,
            totalizer,
            sensor_strom,
            consumption,
            modbus_status,
            timestamp
        FROM {$table}
        WHERE modbus_status = 'OK' 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt_current->execute();
    $current = $stmt_current->fetch();
    
    // Summierten Verbrauch berechnen (NEUE FUNKTION v1.2.0)
    $total_consumption = 0;
    if ($current) {
        $stmt_total_consumption = $pdo->prepare("
            SELECT SUM(consumption) as total_consumption
            FROM {$table}
            WHERE modbus_status = 'OK' 
            AND consumption > 0
        ");
        $stmt_total_consumption->execute();
        $consumption_result = $stmt_total_consumption->fetch();
        $total_consumption = $consumption_result['total_consumption'] ?? 0;
        
        // Zu aktuellen Werten hinzufügen
        $current['total_consumption'] = $total_consumption;
    }
    
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
            $sample_interval = 'AND MINUTE(timestamp) % 5 = 0'; // Alle 5 Minuten
            break;
        case '30d':
            $sample_interval = 'AND MINUTE(timestamp) % 15 = 0'; // Alle 15 Minuten
            break;
        case '1y':
            $sample_interval = 'AND MINUTE(timestamp) = 0 AND SECOND(timestamp) = 0'; // Stündlich
            break;
    }
    
    $stmt_history = $pdo->prepare("
        SELECT 
            wasserstand,
            durchflussrate,
            totalizer,
            sensor_strom,
            consumption,
            timestamp
        FROM {$table}
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$interval})
        AND modbus_status = 'OK'
        {$sample_interval}
        ORDER BY timestamp ASC
        LIMIT {$max_points}
    ");
    $stmt_history->execute();
    $history = $stmt_history->fetchAll();
    
    // Statistiken berechnen
    $stats = [];
    if (!empty($history)) {
        $durchfluss_values = array_column($history, 'durchflussrate');
        $wasserstand_values = array_column($history, 'wasserstand');
        
        $stats = [
            'durchfluss_avg' => array_sum($durchfluss_values) / count($durchfluss_values),
            'durchfluss_max' => max($durchfluss_values),
            'durchfluss_min' => min($durchfluss_values),
            'wasserstand_avg' => array_sum($wasserstand_values) / count($wasserstand_values),
            'wasserstand_max' => max($wasserstand_values),
            'wasserstand_min' => min($wasserstand_values),
            'data_points' => count($history)
        ];
    }
    
    // Systemstatus prüfen
    $stmt_status = $pdo->prepare("
        SELECT 
            modbus_status,
            error_message,
            timestamp
        FROM {$table}
        ORDER BY id DESC 
        LIMIT 10
    ");
    $stmt_status->execute();
    $recent_status = $stmt_status->fetchAll();
    
    $error_count = 0;
    foreach ($recent_status as $status) {
        if ($status['modbus_status'] !== 'OK') {
            $error_count++;
        }
    }
    
    $max_error_rate = getConfig('alerts.system.max_error_rate', 10);
    $error_rate = round(($error_count / count($recent_status)) * 100, 1);
    
    $system_health = [
        'status' => $error_rate > $max_error_rate ? 'WARNING' : 'OK',
        'error_rate' => $error_rate,
        'last_error' => null,
        'max_error_rate' => $max_error_rate
    ];
    
    // Letzten Fehler finden
    foreach ($recent_status as $status) {
        if ($status['modbus_status'] !== 'OK') {
            $system_health['last_error'] = [
                'message' => $status['error_message'],
                'timestamp' => $status['timestamp']
            ];
            break;
        }
    }
    
    // Datenalter prüfen und für Frontend aufbereiten
    $last_update_info = null;
    if ($current) {
        $last_update = new DateTime($current['timestamp']);
        $now = new DateTime();
        $data_age_minutes = ($now->getTimestamp() - $last_update->getTimestamp()) / 60;
        $max_age = getConfig('alerts.system.max_data_age_minutes', 5);
        $attention_age = getConfig('alerts.system.attention_age_minutes', 2);
        
        $last_update_info = [
            'timestamp' => $current['timestamp'],
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
        'system_info' => getSystemInfo(), // NEUE ZEILE: System-Informationen hinzufügen
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
        error_log("Dashboard DB Error: " . $e->getMessage());
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
        error_log("Dashboard Error: " . $e->getMessage());
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
    
    // Wasserstand prüfen (Werte sind bereits in cm)
    $wasserstand = (float) $current['wasserstand'];
    $ws_thresholds = getAlertThresholds('wasserstand');
    
    if ($wasserstand <= $ws_thresholds['critical_low']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'wasserstand',
            'message' => '🚨 Kritischer Wasserstand: Sehr niedrig (' . formatValue('wasserstand', $wasserstand) . 'cm)',
            'value' => $wasserstand,
            'threshold' => $ws_thresholds['critical_low']
        ];
    } elseif ($wasserstand <= $ws_thresholds['warning_low']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'wasserstand',
            'message' => '⚠️ Niedriger Wasserstand (' . formatValue('wasserstand', $wasserstand) . 'cm)',
            'value' => $wasserstand,
            'threshold' => $ws_thresholds['warning_low']
        ];
    } elseif ($wasserstand >= $ws_thresholds['critical_high']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'wasserstand',
            'message' => '🚨 Kritischer Wasserstand: Sehr hoch (' . formatValue('wasserstand', $wasserstand) . 'cm)',
            'value' => $wasserstand,
            'threshold' => $ws_thresholds['critical_high']
        ];
    } elseif ($wasserstand >= $ws_thresholds['warning_high']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'wasserstand', 
            'message' => '⚠️ Hoher Wasserstand (' . formatValue('wasserstand', $wasserstand) . 'cm)',
            'value' => $wasserstand,
            'threshold' => $ws_thresholds['warning_high']
        ];
    }
    
    // Durchfluss prüfen
    $durchfluss = (float) $current['durchflussrate'];
    $df_thresholds = getAlertThresholds('durchflussrate');
    
    if ($durchfluss >= $df_thresholds['critical_high']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'durchflussrate',
            'message' => '🚨 Kritischer Durchfluss: Sehr hoch (' . formatValue('durchflussrate', $durchfluss) . 'l/s)',
            'value' => $durchfluss,
            'threshold' => $df_thresholds['critical_high']
        ];
    } elseif ($durchfluss >= $df_thresholds['warning_high']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'durchflussrate',
            'message' => '⚡ Hoher Durchfluss (' . formatValue('durchflussrate', $durchfluss) . 'l/s)',
            'value' => $durchfluss,
            'threshold' => $df_thresholds['warning_high']
        ];
    } elseif ($durchfluss <= $df_thresholds['warning_low']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'durchflussrate',
            'message' => '⚠️ Kein Durchfluss detektiert',
            'value' => $durchfluss,
            'threshold' => $df_thresholds['warning_low']
        ];
    }
    
    // Sensor-Strom prüfen
    $sensor_strom = (float) $current['sensor_strom'];
    $ss_thresholds = getAlertThresholds('sensor_strom');
    
    if ($sensor_strom <= $ss_thresholds['critical_low'] || $sensor_strom >= $ss_thresholds['critical_high']) {
        $alerts[] = [
            'type' => 'danger',
            'sensor' => 'sensor_strom',
            'message' => '🚨 Kritischer Sensor-Strom: Außerhalb des Bereichs (' . formatValue('sensor_strom', $sensor_strom) . 'mA)',
            'value' => $sensor_strom,
            'threshold' => $ss_thresholds
        ];
    } elseif ($sensor_strom <= $ss_thresholds['warning_low'] || $sensor_strom >= $ss_thresholds['warning_high']) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'sensor_strom',
            'message' => '🔧 Sensor-Strom außerhalb des Normalbereichs (' . formatValue('sensor_strom', $sensor_strom) . 'mA)',
            'value' => $sensor_strom,
            'threshold' => $ss_thresholds
        ];
    }
    
    // Totalizer/Verbrauch prüfen
    $consumption = (float) $current['consumption'];
    $tl_thresholds = getAlertThresholds('totalizer');
    
    if ($tl_thresholds['negative_consumption'] && $consumption < 0) {
        $alerts[] = [
            'type' => 'warning',
            'sensor' => 'consumption',
            'message' => '⚠️ Negativer Verbrauch detektiert (' . formatValue('consumption', $consumption) . 'm³)',
            'value' => $consumption,
            'threshold' => 0
        ];
    }
    
    return $alerts;
}

/**
 * Behandelt Export-Anfragen - NEUE FUNKTION
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
                timestamp,
                wasserstand,
                durchflussrate,
                totalizer,
                consumption,
                sensor_strom
            FROM {$table} 
            WHERE modbus_status = 'OK'
            ORDER BY timestamp DESC
            LIMIT {$limit}
        ");
        $exportStmt->execute();
        $exportData = $exportStmt->fetchAll();
        
        // CSV-Headers setzen
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="abwasser_messwerte_vollstaendig_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        
        // CSV-Inhalt ausgeben
        $output = fopen('php://output', 'w');
        
        // BOM für korrekte UTF-8 Darstellung in Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header-Zeile
        fputcsv($output, [
            'Datum/Zeit',
            'Wasserstand (cm)',
            'Durchfluss (l/s)',
            'Zählerstand (m³)',
            'Verbrauch (m³)',
            'Sensor (mA)'
        ], ';');
        
        // Datenzeilen
        foreach ($exportData as $row) {
            $date = new DateTime($row['timestamp']);
            fputcsv($output, [
                $date->format('d.m.Y H:i:s'),
                number_format(floatval($row['wasserstand'] ?? 0), 3, ',', ''),
                number_format(floatval($row['durchflussrate'] ?? 0), 3, ',', ''),
                number_format(floatval($row['totalizer'] ?? 0), 3, ',', ''),
                number_format(floatval($row['consumption'] ?? 0), 3, ',', ''),
                number_format(floatval($row['sensor_strom'] ?? 0), 3, ',', '')
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
            0 => 'timestamp',
            1 => 'wasserstand',
            2 => 'durchflussrate', 
            3 => 'totalizer',
            4 => 'consumption',
            5 => 'sensor_strom'
        ];
        
        $orderBy = $sortColumns[$sortColumn] ?? 'timestamp';
        $offset = ($page - 1) * $limit;
        
        // Gesamtanzahl der Einträge ermitteln
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM {$table} 
            WHERE modbus_status = 'OK'
        ");
        $countStmt->execute();
        $totalEntries = $countStmt->fetch()['total'];
        $totalPages = ceil($totalEntries / $limit);
        
        // Tabellendaten abrufen
        $dataStmt = $pdo->prepare("
            SELECT 
                timestamp,
                wasserstand,
                durchflussrate,
                totalizer,
                consumption,
                sensor_strom
            FROM {$table} 
            WHERE modbus_status = 'OK'
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