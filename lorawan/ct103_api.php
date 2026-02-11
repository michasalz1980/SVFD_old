<?php
/**
 * CT103-868M Data API
 * Liefert die letzten 100 Messwerte aus SQLite für das Dashboard
 */

header('Content-Type: application/json');

define('DB_FILE', __DIR__ . 'logs/lorawan_ct103.db');

try {
    // Verbindung zur SQLite-Datenbank
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Überprüfe, ob Tabelle existiert
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ct103_data'")->fetchAll();
    
    if (empty($tables)) {
        http_response_code(404);
        die(json_encode(['error' => 'Keine Daten verfügbar']));
    }
    
    // Hole die letzten 100 Messwerte (sortiert: neueste zuerst)
    $stmt = $db->prepare("
        SELECT 
            id,
            timestamp,
            unix_timestamp,
            device_eui,
            device_name,
            current_ma,
            current_a,
            power_w,
            energy_wh,
            energy_kwh,
            rssi,
            snr,
            spreading_factor,
            bandwidth,
            raw_payload,
            status
        FROM ct103_data
        ORDER BY unix_timestamp DESC
        LIMIT 100
    ");
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Konvertiere zu korrektem Format
    $data = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'timestamp' => $row['timestamp'],
            'unix_timestamp' => (int)$row['unix_timestamp'],
            'device_eui' => $row['device_eui'],
            'device_name' => $row['device_name'],
            'current_ma' => $row['current_ma'] !== null ? (int)$row['current_ma'] : null,
            'current_a' => $row['current_a'] !== null ? (float)$row['current_a'] : null,
            'power_w' => $row['power_w'] !== null ? (int)$row['power_w'] : null,
            'energy_wh' => $row['energy_wh'] !== null ? (int)$row['energy_wh'] : null,
            'energy_kwh' => $row['energy_kwh'] !== null ? (float)$row['energy_kwh'] : null,
            'rssi' => $row['rssi'] !== null ? (int)$row['rssi'] : null,
            'snr' => $row['snr'] !== null ? (float)$row['snr'] : null,
            'spreading_factor' => $row['spreading_factor'] !== null ? (int)$row['spreading_factor'] : null,
            'bandwidth' => $row['bandwidth'],
            'raw_payload' => $row['raw_payload'],
            'status' => $row['status']
        ];
    }, $rows);
    
    // Gebe JSON zurück
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbank-Fehler: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
}
?>
