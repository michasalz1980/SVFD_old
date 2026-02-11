<?php
/**
 * Debug Script für CronJob-Umgebung
 * Erstellt Test-Scripts für CronJob-Debugging
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>CronJob Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .command { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 10px 0; font-family: monospace; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 CronJob Debug</h1>
        
        <?php
        $base_dir = __DIR__;
        
        // 1. Test-Script für CronJob-Umgebung erstellen
        $debug_script = $base_dir . '/cronjob_debug_test.sh';
        $debug_content = '#!/bin/bash
# CronJob Debug Test Script
echo "=== CronJob Environment Debug ===" >> ' . $base_dir . '/debug_output.log
echo "Date: $(date)" >> ' . $base_dir . '/debug_output.log
echo "User: $(whoami)" >> ' . $base_dir . '/debug_output.log
echo "PATH: $PATH" >> ' . $base_dir . '/debug_output.log
echo "PWD: $(pwd)" >> ' . $base_dir . '/debug_output.log
echo "" >> ' . $base_dir . '/debug_output.log

echo "Testing Python paths:" >> ' . $base_dir . '/debug_output.log
echo "which python3: $(which python3 2>&1)" >> ' . $base_dir . '/debug_output.log
echo "which python: $(which python 2>&1)" >> ' . $base_dir . '/debug_output.log

echo "" >> ' . $base_dir . '/debug_output.log
echo "Testing specific paths:" >> ' . $base_dir . '/debug_output.log

for python_path in "/usr/bin/python3" "/usr/bin/python3.10" "/opt/plesk/python/3/bin/python3.10" "/opt/alt/python311/bin/python3.11"; do
    if [ -f "$python_path" ]; then
        echo "$python_path: EXISTS - $($python_path --version 2>&1)" >> ' . $base_dir . '/debug_output.log
    else
        echo "$python_path: NOT FOUND" >> ' . $base_dir . '/debug_output.log
    fi
done

echo "" >> ' . $base_dir . '/debug_output.log
echo "File permissions:" >> ' . $base_dir . '/debug_output.log
echo "Script: $(ls -la ' . $base_dir . '/modbus_reader_local.py)" >> ' . $base_dir . '/debug_output.log
echo "Config: $(ls -la ' . $base_dir . '/config.ini)" >> ' . $base_dir . '/debug_output.log

echo "=== End Debug ===" >> ' . $base_dir . '/debug_output.log
echo "" >> ' . $base_dir . '/debug_output.log
';

        if (file_put_contents($debug_script, $debug_content)) {
            chmod($debug_script, 0755);
            echo '<p class="success">✅ Debug-Script erstellt: cronjob_debug_test.sh</p>';
        } else {
            echo '<p class="error">❌ Konnte Debug-Script nicht erstellen</p>';
        }

        // 2. Einfaches Python-Test-Script
        $python_test = $base_dir . '/simple_python_test.py';
        $python_content = '#!/usr/bin/env python3
import sys
import os
from datetime import datetime

# Log in Datei schreiben
with open("' . $base_dir . '/python_test.log", "a") as f:
    f.write(f"=== Python Test - {datetime.now()} ===\\n")
    f.write(f"Python Version: {sys.version}\\n")
    f.write(f"Python Executable: {sys.executable}\\n")
    f.write(f"Current Directory: {os.getcwd()}\\n")
    f.write(f"Script Path: {__file__}\\n")
    f.write("Python Test successful!\\n")
    f.write("\\n")

print("Python test completed - check python_test.log")
';

        if (file_put_contents($python_test, $python_content)) {
            chmod($python_test, 0755);
            echo '<p class="success">✅ Python-Test-Script erstellt: simple_python_test.py</p>';
        } else {
            echo '<p class="error">❌ Konnte Python-Test-Script nicht erstellen</p>';
        }

        // 3. Wrapper-Script erstellen
        $wrapper_script = $base_dir . '/run_modbus.sh';
        $wrapper_content = '#!/bin/bash
# Wrapper Script für Modbus Reader

# Debugging aktivieren
set -x

# Arbeitsverzeichnis setzen
cd "' . $base_dir . '"

# Log-Datei
LOGFILE="' . $base_dir . '/wrapper.log"

echo "=== Modbus Reader Start - $(date) ===" >> "$LOGFILE"
echo "Current directory: $(pwd)" >> "$LOGFILE"
echo "User: $(whoami)" >> "$LOGFILE"

# Python-Pfade probieren
PYTHON_PATHS=(
    "/opt/plesk/python/3/bin/python3.10"
    "/opt/alt/python311/bin/python3.11" 
    "/usr/bin/python3.10"
    "/usr/bin/python3"
    "python3"
)

for PYTHON_PATH in "${PYTHON_PATHS[@]}"; do
    echo "Trying: $PYTHON_PATH" >> "$LOGFILE"
    if command -v "$PYTHON_PATH" >/dev/null 2>&1; then
        echo "Found: $PYTHON_PATH" >> "$LOGFILE"
        echo "Version: $($PYTHON_PATH --version 2>&1)" >> "$LOGFILE"
        
        # Script ausführen
        "$PYTHON_PATH" "' . $base_dir . '/modbus_reader_local.py" "' . $base_dir . '/config.ini" >> "$LOGFILE" 2>&1
        EXIT_CODE=$?
        
        echo "Exit code: $EXIT_CODE" >> "$LOGFILE"
        echo "=== End - $(date) ===" >> "$LOGFILE"
        echo "" >> "$LOGFILE"
        
        exit $EXIT_CODE
    else
        echo "Not found: $PYTHON_PATH" >> "$LOGFILE"
    fi
done

echo "ERROR: No working Python found!" >> "$LOGFILE"
echo "=== End with Error - $(date) ===" >> "$LOGFILE"
echo "" >> "$LOGFILE"
exit 1
';

        if (file_put_contents($wrapper_script, $wrapper_content)) {
            chmod($wrapper_script, 0755);
            echo '<p class="success">✅ Wrapper-Script erstellt: run_modbus.sh</p>';
        } else {
            echo '<p class="error">❌ Konnte Wrapper-Script nicht erstellen</p>';
        }

        // Anweisungen anzeigen
        echo '<h2>🚀 Test-Strategien:</h2>';
        
        echo '<h3>Strategie 1: Debug-Script ausführen</h3>';
        echo '<div class="command">';
        echo '<strong>CronJob Befehl:</strong><br>';
        echo '/bin/bash ' . $base_dir . '/cronjob_debug_test.sh';
        echo '</div>';
        echo '<p>Das erstellt eine debug_output.log mit allen Umgebungsinformationen.</p>';
        
        echo '<h3>Strategie 2: Einfacher Python-Test</h3>';
        echo '<div class="command">';
        echo '<strong>CronJob Befehle zum Testen:</strong><br><br>';
        echo '/opt/plesk/python/3/bin/python3.10 ' . $base_dir . '/simple_python_test.py<br>';
        echo 'ODER<br>';
        echo '/opt/alt/python311/bin/python3.11 ' . $base_dir . '/simple_python_test.py<br>';
        echo 'ODER<br>';
        echo '/usr/bin/python3.10 ' . $base_dir . '/simple_python_test.py';
        echo '</div>';
        echo '<p>Das erstellt eine python_test.log wenn Python funktioniert.</p>';
        
        echo '<h3>Strategie 3: Wrapper-Script (Empfohlen)</h3>';
        echo '<div class="command">';
        echo '<strong>CronJob Befehl:</strong><br>';
        echo '/bin/bash ' . $base_dir . '/run_modbus.sh';
        echo '</div>';
        echo '<p>Das probiert automatisch alle Python-Pfade und erstellt detaillierte Logs.</p>';
        
        echo '<h2>📊 Nach dem Test:</h2>';
        echo '<p>Schauen Sie in diese Log-Dateien:</p>';
        echo '<ul>';
        echo '<li><strong>debug_output.log</strong> - CronJob Umgebung</li>';
        echo '<li><strong>python_test.log</strong> - Python Test Ergebnis</li>';
        echo '<li><strong>wrapper.log</strong> - Detaillierte Ausführung</li>';
        echo '</ul>';

        // Log-Viewer
        echo '<h2>📄 Aktuelle Logs anzeigen:</h2>';
        
        $log_files = ['debug_output.log', 'python_test.log', 'wrapper.log'];
        foreach ($log_files as $log_file) {
            $full_path = $base_dir . '/' . $log_file;
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
                echo "<h4>$log_file:</h4>";
                echo '<pre>' . htmlspecialchars($content) . '</pre>';
            } else {
                echo "<h4>$log_file:</h4>";
                echo '<p class="info">Noch nicht vorhanden</p>';
            }
        }
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;">
            <h3>📋 Empfohlenes Vorgehen:</h3>
            <ol>
                <li><strong>Wrapper-Script testen:</strong> CronJob mit <code>/bin/bash run_modbus.sh</code></li>
                <li><strong>1 Minute warten</strong> und dann diese Seite neu laden</li>
                <li><strong>wrapper.log prüfen</strong> - zeigt welcher Python-Pfad funktioniert</li>
                <li><strong>Erfolgreichen Pfad</strong> für finalen CronJob verwenden</li>
            </ol>
        </div>
    </div>
</body>
</html>