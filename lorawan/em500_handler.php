<?php
// webhook_lorawan.php - Speichert UTC in Datenbank

// Hinweis: Timestamps werden als UTC gespeichert (best practice)
// Das Dashboard konvertiert sie beim Anzeigen nach Europe/Berlin

// Log-Datei
$logfile = 'logs/lorawan_data.log';
$dbfile = 'logs/lorawan_data.db';

// POST-Daten empfangen
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Strukturierte Daten extrahieren
$structured = [
    'timestamp' => gmdate('Y-m-d H:i:s'), // UTC Timestamp (best practice für Datenbanken)
    'device_eui' => $data['devEUI'] ?? 'unknown',
    'device_name' => $data['deviceName'] ?? 'unknown',
    'gateway_id' => $data['gateway_id'] ?? ($data['rxInfo'][0]['mac'] ?? 'unknown'),
    'rssi' => $data['rssi'] ?? ($data['rxInfo'][0]['rssi'] ?? null),
    'snr' => $data['snr'] ?? ($data['rxInfo'][0]['loRaSNR'] ?? null),
    'frequency' => $data['frequency'] ?? ($data['txInfo']['frequency'] ?? null),
    'dataRate' => $data['dataRate'] ?? null,
    'spreading_factor' => $data['spreading_factor'] ?? ($data['txInfo']['dataRate']['spreadFactor'] ?? null),
    'bandwidth' => $data['bandwidth'] ?? ($data['txInfo']['dataRate']['bandwidth'] ?? null),
    'frame_count' => $data['frame_count'] ?? ($data['fCnt'] ?? null),
    'port' => $data['port'] ?? ($data['fPort'] ?? null),
    'distance_mm' => $data['distance'] ?? null,
    'distance_m' => isset($data['distance']) ? round($data['distance'] / 1000, 3) : null,
    'is_valid' => isset($data['distance']) && $data['distance'] > 0 && $data['distance'] < 65535
];

// Output erstellen
$output = [
    'raw_data' => $data,
    'structured_data' => $structured,
    'separator' => str_repeat('-', 80)
];

// In Log-Datei schreiben (JSON Pretty Print)
file_put_contents(
    $logfile, 
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", 
    FILE_APPEND
);

// Optional: In SQLite-Datenbank speichern
try {
    $db = new PDO("sqlite:$dbfile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabelle erstellen (falls nicht vorhanden)
    $db->exec("
        CREATE TABLE IF NOT EXISTS measurements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT,
            device_eui TEXT,
            device_name TEXT,
            gateway_id TEXT,
            rssi INTEGER,
            snr REAL,
            frequency INTEGER,
            data_rate TEXT,
            frame_count INTEGER,
            port INTEGER,
            distance_mm INTEGER,
            distance_m REAL,
            is_valid BOOLEAN,
            raw_json TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Daten einfügen
    $stmt = $db->prepare("
        INSERT INTO measurements (
            timestamp, device_eui, device_name, gateway_id, rssi, snr,
            frequency, data_rate, frame_count, port, distance_mm, distance_m,
            is_valid, raw_json
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $structured['timestamp'],
        $structured['device_eui'],
        $structured['device_name'],
        $structured['gateway_id'],
        $structured['rssi'],
        $structured['snr'],
        $structured['frequency'],
        $structured['dataRate'],
        $structured['frame_count'],
        $structured['port'],
        $structured['distance_mm'],
        $structured['distance_m'],
        $structured['is_valid'] ? 1 : 0,
        $json
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// HTTP 200 OK zurückgeben
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success', 
    'received' => true,
    'device' => $structured['device_eui'],
    'distance_m' => $structured['distance_m'],
    'is_valid' => $structured['is_valid'],
    'timestamp_utc' => $structured['timestamp']
]);
?>