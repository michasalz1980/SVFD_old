<?php
/**
 * Script zum Erstellen der Log-Verzeichnisse und -Dateien
 * Aufruf über Browser: https://personal.freibad-dabringhausen.de/modbus/modbus_project/create_logs.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Logs Setup</h1>
        
        <?php
        $base_dir = __DIR__;
        $logs_dir = $base_dir . '/logs';
        
        echo "<p><strong>Basis-Verzeichnis:</strong> $base_dir</p>";
        echo "<p><strong>Logs-Verzeichnis:</strong> $logs_dir</p>";
        
        // Logs-Verzeichnis erstellen
        if (!is_dir($logs_dir)) {
            if (mkdir($logs_dir, 0755, true)) {
                echo '<p class="success">✅ Logs-Verzeichnis erstellt</p>';
            } else {
                echo '<p class="error">❌ Konnte Logs-Verzeichnis nicht erstellen</p>';
            }
        } else {
            echo '<p class="info">📁 Logs-Verzeichnis existiert bereits</p>';
        }
        
        // Berechtigung prüfen/setzen
        if (is_dir($logs_dir)) {
            $perms = fileperms($logs_dir);
            echo '<p class="info">📋 Aktuelle Berechtigung: ' . substr(sprintf('%o', $perms), -4) . '</p>';
            
            if (chmod($logs_dir, 0755)) {
                echo '<p class="success">✅ Berechtigung auf 755 gesetzt</p>';
            } else {
                echo '<p class="error">❌ Konnte Berechtigung nicht setzen</p>';
            }
        }
        
        // Log-Dateien erstellen
        $log_files = [
            'cron.log',
            'modbus_reader.log'
        ];
        
        foreach ($log_files as $log_file) {
            $full_path = $logs_dir . '/' . $log_file;
            
            if (!file_exists($full_path)) {
                if (touch($full_path)) {
                    chmod($full_path, 0644);
                    echo '<p class="success">✅ ' . $log_file . ' erstellt</p>';
                } else {
                    echo '<p class="error">❌ Konnte ' . $log_file . ' nicht erstellen</p>';
                }
            } else {
                echo '<p class="info">📄 ' . $log_file . ' existiert bereits</p>';
            }
        }
        
        // .htaccess für Logs erstellen
        $htaccess_path = $logs_dir . '/.htaccess';
        $htaccess_content = '<Files "*">
    Order allow,deny
    Deny from all
</Files>';
        
        if (file_put_contents($htaccess_path, $htaccess_content)) {
            echo '<p class="success">✅ .htaccess für Logs erstellt</p>';
        } else {
            echo '<p class="error">❌ Konnte .htaccess nicht erstellen</p>';
        }
        
        // Übersicht anzeigen
        echo '<h2>📊 Logs-Verzeichnis Inhalt:</h2>';
        if (is_dir($logs_dir)) {
            $files = scandir($logs_dir);
            echo '<ul>';
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $full_path = $logs_dir . '/' . $file;
                    $size = filesize($full_path);
                    $perms = substr(sprintf('%o', fileperms($full_path)), -3);
                    echo "<li>$file ($size bytes, $perms)</li>";
                }
            }
            echo '</ul>';
        }
        
        echo '<h2>🚀 Nächste Schritte:</h2>';
        echo '<ol>';
        echo '<li>CronJob in Plesk <strong>OHNE</strong> Logging erstellen:</li>';
        echo '<pre>/usr/bin/python3 ' . $base_dir . '/modbus_reader_local.py ' . $base_dir . '/config.ini</pre>';
        echo '<li>Erst testen, ob Script ohne Umleitung läuft</li>';
        echo '<li>Später Logging hinzufügen:</li>';
        echo '<pre>/usr/bin/python3 ' . $base_dir . '/modbus_reader_local.py ' . $base_dir . '/config.ini >> ' . $logs_dir . '/cron.log 2>&1</pre>';
        echo '</ol>';
        ?>
        
        <p style="margin-top: 30px; text-align: center;">
            <a href="test_connection.php">🔄 Zurück zum System-Test</a>
        </p>
    </div>
</body>
</html>