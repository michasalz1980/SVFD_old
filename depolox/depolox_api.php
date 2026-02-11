<?php
// depolox_api.php - Ultra-robuste Backend API für Depolox Dashboard
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Debug-Modus (für Entwicklung auf true setzen)
$DEBUG_MODE = false;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', $DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);

// Debug-Funktion
function debug_log($message) {
    global $DEBUG_MODE;
    if ($DEBUG_MODE) {
        error_log("DEPOLOX DEBUG: " . $message);
    }
}

// Datenbankverbindung
$config = [
    'host' => 'localhost',
    'username' => 'svfd_Schedule',
    'password' => 'REDACTED',
    'database' => 'svfd_schedule',
    'charset' => 'utf8mb4'
];

$pdo = null;
$db_connected = false;

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $db_connected = true;
    debug_log("Database connected successfully");
} catch (PDOException $e) {
    debug_log("Database connection failed: " . $e->getMessage());
    $db_connected = false;
}

// API-Endpunkt bestimmen
$endpoint = $_GET['endpoint'] ?? '';
debug_log("Endpoint requested: " . $endpoint);

// Prüfe ob Datenbank verfügbar ist
if (!$db_connected || !$pdo) {
    debug_log("No database connection - using demo mode");
    echo json_encode([
        'status' => 'success',
        'demo_mode' => true,
        'message' => 'Datenbank nicht verfügbar - Demo-Modus aktiv',
        'data' => getDemoData($endpoint),
        'total_count' => $endpoint === 'active_alarms' ? 2 : null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    switch ($endpoint) {
        case 'current_values':
            getCurrentValues($pdo);
            break;
        case 'system_health':
            getSystemHealth($pdo);
            break;
        case 'active_alarms':
            getActiveAlarms($pdo);
            break;
        case 'recommendations':
            getRecommendations($pdo);
            break;
        case 'trends':
            getTrends($pdo);
            break;
        case 'system_comparison':
            getSystemComparison($pdo);
            break;
        case 'database_info':
            getDatabaseInfo($pdo);
            break;
        case 'debug_data':
            getDebugData($pdo);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    debug_log("API Error in endpoint $endpoint: " . $e->getMessage());
    echo json_encode([
        'status' => 'success',
        'demo_mode' => true,
        'message' => 'Datenbankfehler - Demo-Modus aktiv',
        'data' => getDemoData($endpoint),
        'total_count' => $endpoint === 'active_alarms' ? 2 : null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Demo-Daten für alle Endpunkte
function getDemoData($endpoint) {
    switch ($endpoint) {
        case 'current_values':
            return [
                [
                    'name' => 'Schwimmer',
                    'parameters' => [
                        ['name' => 'Chlor (Cl2)', 'type' => 'chlorine', 'value' => 0.04, 'unit' => 'mg/l', 'setpoint' => 0.6, 'dosing' => 45, 'status' => 'critical', 'timestamp' => date('Y-m-d H:i:s'), 'formatted_time' => date('d.m.Y H:i')],
                        ['name' => 'pH-Wert', 'type' => 'ph', 'value' => 7.8, 'unit' => 'pH', 'setpoint' => 7.2, 'dosing' => 12, 'status' => 'warning', 'timestamp' => date('Y-m-d H:i:s'), 'formatted_time' => date('d.m.Y H:i')],
                        ['name' => 'Redox-Potential', 'type' => 'redox', 'value' => 685, 'unit' => 'mV', 'setpoint' => 770, 'dosing' => null, 'status' => 'critical', 'timestamp' => date('Y-m-d H:i:s'), 'formatted_time' => date('d.m.Y H:i')],
                        ['name' => 'Temperatur', 'type' => 'temperature', 'value' => 24.5, 'unit' => '°C', 'setpoint' => null, 'dosing' => null, 'status' => 'excellent', 'timestamp' => date('Y-m-d H:i:s'), 'formatted_time' => date('d.m.Y H:i')]
                    ],
                    'last_update' => date('Y-m-d H:i:s')
                ]
            ];
            
        case 'system_health':
            return [
                [
                    'system_name' => 'Schwimmer',
                    'location_description' => 'Hauptbecken',
                    'active_error_count' => 2,
                    'max_error_severity' => 'WARNING',
                    'connection_status' => 'ONLINE',
                    'health_status' => 'warning',
                    'minutes_since_update' => 2,
                    'last_status_update' => date('Y-m-d H:i:s')
                ]
            ];
            
        case 'active_alarms':
            return [
                [
                    'system_name' => 'Schwimmer',
                    'error_category' => 'POOL_CHEMISTRY',
                    'error_code' => 'LOW_CHLORINE',
                    'error_description' => 'Kritisch niedrige Chlor-Konzentration',
                    'severity' => 'CRITICAL',
                    'last_occurrence' => date('Y-m-d H:i:s'),
                    'minutes_ago' => 0,
                    'value' => 0.04,
                    'unit' => 'mg/l',
                    'occurrence_count' => 1
                ],
                [
                    'system_name' => 'Schwimmer',
                    'error_category' => 'POOL_CHEMISTRY',
                    'error_code' => 'REDOX_LOW',
                    'error_description' => 'Redox-Potential unter Sollwert von 770 mV',
                    'severity' => 'CRITICAL',
                    'last_occurrence' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
                    'minutes_ago' => 2,
                    'value' => 685,
                    'unit' => 'mV',
                    'occurrence_count' => 1
                ]
            ];
            
        case 'recommendations':
            return [
                [
                    'system' => 'Schwimmer',
                    'actions' => [
                        [
                            'type' => 'critical',
                            'title' => 'Sofortiger Chlor-Schock erforderlich',
                            'description' => 'Chlor-Konzentration kritisch niedrig. Manuelle Chlorzugabe und Systemprüfung erforderlich.',
                            'steps' => [
                                'Sofort 0.5-1.0 mg/l Chlor manuell zugeben',
                                'Wasserzirkulation prüfen',
                                'Chlor-Dosieranlage überprüfen',
                                'Nach 2 Stunden erneut messen'
                            ]
                        ],
                        [
                            'type' => 'critical',
                            'title' => 'Redox-Potential kritisch niedrig',
                            'description' => 'Redox-Wert von 685 mV liegt unter dem Sollwert von 770 mV. Sofortige Maßnahmen erforderlich.',
                            'steps' => [
                                'Chlor-Konzentration erhöhen',
                                'pH-Wert auf 7.0-7.2 einstellen',
                                'Wasserzirkulation intensivieren',
                                'Nach 1 Stunde Redox-Wert erneut messen'
                            ]
                        ]
                    ],
                    'priority' => 'critical',
                    'overall_status' => 'critical'
                ]
            ];
            
        case 'trends':
            $hours = $_GET['hours'] ?? 24;
            return [
                'Schwimmer' => [
                    'chlorine' => [
                        'name' => 'Chlor (Cl2)',
                        'unit' => 'mg/l',
                        'data' => generateDemoTrends(0.04, $hours)
                    ],
                    'ph' => [
                        'name' => 'pH-Wert', 
                        'unit' => 'pH',
                        'data' => generateDemoTrends(7.8, $hours)
                    ],
                    'redox' => [
                        'name' => 'Redox-Potential',
                        'unit' => 'mV', 
                        'data' => generateDemoTrends(685, $hours)
                    ],
                    'temperature' => [
                        'name' => 'Temperatur',
                        'unit' => '°C',
                        'data' => generateDemoTrends(24.5, $hours)
                    ]
                ]
            ];
            
        case 'system_comparison':
            return [
                [
                    'system_name' => 'Schwimmer',
                    'chlorine' => 0.04,
                    'ph' => 7.8,
                    'redox' => 685,
                    'temperature' => 24.5,
                    'chlorine_dosing' => 45,
                    'ph_dosing' => 12,
                    'scores' => [
                        'chlorine' => 20,
                        'ph' => 70,
                        'redox' => 20, // Redox unter 770 = schlecht
                        'temperature' => 100
                    ],
                    'overall_score' => 52.5,
                    'grade' => 'D',
                    'last_update' => date('Y-m-d H:i:s')
                ]
            ];
            
        default:
            return [];
    }
}

function generateDemoTrends($baseValue, $hours) {
    $data = [];
    $now = new DateTime();
    
    for ($i = $hours; $i >= 0; $i--) {
        $timestamp = clone $now;
        $timestamp->sub(new DateInterval("PT{$i}H"));
        $variation = (mt_rand() / mt_getrandmax() - 0.5) * 0.2 * $baseValue;
        
        $data[] = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'time' => $timestamp->format('H:i'),
            'value' => max(0, round($baseValue + $variation, 2)),
            'setpoint' => getCorrectSetpoint($baseValue) // Korrigierte Sollwerte
        ];
    }
    
    return $data;
}

function getCorrectSetpoint($baseValue) {
    // Bestimme Sollwerte basierend auf dem Basiswert
    if ($baseValue < 1) {
        return 0.6; // Chlor
    } elseif ($baseValue < 15) {
        return 7.2; // pH
    } elseif ($baseValue < 100) {
        return 25.0; // Temperatur
    } else {
        return 770; // Redox - NEUER SOLLWERT
    }
}

// Bestimme Parameter-Status basierend auf Typ und Wert
// ANGEPASST: Redox < 770 ist jetzt critical
function determineParameterStatus($type, $value) {
    switch ($type) {
        case 'chlorine':
            if ($value < 0.2) return 'critical';
            if ($value < 0.3 || $value > 1.5) return 'warning';
            if ($value >= 0.4 && $value <= 1.0) return 'excellent';
            return 'warning';
            
        case 'ph':
            if ($value < 6.5 || $value > 8.0) return 'critical';
            if ($value < 6.8 || $value > 7.8) return 'warning';
            if ($value >= 7.0 && $value <= 7.4) return 'excellent';
            return 'warning';
            
        case 'redox':
            // NEUE LOGIK: < 770 ist critical (User Story 1)
            if ($value < 770) return 'critical';
            if ($value > 900) return 'warning';
            if ($value >= 770 && $value <= 850) return 'excellent';
            return 'warning';
            
        case 'temperature':
            if ($value < 15 || $value > 35) return 'warning';
            if ($value >= 20 && $value <= 28) return 'excellent';
            return 'warning';
            
        default:
            return 'excellent';
    }
}

// Prüfe ob Tabelle existiert
function tableExists($pdo, $tableName) {
    try {
        debug_log("Checking table existence for: $tableName");
        
        // Methode 1: SHOW TABLES LIKE (Original)
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $result1 = $stmt->rowCount() > 0;
        debug_log("Method 1 (SHOW TABLES LIKE): $tableName = " . ($result1 ? 'YES' : 'NO'));
        
        // Methode 2: information_schema (Fallback)
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
        ");
        $stmt2->execute([$tableName]);
        $row = $stmt2->fetch();
        $result2 = $row['count'] > 0;
        debug_log("Method 2 (information_schema): $tableName = " . ($result2 ? 'YES' : 'NO'));
        
        // Methode 3: Direkte Abfrage (Views)
        if (strpos($tableName, 'v_') === 0) {
            // Für Views verwenden wir information_schema.VIEWS
            try {
                $stmt3 = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM information_schema.VIEWS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ?
                ");
                $stmt3->execute([$tableName]);
                $row3 = $stmt3->fetch();
                $result3 = $row3['count'] > 0;
                debug_log("Method 3 (VIEWS): $tableName = " . ($result3 ? 'YES' : 'NO'));
                
                // Für Views verwenden wir das beste Ergebnis
                return $result3 || $result2 || $result1;
            } catch (Exception $e) {
                debug_log("View check failed for $tableName: " . $e->getMessage());
            }
        }
        
        // Für normale Tabellen: Wenn eine Methode true zurückgibt, existiert die Tabelle
        $exists = $result1 || $result2;
        debug_log("Final result for $tableName: " . ($exists ? 'EXISTS' : 'NOT FOUND'));
        
        return $exists;
        
    } catch (Exception $e) {
        debug_log("Error checking table $tableName: " . $e->getMessage());
        
        // Notfall-Fallback: Direkte Tabellenabfrage
        try {
            debug_log("Attempting direct table query for $tableName");
            $stmt = $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
            debug_log("Direct query successful for $tableName - table exists!");
            return true;
        } catch (Exception $e2) {
            debug_log("Direct query failed for $tableName: " . $e2->getMessage());
            return false;
        }
    }
}

// Aktuelle Werte aller Systeme
function getCurrentValues($pdo) {
    debug_log("Loading current values...");
    
    try {
        // Prüfe verfügbare Tabellen mit der neuen Funktion
        $availableTables = [];
        $checkTables = ['v_latest_depolox_measurements', 'depolox_measurements', 'depolox_systems', 'depolox_measurement_types'];
        
        foreach ($checkTables as $table) {
            if (tableExists($pdo, $table)) {
                $availableTables[] = $table;
                debug_log("✅ Table available: $table");
            } else {
                debug_log("❌ Table missing: $table");
            }
        }
        debug_log("Available tables: " . implode(', ', $availableTables));
        
        // Wenn weniger als 3 Grundtabellen verfügbar sind, verwende Demo-Daten
        if (count($availableTables) < 3) {
            debug_log("Insufficient tables found (" . count($availableTables) . "), using demo data");
            throw new Exception("Insufficient tables found for real data");
        }
        
        // Prüfe ob aktuelle Daten vorhanden sind
        $hasData = false;
        if (in_array('depolox_measurements', $availableTables)) {
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM depolox_measurements WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                $countResult = $countStmt->fetch();
                $hasData = $countResult['count'] > 0;
                debug_log("Found " . $countResult['count'] . " measurements in last 24 hours");
            } catch (Exception $e) {
                debug_log("Error counting measurements: " . $e->getMessage());
            }
        }
        
        if (!$hasData) {
            debug_log("No recent measurement data found, using demo data");
            throw new Exception("No recent measurement data found");
        }
        
        // Versuche zuerst die View (falls verfügbar)
        if (in_array('v_latest_depolox_measurements', $availableTables)) {
            debug_log("Using view v_latest_depolox_measurements");
            $sql = "
                SELECT 
                    system_name,
                    display_name as parameter,
                    ROUND(measured_value, 2) as value,
                    unit,
                    ROUND(setpoint_value, 2) as setpoint,
                    ROUND(dosing_power, 1) as dosing,
                    COALESCE(status_level, 'OK') as status_level,
                    type_name,
                    timestamp
                FROM v_latest_depolox_measurements
                ORDER BY system_name, 
                    CASE type_name 
                        WHEN 'chlorine' THEN 1 
                        WHEN 'ph' THEN 2 
                        WHEN 'redox' THEN 3 
                        WHEN 'temperature' THEN 4 
                        ELSE 5 
                    END
                LIMIT 100
            ";
        } 
        // Fallback auf Basistabellen
        else if (count($availableTables) >= 3) {
            debug_log("Using base tables");
            $sql = "
                SELECT 
                    s.system_name,
                    COALESCE(mt.display_name, mt.type_name, 'Parameter') as parameter,
                    ROUND(m.measured_value, 2) as value,
                    COALESCE(mt.unit, '') as unit,
                    ROUND(m.setpoint_value, 2) as setpoint,
                    ROUND(m.dosing_power, 1) as dosing,
                    'OK' as status_level,
                    COALESCE(mt.type_name, 'unknown') as type_name,
                    m.timestamp
                FROM depolox_measurements m
                JOIN depolox_systems s ON m.system_id = s.system_id
                LEFT JOIN depolox_measurement_types mt ON m.measurement_type_id = mt.measurement_type_id
                WHERE m.timestamp >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
                  AND s.is_active = 1
                ORDER BY s.system_name, mt.type_name, m.timestamp DESC
                LIMIT 200
            ";
        } else {
            throw new Exception("Not enough tables available");
        }
        
        debug_log("Executing SQL query...");
        $stmt = $pdo->query($sql);
        $measurements = $stmt->fetchAll();
        debug_log("Found " . count($measurements) . " measurements");
        
        if (empty($measurements)) {
            debug_log("No measurements found, using demo data");
            throw new Exception("No measurements found in tables");
        }
        
        // Gruppiere nach System (wie im Original)
        $systems = [];
        foreach ($measurements as $measurement) {
            $systemName = $measurement['system_name'];
            if (!isset($systems[$systemName])) {
                $systems[$systemName] = [
                    'name' => $systemName,
                    'parameters' => [],
                    'last_update' => $measurement['timestamp']
                ];
            }
            
            // Bestimme Status basierend auf Werten
            $status = determineParameterStatus($measurement['type_name'], (float)$measurement['value']);
            
            $systems[$systemName]['parameters'][] = [
                'name' => $measurement['parameter'],
                'type' => $measurement['type_name'],
                'value' => (float)$measurement['value'],
                'unit' => $measurement['unit'],
                'setpoint' => $measurement['setpoint'] ? (float)$measurement['setpoint'] : null,
                'dosing' => $measurement['dosing'] ? (float)$measurement['dosing'] : null,
                'status' => $status,
                'timestamp' => $measurement['timestamp'],
                'formatted_time' => date('d.m.Y H:i', strtotime($measurement['timestamp'])) // User Story 2: Formatierter Zeitstempel
            ];
        }
        
        debug_log("Processed " . count($systems) . " systems");
        debug_log("🎉 SUCCESS: Real data loaded and processed!");
        
        echo json_encode([
            'status' => 'success',
            'data' => array_values($systems),
            'source' => 'real_database',
            'tables_used' => $availableTables,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getCurrentValues error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Fallback zu Demo-Daten: ' . $e->getMessage(),
            'data' => getDemoData('current_values'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// System-Gesundheitsstatus
function getSystemHealth($pdo) {
    debug_log("Loading system health...");
    
    try {
        // Verwende die gleiche Logik wie bei getCurrentValues - das funktioniert ja!
        debug_log("Checking if depolox_systems table exists...");
        
        if (!tableExists($pdo, 'depolox_systems')) {
            debug_log("❌ depolox_systems table not found");
            throw new Exception("depolox_systems table not found");
        }
        
        debug_log("✅ depolox_systems table exists, loading data...");
        
        // Einfache, direkte Query basierend auf dem erfolgreichen getCurrentValues Ansatz
        $sql = "
            SELECT 
                s.system_name,
                COALESCE(s.location_description, 'Pool-System') as location_description,
                0 as active_error_count,
                'NONE' as max_error_severity,
                CASE 
                    WHEN s.is_active = 1 THEN 'ONLINE'
                    ELSE 'OFFLINE'
                END as connection_status,
                CASE 
                    WHEN s.is_active = 1 THEN 'excellent'
                    ELSE 'critical'
                END as health_status,
                COALESCE(TIMESTAMPDIFF(MINUTE, s.last_updated, NOW()), 0) as minutes_since_update,
                COALESCE(s.last_updated, NOW()) as last_status_update
            FROM depolox_systems s
            WHERE s.is_active = 1
            ORDER BY s.system_name
        ";
        
        debug_log("Executing system health query...");
        $stmt = $pdo->query($sql);
        $health = $stmt->fetchAll();
        debug_log("Found " . count($health) . " systems for health check");
        
        if (empty($health)) {
            debug_log("❌ No systems found in health check");
            throw new Exception("No active systems found");
        }
        
        // Erweitere um Error-Count falls error_logs Tabelle existiert
        if (tableExists($pdo, 'depolox_error_logs')) {
            debug_log("✅ Adding error counts from depolox_error_logs");
            foreach ($health as &$system) {
                try {
                    $errorStmt = $pdo->prepare("
                        SELECT COUNT(*) as error_count,
                               MAX(severity) as max_severity
                        FROM depolox_error_logs el
                        JOIN depolox_systems s ON el.system_id = s.system_id  
                        WHERE s.system_name = ? AND el.is_active = 1
                    ");
                    $errorStmt->execute([$system['system_name']]);
                    $errorData = $errorStmt->fetch();
                    
                    if ($errorData && $errorData['error_count'] > 0) {
                        $system['active_error_count'] = (int)$errorData['error_count'];
                        $system['max_error_severity'] = $errorData['max_severity'] ?: 'WARNING';
                        $system['health_status'] = $errorData['max_severity'] === 'CRITICAL' ? 'critical' : 'warning';
                    }
                } catch (Exception $e) {
                    debug_log("Error checking error logs for {$system['system_name']}: " . $e->getMessage());
                }
            }
        }
        
        debug_log("🎉 SUCCESS: System health data loaded successfully!");
        
        echo json_encode([
            'status' => 'success',
            'data' => $health,
            'source' => 'real_database',
            'systems_found' => count($health),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getSystemHealth error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Systemdaten-Fehler - Demo-Modus: ' . $e->getMessage(),
            'data' => getDemoData('system_health'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Aktive Alarme und Fehler
function getActiveAlarms($pdo) {
    debug_log("Loading active alarms...");
    
    try {
        $alarms = [];
        
        // Erstelle Alarme basierend auf aktuellen Messwerten (wie bei current_values)
        if (tableExists($pdo, 'v_latest_depolox_measurements')) {
            debug_log("✅ Using v_latest_depolox_measurements for alarms");
            
            $sql = "
                SELECT 
                    system_name,
                    type_name,
                    display_name,
                    measured_value,
                    unit,
                    timestamp
                FROM v_latest_depolox_measurements
                WHERE (
                    (type_name = 'chlorine' AND (measured_value < 0.3 OR measured_value > 1.5)) OR
                    (type_name = 'ph' AND (measured_value < 6.8 OR measured_value > 7.8)) OR
                    (type_name = 'redox' AND measured_value < 770) OR
                    (type_name = 'temperature' AND (measured_value < 18 OR measured_value > 32))
                )
            ";
            
            $stmt = $pdo->query($sql);
            $problematicValues = $stmt->fetchAll();
            
            foreach ($problematicValues as $value) {
                $severity = 'WARNING';
                $description = '';
                
                switch ($value['type_name']) {
                    case 'chlorine':
                        if ($value['measured_value'] < 0.2) {
                            $severity = 'CRITICAL';
                            $description = 'Kritisch niedrige Chlor-Konzentration';
                        } else if ($value['measured_value'] < 0.3) {
                            $description = 'Niedrige Chlor-Konzentration';
                        } else {
                            $description = 'Chlor-Konzentration zu hoch';
                        }
                        break;
                    case 'ph':
                        if ($value['measured_value'] < 6.5 || $value['measured_value'] > 8.0) {
                            $severity = 'CRITICAL';
                        }
                        $description = $value['measured_value'] < 7 ? 'pH-Wert zu niedrig' : 'pH-Wert zu hoch';
                        break;
                    case 'redox':
                        // ANGEPASST: Redox < 770 ist jetzt critical (User Story 1)
                        $severity = 'CRITICAL';
                        $description = 'Redox-Potential unter Sollwert von 770 mV';
                        break;
                    case 'temperature':
                        $description = $value['measured_value'] < 20 ? 'Wassertemperatur zu niedrig' : 'Wassertemperatur zu hoch';
                        break;
                }
                
                $alarms[] = [
                    'system_name' => $value['system_name'],
                    'error_category' => 'POOL_CHEMISTRY',
                    'error_code' => strtoupper($value['type_name']) . '_OUT_OF_RANGE',
                    'error_description' => $description,
                    'severity' => $severity,
                    'last_occurrence' => $value['timestamp'],
                    'minutes_ago' => 0,
                    'value' => $value['measured_value'],
                    'unit' => $value['unit'],
                    'occurrence_count' => 1
                ];
            }
        }
        
        debug_log("Generated " . count($alarms) . " pool value alarms");
        
        echo json_encode([
            'status' => 'success',
            'data' => $alarms,
            'source' => 'real_database',
            'total_count' => count($alarms),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getActiveAlarms error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Keine Alarmdaten verfügbar - Demo-Modus',
            'data' => getDemoData('active_alarms'),
            'total_count' => 2,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Empfehlungen
function getRecommendations($pdo) {
    debug_log("Loading intelligent recommendations...");
    
    try {
        $recommendations = [];
        
        // Verwende echte Messdaten für intelligente Empfehlungen
        if (!tableExists($pdo, 'v_latest_depolox_measurements')) {
            debug_log("❌ View not available for recommendations");
            throw new Exception("View not available");
        }
        
        debug_log("✅ Analyzing current measurements for recommendations");
        
        // Hole aktuelle Messwerte aller Systeme
        $sql = "
            SELECT 
                system_name,
                type_name,
                display_name,
                measured_value,
                setpoint_value,
                unit,
                timestamp
            FROM v_latest_depolox_measurements
            ORDER BY system_name, type_name
        ";
        
        $stmt = $pdo->query($sql);
        $measurements = $stmt->fetchAll();
        
        if (empty($measurements)) {
            throw new Exception("No measurements available for recommendations");
        }
        
        // Gruppiere Messungen nach System
        $systemData = [];
        foreach ($measurements as $measurement) {
            $system = $measurement['system_name'];
            if (!isset($systemData[$system])) {
                $systemData[$system] = [
                    'system' => $system,
                    'actions' => [],
                    'priority' => 'excellent',
                    'overall_status' => 'excellent'
                ];
            }
            $systemData[$system]['measurements'][$measurement['type_name']] = $measurement;
        }
        
        // Analysiere jeden Pool und generiere Empfehlungen
        foreach ($systemData as $system => &$data) {
            $actions = [];
            $highestPriority = 'excellent';
            $measurements = $data['measurements'];
            
            // Chlor-Analyse
            if (isset($measurements['chlorine'])) {
                $chlor = $measurements['chlorine'];
                $value = (float)$chlor['measured_value'];
                $setpoint = (float)$chlor['setpoint_value'];
                
                if ($value < 0.3) {
                    $actions[] = [
                        'type' => 'critical',
                        'title' => 'Chlor-Konzentration erhöhen',
                        'description' => "Chlor-Wert von {$value} mg/l ist zu niedrig (Sollwert: {$setpoint} mg/l).",
                        'steps' => [
                            'Chlor-Dosierung prüfen und erhöhen',
                            'Manuelle Chlorzugabe wenn nötig',
                            'Wasserzirkulation prüfen',
                            'Nach 2 Stunden erneut messen'
                        ]
                    ];
                    $highestPriority = 'critical';
                } elseif ($value > 1.5) {
                    $actions[] = [
                        'type' => 'warning',
                        'title' => 'Chlor-Konzentration zu hoch',
                        'description' => "Chlor-Wert von {$value} mg/l ist zu hoch (Sollwert: {$setpoint} mg/l).",
                        'steps' => [
                            'Chlor-Dosierung reduzieren',
                            'Wasserzirkulation verbessern',
                            'Warten bis Wert sinkt',
                            'Regelmäßig kontrollieren'
                        ]
                    ];
                    if ($highestPriority !== 'critical') $highestPriority = 'warning';
                }
            }
            
            // pH-Analyse
            if (isset($measurements['ph'])) {
                $ph = $measurements['ph'];
                $value = (float)$ph['measured_value'];
                $setpoint = (float)$ph['setpoint_value'];
                
                if ($value < 6.8 || $value > 7.8) {
                    $type = ($value < 6.5 || $value > 8.0) ? 'critical' : 'warning';
                    $direction = $value < 7 ? 'niedrig' : 'hoch';
                    $chemical = $value < 7 ? 'pH-Plus (Soda)' : 'pH-Minus (Salzsäure)';
                    
                    $actions[] = [
                        'type' => $type,
                        'title' => "pH-Wert zu {$direction}",
                        'description' => "pH-Wert von {$value} ist zu {$direction} (Sollwert: {$setpoint}).",
                        'steps' => [
                            "{$chemical} vorsichtig zugeben",
                            "Bei laufender Umwälzung zugeben",
                            "Nach 2 Stunden erneut messen",
                            "Schrittweise korrigieren"
                        ]
                    ];
                    
                    if ($type === 'critical') {
                        $highestPriority = 'critical';
                    } elseif ($type === 'warning' && $highestPriority !== 'critical') {
                        $highestPriority = 'warning';
                    }
                }
            }
            
            // Redox-Analyse (ANGEPASST für User Story 1)
            if (isset($measurements['redox'])) {
                $redox = $measurements['redox'];
                $value = (float)$redox['measured_value'];
                
                if ($value < 770) { // NEUE SCHWELLE
                    $actions[] = [
                        'type' => 'critical', // CRITICAL statt warning
                        'title' => 'Redox-Potential kritisch niedrig',
                        'description' => "Redox-Wert von {$value} mV liegt unter dem Sollwert von 770 mV. Sofortige Maßnahmen erforderlich.",
                        'steps' => [
                            'Chlor-Konzentration erhöhen',
                            'pH-Wert auf 7.0-7.2 einstellen',
                            'Wasserzirkulation intensivieren',
                            'Nach 1 Stunde Redox-Wert erneut messen'
                        ]
                    ];
                    $highestPriority = 'critical'; // Immer critical bei Redox < 770
                } elseif ($value > 900) {
                    $actions[] = [
                        'type' => 'info',
                        'title' => 'Redox-Potential hoch',
                        'description' => "Redox-Wert von {$value} mV ist hoch aber unbedenklich.",
                        'steps' => [
                            'Verlauf beobachten',
                            'Chlor-Dosierung ggf. anpassen'
                        ]
                    ];
                    if ($highestPriority === 'excellent') $highestPriority = 'info';
                }
            }
            
            // Temperatur-Analyse
            if (isset($measurements['temperature'])) {
                $temp = $measurements['temperature'];
                $value = (float)$temp['measured_value'];
                
                if ($value < 18) {
                    $actions[] = [
                        'type' => 'info',
                        'title' => 'Wassertemperatur niedrig',
                        'description' => "Temperatur von {$value}°C ist für Schwimmer möglicherweise zu kalt.",
                        'steps' => [
                            'Heizung prüfen',
                            'Wetter beachten',
                            'Energieeffizienz beachten'
                        ]
                    ];
                    if ($highestPriority === 'excellent') $highestPriority = 'info';
                } elseif ($value > 32) {
                    $actions[] = [
                        'type' => 'warning',
                        'title' => 'Wassertemperatur zu hoch',
                        'description' => "Temperatur von {$value}°C ist zu hoch und kann Bakterienwachstum fördern.",
                        'steps' => [
                            'Kühlung prüfen',
                            'Chlor-Konzentration erhöhen',
                            'Umwälzung verstärken'
                        ]
                    ];
                    if ($highestPriority !== 'critical') $highestPriority = 'warning';
                }
            }
            
            // Falls keine Probleme gefunden: Positive Empfehlung
            if (empty($actions)) {
                $actions[] = [
                    'type' => 'success',
                    'title' => 'Wasserqualität optimal',
                    'description' => 'Alle Parameter sind im optimalen Bereich. Weiter so!',
                    'steps' => [
                        'Regelmäßige Kontrolle beibehalten',
                        'Dosierung nicht verändern',
                        'Verlauf beobachten'
                    ]
                ];
            }
            
            $data['actions'] = $actions;
            $data['priority'] = $highestPriority;
            $data['overall_status'] = $highestPriority;
        }
        
        debug_log("Generated recommendations for " . count($systemData) . " systems");
        
        echo json_encode([
            'status' => 'success',
            'data' => array_values($systemData),
            'source' => 'real_database_analysis',
            'systems_analyzed' => count($systemData),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getRecommendations error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Empfehlungs-Analyse nicht verfügbar - Demo-Modus',
            'data' => getDemoData('recommendations'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Trend-Daten
function getTrends($pdo) {
    debug_log("Loading trends...");
    
    try {
        $hours = min(168, max(1, (int)($_GET['hours'] ?? 24)));
        debug_log("Loading trends for $hours hours");
        
        // Prüfe verfügbare Tabellen mit verbesserter Funktion
        $hasMeasurements = tableExists($pdo, 'depolox_measurements');
        $hasSystems = tableExists($pdo, 'depolox_systems');
        $hasTypes = tableExists($pdo, 'depolox_measurement_types');
        
        debug_log("Tables available - measurements: " . ($hasMeasurements ? 'YES' : 'NO') . 
                 ", systems: " . ($hasSystems ? 'YES' : 'NO') . 
                 ", types: " . ($hasTypes ? 'YES' : 'NO'));
        
        if (!$hasMeasurements || !$hasSystems || !$hasTypes) {
            throw new Exception("Required tables not found");
        }
        
        // Prüfe ob Daten vorhanden sind
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM depolox_measurements WHERE timestamp >= DATE_SUB(NOW(), INTERVAL $hours HOUR)");
        $countResult = $countStmt->fetch();
        $dataCount = $countResult['count'];
        
        debug_log("Found $dataCount measurements in last $hours hours");
        
        if ($dataCount == 0) {
            throw new Exception("No measurements found in timeframe");
        }
        
        $sql = "
            SELECT 
                s.system_name,
                mt.type_name,
                COALESCE(mt.display_name, mt.type_name) as display_name,
                COALESCE(mt.unit, '') as unit,
                ROUND(m.measured_value, 2) as value,
                ROUND(m.setpoint_value, 2) as setpoint,
                DATE_FORMAT(m.timestamp, '%H:%i') as time_bucket,
                m.timestamp
            FROM depolox_measurements m
            JOIN depolox_systems s ON m.system_id = s.system_id
            JOIN depolox_measurement_types mt ON m.measurement_type_id = mt.measurement_type_id
            WHERE m.timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND mt.type_name IN ('chlorine', 'ph', 'redox', 'temperature')
                AND s.is_active = 1
            ORDER BY s.system_name, mt.type_name, m.timestamp
            LIMIT 10000
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hours]);
        $trends = $stmt->fetchAll();
        
        debug_log("Found " . count($trends) . " trend data points from database");
        
        if (empty($trends)) {
            throw new Exception("No trend data found in query result");
        }
        
        // Gruppiere nach System und Parameter
        $trendData = [];
        foreach ($trends as $trend) {
            $system = $trend['system_name'];
            $parameter = $trend['type_name'];
            
            if (!isset($trendData[$system])) {
                $trendData[$system] = [];
            }
            if (!isset($trendData[$system][$parameter])) {
                $trendData[$system][$parameter] = [
                    'name' => $trend['display_name'],
                    'unit' => $trend['unit'],
                    'data' => []
                ];
            }
            
            $trendData[$system][$parameter]['data'][] = [
                'timestamp' => $trend['timestamp'],
                'time' => $trend['time_bucket'],
                'value' => (float)$trend['value'],
                'setpoint' => $trend['setpoint'] ? (float)$trend['setpoint'] : null
            ];
        }
        
        debug_log("Processed trends for " . count($trendData) . " systems");
        
        echo json_encode([
            'status' => 'success',
            'data' => $trendData,
            'source' => 'real_database',
            'hours' => $hours,
            'data_points' => count($trends),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getTrends error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Keine Trend-Daten verfügbar - Demo-Modus: ' . $e->getMessage(),
            'data' => getDemoData('trends'),
            'hours' => $hours ?? 24,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// System-Vergleich
function getSystemComparison($pdo) {
    debug_log("Loading system comparison...");
    
    try {
        // Für jetzt immer Demo-Daten
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Systemvergleich basierend auf Demo-Daten',
            'data' => getDemoData('system_comparison'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getSystemComparison error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success',
            'demo_mode' => true,
            'message' => 'Keine Vergleichsdaten verfügbar - Demo-Modus',
            'data' => getDemoData('system_comparison'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Datenbankdiagnose
function getDatabaseInfo($pdo) {
    debug_log("Loading database info...");
    
    try {
        $requiredTables = [
            'depolox_systems' => 'Basis-Systemtabelle',
            'depolox_measurements' => 'Messwerte-Tabelle', 
            'depolox_measurement_types' => 'Parametertypen',
            'depolox_error_logs' => 'Fehlerprotokoll',
            'v_latest_depolox_measurements' => 'Aktuelle Messwerte (View)',
            'v_depolox_system_health' => 'System-Gesundheit (View)'
        ];
        
        $tableStatus = [];
        $recordCounts = [];
        
        foreach ($requiredTables as $table => $description) {
            $exists = tableExists($pdo, $table);
            $tableStatus[$table] = [
                'exists' => $exists,
                'description' => $description,
                'records' => 0
            ];
            
            if ($exists) {
                try {
                    if (strpos($table, 'v_') === 0) {
                        // Views
                        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table LIMIT 1000");
                    } else {
                        // Tabellen
                        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    }
                    $count = $countStmt->fetch();
                    $tableStatus[$table]['records'] = $count['count'];
                } catch (Exception $e) {
                    $tableStatus[$table]['records'] = 'Fehler: ' . $e->getMessage();
                }
            }
        }
        
        // Systeminfo
        $systemInfo = [];
        if (tableExists($pdo, 'depolox_systems')) {
            try {
                $stmt = $pdo->query("SELECT system_name, location_description, is_active FROM depolox_systems LIMIT 20");
                $systemInfo = $stmt->fetchAll();
            } catch (Exception $e) {
                debug_log("Error loading system info: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'tables' => $tableStatus,
                'systems' => $systemInfo,
                'recommendation' => getDBRecommendation($tableStatus)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getDatabaseInfo error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Datenbankdiagnose fehlgeschlagen: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Debug-Daten für Fehlerdiagnose
function getDebugData($pdo) {
    debug_log("Loading debug data...");
    
    try {
        $debugInfo = [];
        
        // Tabellen-Check
        $tables = ['depolox_systems', 'depolox_measurements', 'depolox_measurement_types', 'depolox_error_logs'];
        foreach ($tables as $table) {
            if (tableExists($pdo, $table)) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch();
                $debugInfo['tables'][$table] = $count['count'];
                
                // Sample data
                if ($count['count'] > 0) {
                    $sampleStmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                    $debugInfo['samples'][$table] = $sampleStmt->fetchAll();
                }
            } else {
                $debugInfo['tables'][$table] = 'NOT_EXISTS';
            }
        }
        
        // Aktuelle Messungen
        if (tableExists($pdo, 'depolox_measurements')) {
            $stmt = $pdo->query("
                SELECT 
                    s.system_name,
                    mt.type_name,
                    m.measured_value,
                    m.setpoint_value,
                    m.timestamp
                FROM depolox_measurements m
                JOIN depolox_systems s ON m.system_id = s.system_id  
                JOIN depolox_measurement_types mt ON m.measurement_type_id = mt.measurement_type_id
                WHERE m.timestamp >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                ORDER BY m.timestamp DESC
                LIMIT 20
            ");
            $debugInfo['recent_measurements'] = $stmt->fetchAll();
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $debugInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        debug_log("getDebugData error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Debug-Daten konnten nicht geladen werden: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function getDBRecommendation($tableStatus) {
    $existingTables = array_filter($tableStatus, function($status) {
        return $status['exists'];
    });
    
    $tableCount = count($existingTables);
    
    if ($tableCount == 0) {
        return "Keine Depolox-Tabellen gefunden. Datenbank-Schema muss erstellt werden.";
    } elseif ($tableCount < 3) {
        return "Grundlegende Tabellen fehlen. Vervollständigen Sie das Datenbankschema.";
    } elseif (!isset($existingTables['v_latest_depolox_measurements'])) {
        return "Views fehlen. Erstellen Sie die v_latest_depolox_measurements View für optimale Performance.";
    } else {
        return "Datenbankschema vollständig. System sollte echte Daten anzeigen.";
    }
}

function removeDemoNotices() {
    // Diese Funktion wird im JavaScript verwendet
    return [
        'hide_demo_notices' => true,
        'real_data_confidence' => 95
    ];
}
?>