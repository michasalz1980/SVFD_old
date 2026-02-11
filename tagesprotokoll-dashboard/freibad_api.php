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

// Wartungs-Modus prüfen
if (isMaintenanceMode()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => getConfig('maintenance.maintenance_message'),
        'maintenance' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// IP-Whitelist prüfen
if (!isIpAllowed($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    logMessage("Zugriff verweigert für IP: " . $_SERVER['REMOTE_ADDR'], 'WARNING');
    echo json_encode([
        'success' => false,
        'error' => 'Zugriff nicht erlaubt',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Datenbankverbindung aus Konfiguration
    $db_config = getConfig('database');
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Parameter aus Request
    $type = $_GET['type'] ?? 'tagesprotokoll';
    $range = $_GET['range'] ?? getConfig('api.default_range', '7d');
    
    // Validierung der Parameter
    $allowed_types = ['tagesprotokoll', 'wasserqualitaet'];
    if (!in_array($type, $allowed_types)) {
        throw new Exception('Ungültiger Typ: ' . $type);
    }
    
    $time_intervals = getTimeIntervals();
    if (!isset($time_intervals[$range])) {
        throw new Exception('Ungültiger Zeitraum: ' . $range);
    }
    
    // Zeitintervall bestimmen aus Konfiguration
    $whereClause = '';
    if ($range !== 'all') {
        $interval = $time_intervals[$range];
        $whereClause = "WHERE Datum >= DATE_SUB(CURDATE(), INTERVAL {$interval})";
    }
    
    // Tabellennamen aus Konfiguration
    $tables = getDatabaseTables();
    
    if ($type === 'tagesprotokoll') {
        // Tagesprotokoll Daten laden
        $table = $tables['tagesprotokoll'];
        $columns = getConfig('tagesprotokoll.columns');
        $max_points = getConfig('api.max_data_points.tagesprotokoll', 1000);
        
        $stmt = $pdo->prepare("
            SELECT 
                {$columns['datum']} as Datum,
                {$columns['besucher']} as Tagesbesucherzahl,
                {$columns['lufttemperatur']} as Lufttemperatur,
                {$columns['temp_mzb']} as Temperatur_MZB,
                {$columns['temp_nsb']} as Temperatur_NSB,
                {$columns['temp_kkb']} as Temperatur_KKB,
                {$columns['zaehler_wasser']} as Zaehlerstand_Wasserleitungsnetz,
                {$columns['zaehler_abwasser']} as Zaehlerstand_Abwasser,
                {$columns['wetter_sonnig']} as Wetter_S,
                {$columns['wetter_heiter']} as Wetter_H,
                {$columns['wetter_bewoelkt']} as Wetter_B,
                {$columns['wetter_regen']} as Wetter_R,
                {$columns['wetter_gewitter']} as Wetter_G,
                {$columns['unterzeichner']} as Protokollunterzeichner,
                {$columns['bemerkungen']} as Bemerkungen
            FROM {$table} 
            {$whereClause}
            ORDER BY {$columns['datum']} DESC
            LIMIT {$max_points}
        ");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        logMessage("Tagesprotokoll abgerufen: " . count($data) . " Einträge für Zeitraum {$range}", 'INFO');
        
        // Aktuelle Werte (neuester Eintrag)
        $current = null;
        if (!empty($data)) {
            $current = $data[0];
        }
        
        // Wetter-Statistiken berechnen
        $wetter_labels = getConfig('tagesprotokoll.wetter_labels');
        $wetter_stats = [
            'sonnig' => 0,
            'heiter' => 0,
            'bewoelkt' => 0,
            'regen' => 0,
            'gewitter' => 0
        ];
        
        foreach ($data as $row) {
            if ($row['Wetter_S']) $wetter_stats['sonnig']++;
            if ($row['Wetter_H']) $wetter_stats['heiter']++;
            if ($row['Wetter_B']) $wetter_stats['bewoelkt']++;
            if ($row['Wetter_R']) $wetter_stats['regen']++;
            if ($row['Wetter_G']) $wetter_stats['gewitter']++;
        }
        
        // Statistiken berechnen mit Validierung
        $validation = getConfig('tagesprotokoll.validation');
        $stats = [];
        if (!empty($data)) {
            $besucher_values = array_filter(
                array_column($data, 'Tagesbesucherzahl'), 
                function($v) use ($validation) { 
                    return $v >= $validation['min_besucher'] && $v <= $validation['max_besucher']; 
                }
            );
            $temp_values = array_filter(
                array_column($data, 'Lufttemperatur'), 
                function($v) use ($validation) { 
                    return $v >= $validation['min_temperatur'] && $v <= $validation['max_temperatur']; 
                }
            );
            
            if (!empty($besucher_values)) {
                $stats['besucher'] = [
                    'avg' => round(array_sum($besucher_values) / count($besucher_values)),
                    'max' => max($besucher_values),
                    'min' => min($besucher_values),
                    'total' => array_sum($besucher_values)
                ];
            }
            
            if (!empty($temp_values)) {
                $stats['temperatur'] = [
                    'avg' => round(array_sum($temp_values) / count($temp_values), 1),
                    'max' => max($temp_values),
                    'min' => min($temp_values)
                ];
            }
        }
        
        $response = [
            'success' => true,
            'type' => 'tagesprotokoll',
            'range' => $range,
            'timestamp' => date('Y-m-d H:i:s'),
            'current' => $current,
            'data' => array_reverse($data), // Chronologisch sortieren
            'stats' => $stats,
            'wetter_stats' => $wetter_stats,
            'wetter_labels' => $wetter_labels,
            'count' => count($data),
            'system_info' => getSystemInfo()
        ];
        
    } else {
        // Wasserqualität Daten laden
        $table = $tables['wasserqualitaet'];
        $columns = getConfig('wasserqualitaet.columns');
        $max_points = getConfig('api.max_data_points.wasserqualitaet', 5000);
        $becken_config = getBeckenConfig();
        
        $whereClauseWQ = str_replace('Datum', $columns['datum'], $whereClause);
        
        $stmt = $pdo->prepare("
            SELECT 
                {$columns['datum']} as Datum,
                {$columns['uhrzeit']} as Uhrzeit,
                {$columns['becken']} as Becken,
                {$columns['cl_frei']} as Cl_frei,
                {$columns['cl_gesamt']} as Cl_gesamt,
                {$columns['ph_wert']} as pH_Wert,
                {$columns['redox_wert']} as Redox_Wert,
                {$columns['wasserhaerte']} as Wasserhaerte
            FROM {$table} 
            {$whereClauseWQ}
            AND {$columns['becken']} IN ('MZB', 'NSB', 'KKB')
            AND {$columns['ph_wert']} > 0
            ORDER BY {$columns['datum']} DESC, {$columns['uhrzeit']} DESC
            LIMIT {$max_points}
        ");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        logMessage("Wasserqualität abgerufen: " . count($data) . " Einträge für Zeitraum {$range}", 'INFO');
        
        // Aktuelle Werte (neueste Einträge pro Becken)
        $current = [];
        $becken_found = [];
        foreach ($data as $row) {
            if (!in_array($row['Becken'], $becken_found)) {
                $current[$row['Becken']] = $row;
                $becken_found[] = $row['Becken'];
            }
            if (count($becken_found) >= 3) break;
        }
        
        // Daten nach Becken gruppieren
        $grouped_data = [];
        foreach ($data as $row) {
            $date_key = $row['Datum'];
            if (!isset($grouped_data[$date_key])) {
                $grouped_data[$date_key] = [
                    'datum' => $date_key,
                    'MZB' => null,
                    'NSB' => null,
                    'KKB' => null
                ];
            }
            $grouped_data[$date_key][$row['Becken']] = $row;
        }
        
        // Statistiken berechnen mit Grenzwerten aus Konfiguration
        $grenzwerte = getGrenzwerte();
        $stats = [];
        
        $ph_values = array_filter(array_column($data, 'pH_Wert'), function($v) use ($grenzwerte) { 
            return $v >= $grenzwerte['ph']['min'] && $v <= $grenzwerte['ph']['max']; 
        });
        $cl_frei_values = array_filter(array_column($data, 'Cl_frei'), function($v) { return $v >= 0; });
        $redox_values = array_filter(array_column($data, 'Redox_Wert'), function($v) { return $v > 0; });
        
        if (!empty($ph_values)) {
            $stats['ph'] = [
                'avg' => round(array_sum($ph_values) / count($ph_values), 2),
                'max' => max($ph_values),
                'min' => min($ph_values),
                'in_range' => count(array_filter($ph_values, function($v) use ($grenzwerte) {
                    return $v >= $grenzwerte['ph']['optimal_min'] && $v <= $grenzwerte['ph']['optimal_max'];
                })),
                'total_count' => count($ph_values)
            ];
        }
        
        if (!empty($cl_frei_values)) {
            $stats['chlor_frei'] = [
                'avg' => round(array_sum($cl_frei_values) / count($cl_frei_values), 2),
                'max' => max($cl_frei_values),
                'min' => min($cl_frei_values),
                'in_range' => count(array_filter($cl_frei_values, function($v) use ($grenzwerte) {
                    return $v >= $grenzwerte['chlor_frei']['min'] && $v <= $grenzwerte['chlor_frei']['max'];
                })),
                'total_count' => count($cl_frei_values)
            ];
        }
        
        if (!empty($redox_values)) {
            $stats['redox'] = [
                'avg' => round(array_sum($redox_values) / count($redox_values)),
                'max' => max($redox_values),
                'min' => min($redox_values),
                'in_range' => count(array_filter($redox_values, function($v) use ($grenzwerte) {
                    return $v >= $grenzwerte['redox']['optimal_min'] && $v <= $grenzwerte['redox']['optimal_max'];
                })),
                'total_count' => count($redox_values)
            ];
        }
        
        $response = [
            'success' => true,
            'type' => 'wasserqualitaet',
            'range' => $range,
            'timestamp' => date('Y-m-d H:i:s'),
            'current' => $current,
            'data' => array_reverse($data), // Chronologisch sortieren
            'grouped_data' => array_reverse($grouped_data, true),
            'stats' => $stats,
            'grenzwerte' => $grenzwerte,
            'becken_config' => $becken_config,
            'count' => count($data),
            'system_info' => getSystemInfo()
        ];
    }
    
    // JSON ausgeben
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Datenbankfehler
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Allgemeiner Fehler
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Verarbeiten der Anfrage: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

/**
 * Formatiert Datum für Frontend
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Formatiert Datum und Zeit für Frontend
 */
function formatDateTime($date, $time) {
    return date('d.m.Y H:i', strtotime($date . ' ' . $time));
}

/**
 * Berechnet Durchschnittswerte
 */
function calculateAverage($values) {
    $filtered = array_filter($values, function($v) { return $v > 0; });
    return empty($filtered) ? 0 : array_sum($filtered) / count($filtered);
}

/**
 * System-Informationen
 */
function getSystemInfo() {
    return [
        'version' => '2.0.0',
        'php_version' => PHP_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
}
?>