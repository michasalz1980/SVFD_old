<?php
/**
 * CT103-868M LoRaWAN Data Handler
 * Empfängt Daten vom UG65-EA Gateway und speichert in SQLite
 * 
 * Gateway-Konfiguration:
 * URL: https://personal.freibad-dabringhausen.de/lorawan/ct103_handler.php
 * Method: POST
 * Content-Type: application/json
 */

// ========== KONFIGURATION ==========
define('DEBUG_MODE', true);
define('DB_FILE', __DIR__ . 'logs/lorawan_ct103.db');
define('LOG_FILE', __DIR__ . 'logs/ct103_' . date('Y-m-d') . '.log');

// Erstelle Log-Verzeichnis falls nicht vorhanden
if (!is_dir(__DIR__ . 'logs')) {
    mkdir(__DIR__ . 'logs', 0755, true);
}

// ========== SQLITE DATENBANK INITIALISIEREN ==========

function init_database() {
    try {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Erstelle ct103_data Tabelle
        $db->exec("
            CREATE TABLE IF NOT EXISTS ct103_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                unix_timestamp INTEGER NOT NULL,
                device_eui TEXT NOT NULL,
                device_name TEXT,
                current_ma INTEGER,
                current_a REAL,
                power_w INTEGER,
                energy_wh INTEGER,
                energy_kwh REAL,
                rssi INTEGER,
                snr REAL,
                spreading_factor INTEGER,
                bandwidth INTEGER,
                raw_payload TEXT,
                status TEXT DEFAULT 'valid',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(unix_timestamp, device_eui)
            );
        ");
        
        // Erstelle Indizes für schnellere Abfragen
        $db->exec("CREATE INDEX IF NOT EXISTS idx_timestamp ON ct103_data(timestamp DESC)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_device_eui ON ct103_data(device_eui)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_power_w ON ct103_data(power_w)");
        
        return $db;
    } catch (Exception $e) {
        log_error("Database init failed: " . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Database error']));
    }
}

// ========== LOGGING FUNKTION ==========

function log_error($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[$timestamp] $message\n";
    file_put_contents(LOG_FILE, $log_line, FILE_APPEND);
    if (DEBUG_MODE) {
        error_log($log_line);
    }
}

// ========== PAYLOAD DEKODIERUNG (CT103) - Offizielles Milesight-Format ==========
// Basierend auf: https://resource.milesight.com (CT101/CT103/CT105 Payload Decoder)

function decode_ct103_payload($hex_payload) {
    if (empty($hex_payload)) {
        return [];
    }
    
    try {
        $bytes = hex2bin($hex_payload);
        $decoded = [];
        $i = 0;
        
        while ($i < strlen($bytes)) {
            if ($i + 1 >= strlen($bytes)) break;
            
            $channel_id = ord($bytes[$i++]);
            $channel_type = ord($bytes[$i++]);
            
            // CT103 Payload-Typen (Offizielles Milesight-Format)
            // Channel 0x03, Type 0x97 = Total Current (4 bytes, Little-Endian, /100)
            // Channel 0x04, Type 0x98 = Current (2 bytes, Little-Endian, /100)
            // Channel 0x09, Type 0x67 = Temperature (2 bytes, Little-Endian, /10)
            // Channel 0xff, Type 0x0a = Firmware Version (2 bytes)
            
            // TOTAL CURRENT
            if ($channel_id === 0x03 && $channel_type === 0x97) {
                if ($i + 3 < strlen($bytes)) {
                    $value = readUInt32LE(array_slice(array_map('ord', str_split($bytes)), $i, 4));
                    $decoded['total_current_ma'] = $value;
                    $decoded['total_current_a'] = $value / 100;
                    $i += 4;
                }
            }
            // CURRENT
            elseif ($channel_id === 0x04 && $channel_type === 0x98) {
                if ($i + 1 < strlen($bytes)) {
                    $value = readUInt16LE(array_slice(array_map('ord', str_split($bytes)), $i, 2));
                    if ($value === 0xffff) {
                        $decoded['current_sensor_status'] = 'read_failed';
                    } else {
                        $decoded['current_ma'] = $value;
                        $decoded['current_a'] = $value / 100;
                    }
                    $i += 2;
                }
            }
            // TEMPERATURE
            elseif ($channel_id === 0x09 && $channel_type === 0x67) {
                if ($i + 1 < strlen($bytes)) {
                    $value = readInt16LE(array_slice(array_map('ord', str_split($bytes)), $i, 2));
                    if ($value === 0xfffd) {
                        $decoded['temperature_sensor_status'] = 'over_range_alarm';
                    } elseif ($value === 0xffff) {
                        $decoded['temperature_sensor_status'] = 'read_failed';
                    } else {
                        $decoded['temperature_c'] = $value / 10;
                    }
                    $i += 2;
                }
            }
            // FIRMWARE VERSION
            elseif ($channel_id === 0xff && $channel_type === 0x0a) {
                if ($i + 1 < strlen($bytes)) {
                    $major = ord($bytes[$i]) & 0xff;
                    $minor = (ord($bytes[$i+1]) & 0xff);
                    $decoded['firmware_version'] = "v" . dechex($major) . "." . dechex($minor);
                    $i += 2;
                }
            }
            else {
                // Unbekannter Typ/Channel, skippe 2 bytes
                break;
            }
        }
        
        return $decoded;
    } catch (Exception $e) {
        log_error("Payload decode error: " . $e->getMessage());
        return [];
    }
}

// Helper-Funktionen für Byte-Konvertierung
function readUInt32LE($bytes) {
    return ($bytes[3] << 24) | ($bytes[2] << 16) | ($bytes[1] << 8) | $bytes[0];
}

function readInt32LE($bytes) {
    $ref = readUInt32LE($bytes);
    return $ref > 0x7fffffff ? $ref - 0x100000000 : $ref;
}

function readUInt16LE($bytes) {
    return ($bytes[1] << 8) | $bytes[0];
}

function readInt16LE($bytes) {
    $ref = readUInt16LE($bytes);
    return $ref > 0x7fff ? $ref - 0x10000 : $ref;
}

// ========== HAUPTLOGIK ==========

// Lese JSON vom Gateway
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validierung
if (!$data) {
    log_error("ERROR: Kein JSON empfangen oder JSON ungültig");
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Invalid JSON']));
}

// Initialisiere Datenbank
$db = init_database();

// Extrahiere Basis-Daten
$device_eui = $data['devEUI'] ?? 'UNKNOWN';
$device_name = $data['deviceName'] ?? 'CT103_Unknown';
$timestamp = date('Y-m-d H:i:s');
$unix_timestamp = time();
$port = $data['fPort'] ?? 85;

// Dekodiere Payload
$decoded = decode_ct103_payload($data['payload'] ?? '');

// Baue Daten-Array
$log_entry = [
    'timestamp' => $timestamp,
    'unix_timestamp' => $unix_timestamp,
    'device_eui' => $device_eui,
    'device_name' => $device_name,
    'port' => $port,
    'current_a' => $decoded['current_a'] ?? null,
    'current_ma' => $decoded['current_ma'] ?? null,
    'power_w' => $decoded['power_w'] ?? null,
    'energy_wh' => $decoded['energy_wh'] ?? null,
    'energy_kwh' => $decoded['energy_kwh'] ?? null,
    'rssi' => $data['rssi'] ?? null,
    'snr' => $data['snr'] ?? null,
    'spreading_factor' => $data['spreadingFactor'] ?? null,
    'bandwidth' => $data['bandwidth'] ?? null,
    'raw_payload' => $data['payload'] ?? null,
    'status' => 'valid'
];

// ========== DATENBANK SPEICHERN ==========

try {
    // Dekodierte Werte auslesen
    $current_a = $decoded['current_a'] ?? null;
    $current_ma = $decoded['current_ma'] ?? null;
    $temperature_c = $decoded['temperature_c'] ?? null;
    $total_current_a = $decoded['total_current_a'] ?? null;
    
    // Falls nur Total Current da ist, verwende diese für Current
    if ($current_a === null && $total_current_a !== null) {
        $current_a = $total_current_a;
        $current_ma = $total_current_a * 1000;
    }
    
    $stmt = $db->prepare("
        INSERT INTO ct103_data 
        (unix_timestamp, device_eui, device_name, current_ma, current_a, 
         power_w, energy_wh, energy_kwh, rssi, snr, spreading_factor, 
         bandwidth, raw_payload, status)
        VALUES 
        (:unix_ts, :eui, :name, :current_ma, :current_a, 
         :power_w, :energy_wh, :energy_kwh, :rssi, :snr, :sf, 
         :bw, :payload, :status)
    ");
    
    $stmt->execute([
        ':unix_ts' => $unix_timestamp,
        ':eui' => $device_eui,
        ':name' => $device_name,
        ':current_ma' => $current_ma,
        ':current_a' => $current_a,
        ':power_w' => null,  // CT103 sendet keine Leistung direkt
        ':energy_wh' => null,
        ':energy_kwh' => null,
        ':rssi' => $data['rssi'] ?? null,
        ':snr' => $data['snr'] ?? null,
        ':sf' => $data['spreadingFactor'] ?? null,
        ':bw' => $data['bandwidth'] ?? null,
        ':payload' => $data['payload'] ?? null,
        ':status' => 'valid'
    ]);
    
    log_error("✓ Data saved - Device: {$device_eui}, Current: {$current_a}A");
    
} catch (PDOException $e) {
    // Duplicate-Entry ist ok
    if (strpos($e->getMessage(), 'UNIQUE') !== false) {
        log_error("⚠ Duplicate entry (retry) - Device: {$device_eui}");
    } else {
        log_error("✗ Database error: " . $e->getMessage());
    }
}

// ========== RESPONSE AN GATEWAY ==========

header('Content-Type: application/json');
http_response_code(200);

$response = [
    'status' => 'received',
    'device_eui' => $device_eui,
    'device_name' => $device_name,
    'timestamp' => $timestamp,
    'power_w' => $log_entry['power_w'],
    'current_a' => $log_entry['current_a'],
];

echo json_encode($response);

log_error("✓ HTTP 200 response sent to Gateway");

// Schließe DB-Verbindung
$db = null;
?>
