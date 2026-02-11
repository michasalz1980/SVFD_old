<?php
/**
 * Script zum Finden des korrekten Python-Pfads
 * Aufruf: https://personal.freibad-dabringhausen.de/modbus/modbus_project/find_python.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Python Pfad finden</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .command { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 10px 0; font-family: monospace; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐍 Python Pfad ermitteln</h1>
        
        <?php
        echo "<h2>🔍 Teste verschiedene Python-Pfade:</h2>";
        
        // Mögliche Python-Pfade
        $python_paths = [
            '/usr/bin/python3',
            '/usr/local/bin/python3',
            '/opt/plesk/python/3.8/bin/python3',
            '/opt/plesk/python/3.9/bin/python3', 
            '/opt/plesk/python/3.10/bin/python3',
            '/opt/plesk/python/3.11/bin/python3',
            '/opt/alt/python38/bin/python3.8',
            '/opt/alt/python39/bin/python3.9',
            '/opt/alt/python310/bin/python3.10',
            '/opt/alt/python311/bin/python3.11',
            '/usr/bin/python',
            'python3',
            'python'
        ];
        
        $working_python = null;
        $working_version = null;
        
        foreach ($python_paths as $python_path) {
            echo "<p>Teste: <code>$python_path</code> ... ";
            
            $output = [];
            $return_var = 0;
            exec("$python_path --version 2>&1", $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                $version = implode(' ', $output);
                echo '<span class="success">✅ GEFUNDEN: ' . $version . '</span>';
                
                if (!$working_python) {
                    $working_python = $python_path;
                    $working_version = $version;
                }
            } else {
                echo '<span class="error">❌ Nicht gefunden</span>';
            }
            echo "</p>";
        }
        
        if ($working_python) {
            echo "<h2>🎯 LÖSUNG GEFUNDEN:</h2>";
            echo '<div class="command">';
            echo '<strong>Verwenden Sie diesen Python-Pfad:</strong><br>';
            echo "<code>$working_python</code> ($working_version)";
            echo '</div>';
            
            echo "<h2>📋 Korrigierter CronJob Befehl:</h2>";
            $base_path = dirname(__DIR__ . '/dummy'); // Removes the dummy to get parent
            $full_path = __DIR__;
            
            echo '<div class="command">';
            echo '<strong>EINFACHER BEFEHL (ohne Logging):</strong><br>';
            echo "<code>$working_python $full_path/modbus_reader_local.py $full_path/config.ini</code>";
            echo '</div>';
            
            echo '<div class="command">';
            echo '<strong>MIT LOGGING:</strong><br>';
            echo "<code>$working_python $full_path/modbus_reader_local.py $full_path/config.ini >> $full_path/logs/cron.log 2>&1</code>";
            echo '</div>';
            
            // Test ob Script ausführbar ist
            echo "<h2>🧪 Script-Test:</h2>";
            $script_path = __DIR__ . '/modbus_reader_local.py';
            
            if (file_exists($script_path)) {
                if (is_executable($script_path)) {
                    echo '<p class="success">✅ Script ist ausführbar</p>';
                } else {
                    echo '<p class="error">❌ Script nicht ausführbar - chmod +x erforderlich</p>';
                    echo '<p class="info">💡 Führen Sie aus: <code>chmod +x ' . $script_path . '</code></p>';
                }
                
                // Kurzer Syntax-Test
                echo "<p>Teste Python-Syntax...</p>";
                $output = [];
                $return_var = 0;
                exec("$working_python -m py_compile $script_path 2>&1", $output, $return_var);
                
                if ($return_var === 0) {
                    echo '<p class="success">✅ Python-Syntax OK</p>';
                } else {
                    echo '<p class="error">❌ Python-Syntax Fehler:</p>';
                    echo '<pre>' . implode("\n", $output) . '</pre>';
                }
            } else {
                echo '<p class="error">❌ modbus_reader_local.py nicht gefunden</p>';
            }
            
            // Module Test
            echo "<h2>📦 Module Test mit korrektem Python:</h2>";
            $test_script = __DIR__ . '/quick_module_test.py';
            $test_code = '#!/usr/bin/env python3
import sys
sys.path.insert(0, "' . __DIR__ . '/modules")

try:
    import pymysql
    print("✅ pymysql OK")
except Exception as e:
    print(f"❌ pymysql: {e}")

try:
    from pymodbus.client import ModbusTcpClient
    print("✅ pymodbus OK")
except Exception as e:
    print(f"❌ pymodbus: {e}")

try:
    import configparser
    print("✅ configparser OK")
except Exception as e:
    print(f"❌ configparser: {e}")
';
            
            file_put_contents($test_script, $test_code);
            chmod($test_script, 0755);
            
            $output = [];
            $return_var = 0;
            exec("$working_python $test_script 2>&1", $output, $return_var);
            
            echo '<pre>' . implode("\n", $output) . '</pre>';
            unlink($test_script);
            
        } else {
            echo "<h2>❌ PROBLEM:</h2>";
            echo '<p class="error">Kein funktionierender Python-Interpreter gefunden!</p>';
            echo '<p>Kontaktieren Sie Ihren Hosting-Provider für Python 3 Installation.</p>';
        }
        
        // Zusätzliche Diagnose
        echo "<h2>🔧 System-Informationen:</h2>";
        
        // which python3
        echo "<p><strong>which python3:</strong></p>";
        $output = [];
        exec("which python3 2>&1", $output);
        echo '<pre>' . implode("\n", $output) . '</pre>';
        
        // whereis python3  
        echo "<p><strong>whereis python3:</strong></p>";
        $output = [];
        exec("whereis python3 2>&1", $output);
        echo '<pre>' . implode("\n", $output) . '</pre>';
        
        // find python
        echo "<p><strong>find python (erste 10 Treffer):</strong></p>";
        $output = [];
        exec("find /usr /opt -name 'python*' -type f -executable 2>/dev/null | head -10", $output);
        echo '<pre>' . implode("\n", $output) . '</pre>';
        
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;">
            <h3>📋 Nächste Schritte:</h3>
            <ol>
                <li>Kopieren Sie den <strong>korrigierten CronJob Befehl</strong> von oben</li>
                <li>Gehen Sie zu Plesk → Scheduled Tasks</li>
                <li>Bearbeiten Sie Ihren CronJob</li>
                <li>Ersetzen Sie den Befehl mit dem korrigierten</li>
                <li>Speichern und testen</li>
            </ol>
        </div>
        
        <p style="margin-top: 30px; text-align: center;">
            <a href="test_connection.php">🔄 Zurück zum System-Test</a>
        </p>
    </div>
</body>
</html>