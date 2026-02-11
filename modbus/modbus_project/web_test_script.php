<?php
/**
 * Web-Test Script für Modbus Konfiguration
 * Kann über Browser aufgerufen werden zur Diagnose
 * 
 * Aufruf: https://personal.freibad-dabringhausen.de/modbus/modbus_project/test_connection.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modbus System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #ddd; }
        .test-ok { border-left-color: #28a745; }
        .test-error { border-left-color: #dc3545; }
        .test-warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Modbus System Diagnose</h1>
        <p>Überprüfung der Modbus-System Konfiguration</p>
        
        <?php
        // Test 1: Pfad-Informationen
        echo '<div class="test-section test-ok">';
        echo '<h2>📁 Pfad-Informationen</h2>';
        echo '<strong>Aktueller Pfad:</strong> ' . __DIR__ . '<br>';
        echo '<strong>Script-URL:</strong> ' . $_SERVER['PHP_SELF'] . '<br>';
        echo '<strong>Document Root:</strong> ' . $_SERVER['DOCUMENT_ROOT'] . '<br>';
        echo '</div>';
        
        // Test 2: Python verfügbar?
        echo '<div class="test-section">';
        echo '<h2>🐍 Python Test</h2>';
        
        $python_paths = [
            '/usr/bin/python3',
            '/usr/local/bin/python3', 
            '/opt/plesk/python/3.8/bin/python3',
            'python3'
        ];
        
        $python_found = false;
        foreach ($python_paths as $python_path) {
            $output = [];
            $return_var = 0;
            exec("$python_path --version 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                echo '<span class="success">✅ ' . $python_path . ': ' . implode('', $output) . '</span><br>';
                $python_found = true;
                $working_python = $python_path;
                break;
            } else {
                echo '<span class="error">❌ ' . $python_path . ': nicht gefunden</span><br>';
            }
        }
        
        if (!$python_found) {
            echo '<div class="test-section test-error">';
            echo '<span class="error">⚠️ Kein Python 3 gefunden!</span>';
            echo '</div>';
        }
        echo '</div>';
        
        // Test 3: Dateien vorhanden?
        echo '<div class="test-section">';
        echo '<h2>📋 Dateien prüfen</h2>';
        
        $required_files = [
            'modbus_reader_local.py',
            'config.ini',
            'modules/',
            'logs/'
        ];
        
        $all_files_ok = true;
        foreach ($required_files as $file) {
            $full_path = __DIR__ . '/' . $file;
            if (file_exists($full_path)) {
                echo '<span class="success">✅ ' . $file . '</span><br>';
                
                if ($file === 'modbus_reader_local.py') {
                    // Prüfe ob ausführbar
                    if (is_executable($full_path)) {
                        echo '  <span class="info">📝 Datei ist ausführbar</span><br>';
                    } else {
                        echo '  <span class="warning">⚠️ Datei nicht ausführbar (chmod +x erforderlich)</span><br>';
                    }
                }
                
                if ($file === 'modules/') {
                    // Prüfe Module
                    $modules = ['pymodbus', 'pymysql'];
                    foreach ($modules as $module) {
                        $module_path = $full_path . $module;
                        if (file_exists($module_path)) {
                            echo '  <span class="success">📦 ' . $module . '</span><br>';
                        } else {
                            echo '  <span class="error">❌ ' . $module . ' fehlt</span><br>';
                            $all_files_ok = false;
                        }
                    }
                }
            } else {
                echo '<span class="error">❌ ' . $file . ' nicht gefunden</span><br>';
                $all_files_ok = false;
            }
        }
        echo '</div>';
        
        // Test 4: Config.ini lesen
        echo '<div class="test-section">';
        echo '<h2>⚙️ Konfiguration</h2>';
        
        $config_file = __DIR__ . '/config.ini';
        if (file_exists($config_file)) {
            $config = parse_ini_file($config_file, true);
            if ($config) {
                echo '<span class="success">✅ config.ini erfolgreich gelesen</span><br>';
                
                // Wichtige Einstellungen anzeigen (ohne Passwort)
                if (isset($config['MODBUS'])) {
                    echo '<strong>Modbus Server:</strong> ' . ($config['MODBUS']['server_ip'] ?? 'nicht gesetzt') . ':' . ($config['MODBUS']['server_port'] ?? 'nicht gesetzt') . '<br>';
                }
                if (isset($config['DATABASE'])) {
                    echo '<strong>Datenbank:</strong> ' . ($config['DATABASE']['host'] ?? 'nicht gesetzt') . '/' . ($config['DATABASE']['database'] ?? 'nicht gesetzt') . '<br>';
                    echo '<strong>DB User:</strong> ' . ($config['DATABASE']['user'] ?? 'nicht gesetzt') . '<br>';
                    echo '<strong>Passwort:</strong> ' . (empty($config['DATABASE']['password']) ? '<span class="error">❌ nicht gesetzt</span>' : '<span class="success">✅ gesetzt</span>') . '<br>';
                }
            } else {
                echo '<span class="error">❌ Fehler beim Lesen der config.ini</span><br>';
            }
        } else {
            echo '<span class="error">❌ config.ini nicht gefunden</span><br>';
        }
        echo '</div>';
        
        // Test 5: Python Module Test (falls Python gefunden)
        if ($python_found && $all_files_ok) {
            echo '<div class="test-section">';
            echo '<h2>🧪 Python Module Test</h2>';
            
            $test_script = __DIR__ . '/test_imports.py';
            $test_code = '#!/usr/bin/env python3
import sys
sys.path.insert(0, "' . __DIR__ . '/modules")

modules_to_test = ["pymysql", "pymodbus", "configparser"]
success_count = 0

for module in modules_to_test:
    try:
        if module == "pymodbus":
            from pymodbus.client import ModbusTcpClient
            print(f"✅ {module}")
        else:
            __import__(module)
            print(f"✅ {module}")
        success_count += 1
    except ImportError as e:
        print(f"❌ {module}: {e}")
    except Exception as e:
        print(f"⚠️ {module}: {e}")

print(f"Module Test: {success_count}/{len(modules_to_test)} erfolgreich")
';
            
            file_put_contents($test_script, $test_code);
            chmod($test_script, 0755);
            
            $output = [];
            $return_var = 0;
            exec("$working_python $test_script 2>&1", $output, $return_var);
            
            echo '<pre>' . implode("\n", $output) . '</pre>';
            
            unlink($test_script);
            echo '</div>';
        }
        
        // Test 6: CronJob Kommando generieren
        echo '<div class="test-section test-ok">';
        echo '<h2>⏰ CronJob Konfiguration</h2>';
        echo '<p>Verwenden Sie dieses Kommando in Plesk:</p>';
        echo '<pre>' . ($working_python ?? '/usr/bin/python3') . ' ' . __DIR__ . '/modbus_reader_local.py ' . __DIR__ . '/config.ini</pre>';
        echo '<p><strong>Zeitplan:</strong> <code>*/5 * * * *</code> (alle 5 Minuten)</p>';
        echo '<p><strong>Mit Logging:</strong></p>';
        echo '<pre>' . ($working_python ?? '/usr/bin/python3') . ' ' . __DIR__ . '/modbus_reader_local.py ' . __DIR__ . '/config.ini >> ' . __DIR__ . '/logs/cron.log 2>&1</pre>';
        echo '</div>';
        
        // Test 7: Logs prüfen
        echo '<div class="test-section">';
        echo '<h2>📊 Log Dateien</h2>';
        
        $log_files = [
            'logs/modbus_reader.log',
            'logs/cron.log'
        ];
        
        foreach ($log_files as $log_file) {
            $full_log_path = __DIR__ . '/' . $log_file;
            if (file_exists($full_log_path)) {
                $size = filesize($full_log_path);
                $modified = date('Y-m-d H:i:s', filemtime($full_log_path));
                echo '<span class="success">✅ ' . $log_file . '</span> (' . $size . ' bytes, zuletzt: ' . $modified . ')<br>';
                
                // Zeige letzte Zeilen
                if ($size > 0) {
                    $lines = file($full_log_path);
                    $last_lines = array_slice($lines, -3);
                    echo '<div style="margin-left: 20px; font-size: 0.9em; color: #666;">';
                    echo 'Letzte Einträge:<br>';
                    foreach ($last_lines as $line) {
                        echo htmlspecialchars(trim($line)) . '<br>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<span class="warning">⚠️ ' . $log_file . ' noch nicht vorhanden (normal bei erster Installation)</span><br>';
            }
        }
        echo '</div>';
        
        // Zusammenfassung
        echo '<div class="test-section">';
        echo '<h2>📝 Zusammenfassung</h2>';
        
        if ($python_found && $all_files_ok) {
            echo '<span class="success">✅ System ist bereit für CronJob!</span><br>';
            echo '<p>Nächste Schritte:</p>';
            echo '<ol>';
            echo '<li>CronJob in Plesk mit obigem Kommando erstellen</li>';
            echo '<li>Zeitplan auf <code>*/5 * * * *</code> setzen</li>';
            echo '<li>Nach 5-10 Minuten Log-Dateien prüfen</li>';
            echo '<li>Datenbank auf neue Einträge prüfen</li>';
            echo '</ol>';
        } else {
            echo '<span class="error">❌ System noch nicht bereit</span><br>';
            echo '<p>Folgende Probleme beheben:</p>';
            echo '<ul>';
            if (!$python_found) echo '<li>Python 3 installieren/konfigurieren</li>';
            if (!$all_files_ok) echo '<li>Fehlende Dateien hochladen</li>';
            echo '</ul>';
        }
        echo '</div>';
        ?>
        
        <div class="test-section test-warning">
            <h2>⚠️ Wichtiger Hinweis</h2>
            <p><strong>Das Python-Script läuft NICHT über HTTP!</strong></p>
            <p>Es ist für <strong>CronJob-Ausführung</strong> konzipiert. Bitte verwenden Sie die Plesk CronJob-Konfiguration oben.</p>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 0.9em;">
            <p>Test ausgeführt am: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">🔄 Test wiederholen</a></p>
        </div>
    </div>
</body>
</html>