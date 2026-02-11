<?php
// dashboard.php - Mit Sensor-Status-Monitoring
date_default_timezone_set('Europe/Berlin');
$dbfile = 'logs/lorawan_data.db';

try {
    $db = new PDO("sqlite:$dbfile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Letzte Messung für Status-Check
    $last_measurement = $db->query("
        SELECT 
            timestamp,
            device_name,
            distance_m,
            is_valid,
            strftime('%s', 'now', 'localtime') - strftime('%s', timestamp) as age_seconds
        FROM measurements 
        ORDER BY id DESC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Status berechnen
    $status = 'unknown';
    $status_text = 'Unbekannt';
    $status_color = '#6c757d';
    $status_icon = '❓';
    $age_minutes = 0;
    
    if ($last_measurement) {
        $age_minutes = round($last_measurement['age_seconds'] / 60);
        
        if ($age_minutes <= 10) {
            // Online - letzte Messung innerhalb 10 Minuten
            $status = 'online';
            $status_text = '🟢 Online';
            $status_color = '#28a745';
            $status_icon = '✓';
        } elseif ($age_minutes <= 15) {
            // Warnung - letzte Messung 10-15 Minuten alt
            $status = 'warning';
            $status_text = '🟡 Warnung';
            $status_color = '#ffc107';
            $status_icon = '⚠';
        } else {
            // Offline - letzte Messung älter als 15 Minuten
            $status = 'offline';
            $status_text = '🔴 Offline';
            $status_color = '#dc3545';
            $status_icon = '✗';
        }
    }
    
    // Letzte 50 Messungen
    $stmt = $db->query("
        SELECT 
            timestamp,
            device_name,
            distance_m,
            is_valid
        FROM measurements 
        ORDER BY id DESC 
        LIMIT 50
    ");
    $measurements_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Timestamps von UTC (Datenbank) nach Europe/Berlin (Anzeige) konvertieren
    $measurements = [];
    foreach ($measurements_raw as $m) {
        // UTC Timestamp parsen
        $utc_time = new DateTime($m['timestamp'], new DateTimeZone('UTC'));
        // Nach Europe/Berlin (MEZ/MESZ) konvertieren
        $utc_time->setTimezone(new DateTimeZone('Europe/Berlin'));
        // Formatieren
        $m['timestamp'] = $utc_time->format('Y-m-d H:i:s');
        $measurements[] = $m;
    }
    
    // Statistiken (nur gültige Messungen mit sinnvollen Werten)
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            AVG(distance_m) as avg_distance,
            MIN(distance_m) as min_distance,
            MAX(distance_m) as max_distance,
            SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_count
        FROM measurements
        WHERE distance_m > 0 AND distance_m < 65
    ")->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LoRaWAN Wasserstand Monitor</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card.status {
            border-left: 5px solid <?= $status_color ?>;
        }
        
        .stat-card.status.offline {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }
        
        .stat-value.status-value {
            color: <?= $status_color ?>;
            -webkit-text-fill-color: <?= $status_color ?>;
            font-size: 1.8em;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
            font-size: 0.85em;
            color: #666;
        }
        
        .status-details strong {
            color: <?= $status_color ?>;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        h2 {
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0;
            font-size: 1.5em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px 25px;
            text-align: left;
        }
        
        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }
        
        td {
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .valid {
            color: #28a745;
            font-weight: bold;
        }
        
        .invalid {
            color: #dc3545;
            font-weight: bold;
        }
        
        .timestamp {
            font-family: 'Courier New', monospace;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .alert {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .alert-icon {
            font-size: 1.5em;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            th, td {
                padding: 10px 15px;
                font-size: 0.9em;
            }
            
            .stat-value {
                font-size: 2em;
            }
        }
        
        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span>🌊</span>
            LoRaWAN Wasserstand Monitor
        </h1>
        
        <?php if ($status === 'warning' || $status === 'offline'): ?>
        <div class="alert <?= $status === 'offline' ? 'error' : '' ?> show">
            <span class="alert-icon"><?= $status === 'offline' ? '🔴' : '🟡' ?></span>
            <strong><?= $status === 'offline' ? 'SENSOR OFFLINE!' : 'WARNUNG!' ?></strong>
            Letzte Daten vor <strong><?= $age_minutes ?> Minuten</strong> empfangen.
            <?php if ($status === 'offline'): ?>
            Bitte Sensor und Gateway prüfen!
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card status <?= $status ?>">
                <div class="stat-value status-value"><?= $status_text ?></div>
                <div class="stat-label">Sensor Status</div>
                <div class="status-details">
                    <?php if ($last_measurement): ?>
                    Letzte Daten: <strong>vor <?= $age_minutes ?> Min</strong><br>
                    Gerät: <?= htmlspecialchars($last_measurement['device_name']) ?><br>
                    Erwartung: alle 5-10 Min
                    <?php else: ?>
                    Noch keine Daten empfangen
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['avg_distance'], 2) ?>m</div>
                <div class="stat-label">Durchschnittliche Distanz</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['min_distance'], 2) ?>m</div>
                <div class="stat-label">Minimum</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['max_distance'], 2) ?>m</div>
                <div class="stat-label">Maximum</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['valid_count'] ?> / <?= $stats['total'] ?></div>
                <div class="stat-label">Gültige Messungen</div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Letzte Messungen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Zeit</th>
                        <th>Gerät</th>
                        <th>Distanz</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($measurements as $m): ?>
                    <tr>
                        <td class="timestamp"><?= htmlspecialchars($m['timestamp']) ?></td>
                        <td><?= htmlspecialchars($m['device_name']) ?></td>
                        <td><strong><?= number_format($m['distance_m'], 3) ?> m</strong></td>
                        <td class="<?= $m['is_valid'] ? 'valid' : 'invalid' ?>">
                            <?= $m['is_valid'] ? '✓ Gültig' : '✗ Fehler' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Auto-Refresh alle 60 Sekunden
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
