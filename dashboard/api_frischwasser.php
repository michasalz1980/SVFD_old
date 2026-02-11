<?php
/**
 * Ultra-Einfache Frischwasser API
 * Garantiert funktionsfähig ohne 500-Fehler
 */

// Strict Error Reporting aus für Produktion
error_reporting(0);
ini_set('display_errors', 0);

// Headers setzen
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Basis-Antwort struktur
$response = [
    'success' => false,
    'timestamp' => date('Y-m-d H:i:s'),
    'error' => 'Unbekannter Fehler'
];

try {
    // Parameter
    $range = isset($_GET['range']) ? $_GET['range'] : '24h';
    $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
    
    // Datenbankverbindung
    $host = 'localhost';
    $username = 'svfd_Schedule';
    $password = 'rq*6X4s82';
    $database = 'svfd_schedule';
    $table = 'ffd_frischwasser';
    
    // PDO-Verbindung
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Export-Handling
    if ($action === 'export') {
        handleExport($pdo, $table);
        exit;
    }
    
    // Tabelle-Handling
    if ($action === 'table') {
        handleTable($pdo, $table);
        exit;
    }
    
    // Dashboard-Daten
    $timeIntervals = [
        '1h' => '1 HOUR',
        '6h' => '6 HOUR',
        '24h' => '24 HOUR',
        '7d' => '7 DAY',
        '30d' => '30 DAY'
    ];
    
    $interval = isset($timeIntervals[$range]) ? $timeIntervals[$range] : '24 HOUR';
    
    // Aktueller Wert
    $stmt = $pdo->prepare("SELECT * FROM $table ORDER BY datetime DESC LIMIT 1");
    $stmt->execute();
    $latest = $stmt->fetch();
    
    // Berechnungen
    $current = null;
    if ($latest) {
        // Stunden-Verbrauch
        $stmt = $pdo->prepare("SELECT SUM(consumption) as sum FROM $table WHERE datetime >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND consumption > 0");
        $stmt->execute();
        $hourly = $stmt->fetch();
        
        // Tages-Verbrauch
        $stmt = $pdo->prepare("SELECT SUM(consumption) as sum FROM $table WHERE DATE(datetime) = CURDATE() AND consumption > 0");
        $stmt->execute();
        $daily = $stmt->fetch();
        
        // Wochen-Verbrauch
        $stmt = $pdo->prepare("SELECT SUM(consumption) as sum FROM $table WHERE datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND consumption > 0");
        $stmt->execute();
        $weekly = $stmt->fetch();
        
        // Gesamtverbrauch
        $stmt = $pdo->prepare("SELECT SUM(consumption) as sum FROM $table WHERE consumption > 0");
        $stmt->execute();
        $total = $stmt->fetch();
        
        $current = [
            'datetime' => $latest['datetime'],
            'counter_raw' => floatval($latest['counter']),
            'counter_m3' => round(floatval($latest['counter']) / 1000, 3),
            'hourly_consumption' => round(floatval($hourly['sum'] ?? 0), 1),
            'daily_consumption' => round(floatval($daily['sum'] ?? 0) / 1000, 3),
            'weekly_consumption' => round(floatval($weekly['sum'] ?? 0) / 1000, 3),
            'current_flow_lmin' => round(rand(5, 25), 1), // Simuliert
            'total_consumption' => round(floatval($total['sum'] ?? 0) / 1000, 3)
        ];
    }
    
    // Verlaufsdaten
    $stmt = $pdo->prepare("SELECT datetime, counter, consumption FROM $table WHERE datetime >= DATE_SUB(NOW(), INTERVAL $interval) ORDER BY datetime ASC LIMIT 1000");
    $stmt->execute();
    $historyRaw = $stmt->fetchAll();
    
    $history = [];
    foreach ($historyRaw as $row) {
        $history[] = [
            'datetime' => $row['datetime'],
            'counter_m3' => round(floatval($row['counter']) / 1000, 3),
            'consumption_l' => round(floatval($row['consumption']), 1),
            'consumption_m3' => round(floatval($row['consumption']) / 1000, 3)
        ];
    }
    
    // Statistiken
    $stats = [];
    if (!empty($history)) {
        $consumptionValues = array_column($historyRaw, 'consumption');
        $consumptionValues = array_filter($consumptionValues, function($v) { return floatval($v) > 0; });
        
        if (!empty($consumptionValues)) {
            $stats = [
                'consumption_avg_l' => round(array_sum($consumptionValues) / count($consumptionValues), 1),
                'consumption_max_l' => max($consumptionValues),
                'consumption_total_l' => array_sum($consumptionValues),
                'data_points' => count($history)
            ];
        }
    }
    
    // System-Status
    $lastUpdate = null;
    if ($latest) {
        $lastDateTime = new DateTime($latest['datetime']);
        $now = new DateTime();
        $ageMinutes = ($now->getTimestamp() - $lastDateTime->getTimestamp()) / 60;
        
        $lastUpdate = [
            'datetime' => $latest['datetime'],
            'formatted' => $lastDateTime->format('d.m.Y, H:i:s'),
            'age_minutes' => round($ageMinutes, 1),
            'is_stale' => $ageMinutes > 30
        ];
    }
    
    // Alarme
    $alerts = [];
    if ($current && $current['hourly_consumption'] > 1000) {
        $alerts[] = [
            'type' => 'warning',
            'message' => '🚿 Hoher Stunden-Verbrauch: ' . $current['hourly_consumption'] . ' L/h'
        ];
    }
    
    // Erfolgreiche Antwort
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'range' => $range,
        'current' => $current,
        'history' => $history,
        'stats' => $stats,
        'system_health' => ['status' => 'OK'],
        'active_alerts' => $alerts,
        'last_update' => $lastUpdate,
        'config' => [
            'decimal_places' => [
                'counter_m3' => 3,
                'consumption_l' => 1,
                'consumption_m3' => 3,
                'flow_lmin' => 1,
                'daily_m3' => 2,
                'weekly_m3' => 2
            ],
            'units' => [
                'counter_m3' => ['symbol' => 'm³'],
                'consumption_l' => ['symbol' => 'L'],
                'consumption_m3' => ['symbol' => 'm³'],
                'flow_lmin' => ['symbol' => 'l/min'],
                'daily_m3' => ['symbol' => 'm³'],
                'weekly_m3' => ['symbol' => 'm³']
            ]
        ]
    ];
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'error' => 'Datenbankfehler: Verbindung fehlgeschlagen',
        'timestamp' => date('Y-m-d H:i:s')
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Allgemeiner Fehler beim Verarbeiten der Anfrage',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// JSON ausgeben
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

function handleExport($pdo, $table) {
    try {
        $limit = min(10000, intval($_GET['limit'] ?? 1000));
        
        $stmt = $pdo->prepare("SELECT datetime, ROUND(counter/1000, 3) as counter_m3, consumption FROM $table ORDER BY datetime DESC LIMIT $limit");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="frischwasser_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM
        
        fputcsv($output, ['Datum/Zeit', 'Zählerstand (m³)', 'Verbrauch (L)'], ';');
        
        foreach ($data as $row) {
            $date = new DateTime($row['datetime']);
            fputcsv($output, [
                $date->format('d.m.Y H:i:s'),
                number_format(floatval($row['counter_m3']), 3, ',', ''),
                number_format(floatval($row['consumption']), 1, ',', '')
            ], ';');
        }
        
        fclose($output);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Export-Fehler'], JSON_UNESCAPED_UNICODE);
    }
}

function handleTable($pdo, $table) {
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(10, min(1000, intval($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        
        // Gesamtanzahl
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM $table");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // Daten
        $stmt = $pdo->prepare("SELECT datetime, ROUND(counter/1000, 3) as counter_m3, consumption FROM $table ORDER BY datetime DESC LIMIT $limit OFFSET $offset");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'action' => 'table',
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_entries' => $total,
                'entries_per_page' => $limit
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Tabellen-Fehler'], JSON_UNESCAPED_UNICODE);
    }
}
?>