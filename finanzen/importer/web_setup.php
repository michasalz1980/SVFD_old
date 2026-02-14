<?php
/**
 * Web-Setup-Script für SV Freibad Dabringhausen e.V. Import-System
 * 
 * Browser-kompatible Installation und Konfiguration
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 1.0
 * @date 2025-06-09
 */

// Fehlerbehandlung und Timeouts
ini_set('max_execution_time', 300); // 5 Minuten
ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output buffering für Live-Updates
if (ob_get_level() == 0) {
    ob_start();
}

// Sicherheitscheck - Setup nur aus localhost oder von Admin-IP erlauben
$allowed_ips = ['217.231.139.219', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips) && !isset($_GET['allow'])) {
    die('❌ Setup nur von localhost erlaubt. Fügen Sie ?allow=1 hinzu, um fortzufahren.');
}

class WebSystemSetup {
    
    private $errors = [];
    private $warnings = [];
    private $success = [];
    private $step = 1;
    
    public function __construct() {
        $this->step = intval($_GET['step'] ?? 1);
    }
    
    /**
     * Hauptsetup-Prozess für Web
     */
    public function runWebSetup() {
        // HTML Header
        $this->outputHeader();
        
        switch ($this->step) {
            case 1:
                $this->showWelcome();
                break;
            case 2:
                $this->checkRequirements();
                break;
            case 3:
                $this->handleDirectories();
                break;
            case 4:
                $this->handleDatabase();
                break;
            case 5:
                $this->handleConfiguration();
                break;
            case 6:
                $this->testSystem();
                break;
            case 7:
                $this->showCompletion();
                break;
            default:
                $this->showWelcome();
        }
        
        // HTML Footer
        $this->outputFooter();
    }
    
    /**
     * HTML Header ausgeben
     */
    private function outputHeader() {
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>SV Freibad Setup - Schritt <?php echo $this->step; ?></title>
            <style>
                * {
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    max-width: 900px;
                    margin: 0 auto;
                    padding: 20px;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    min-height: 100vh;
                }
                .container {
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #006699, #0099cc);
                    color: white;
                    padding: 30px;
                    border-radius: 15px;
                    margin-bottom: 30px;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
                }
                .header::before {
                    content: '';
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
                    animation: float 6s ease-in-out infinite;
                }
                @keyframes float {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-10px); }
                }
                .header h1, .header h2 {
                    position: relative;
                    z-index: 1;
                    margin: 10px 0;
                }
                .step-indicator {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                    padding: 0 20px;
                    flex-wrap: wrap;
                }
                .step {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    color: white;
                    position: relative;
                    margin: 5px;
                    transition: all 0.3s ease;
                }
                .step.active {
                    background: linear-gradient(135deg, #0099cc, #00ccff);
                    transform: scale(1.1);
                    box-shadow: 0 5px 15px rgba(0,153,204,0.4);
                }
                .step.completed {
                    background: linear-gradient(135deg, #28a745, #20c997);
                    transform: scale(1.05);
                }
                .step.pending {
                    background: #ccc;
                }
                .step::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    right: -25px;
                    width: 20px;
                    height: 2px;
                    background: #ddd;
                    transform: translateY(-50%);
                }
                .step:last-child::after {
                    display: none;
                }
                .step.completed::after {
                    background: #28a745;
                }
                .form-group {
                    margin-bottom: 25px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #333;
                }
                .form-group small {
                    display: block;
                    margin-top: 5px;
                    color: #666;
                    font-size: 0.85em;
                }
                .form-group input, .form-group select, .form-group textarea {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e1e5e9;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.3s ease;
                }
                .form-group input:focus, .form-group select:focus {
                    outline: none;
                    border-color: #0099cc;
                    box-shadow: 0 0 0 3px rgba(0,153,204,0.1);
                }
                .btn {
                    background: linear-gradient(135deg, #0099cc, #007aa3);
                    color: white;
                    padding: 14px 28px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 600;
                    text-decoration: none;
                    display: inline-block;
                    margin: 10px 8px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(0,153,204,0.2);
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(0,153,204,0.3);
                }
                .btn-success {
                    background: linear-gradient(135deg, #28a745, #20c997);
                }
                .btn-danger {
                    background: linear-gradient(135deg, #dc3545, #c82333);
                }
                .btn-secondary {
                    background: linear-gradient(135deg, #6c757d, #5a6268);
                }
                .alert {
                    padding: 16px 20px;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    border-left: 4px solid;
                    position: relative;
                }
                .alert-success {
                    background-color: #d4edda;
                    border-left-color: #28a745;
                    color: #155724;
                }
                .alert-warning {
                    background-color: #fff3cd;
                    border-left-color: #ffc107;
                    color: #856404;
                }
                .alert-danger {
                    background-color: #f8d7da;
                    border-left-color: #dc3545;
                    color: #721c24;
                }
                .alert-info {
                    background-color: #d1ecf1;
                    border-left-color: #17a2b8;
                    color: #0c5460;
                }
                .progress {
                    background-color: #e9ecef;
                    border-radius: 25px;
                    height: 25px;
                    margin-bottom: 30px;
                    overflow: hidden;
                }
                .progress-bar {
                    background: linear-gradient(90deg, #0099cc, #00ccff);
                    height: 100%;
                    border-radius: 25px;
                    transition: width 0.6s ease;
                    position: relative;
                }
                .progress-bar::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                    animation: shimmer 2s infinite;
                }
                @keyframes shimmer {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
                .code-block {
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 20px;
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                    margin: 20px 0;
                    overflow-x: auto;
                    font-size: 14px;
                    line-height: 1.5;
                }
                .feature-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin: 20px 0;
                }
                .feature-card {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    border-left: 4px solid #0099cc;
                }
                .feature-card h4 {
                    margin: 0 0 10px 0;
                    color: #0099cc;
                }
                .section-divider {
                    height: 2px;
                    background: linear-gradient(90deg, transparent, #0099cc, transparent);
                    margin: 30px 0;
                    border: none;
                }
                @media (max-width: 600px) {
                    body { padding: 10px; }
                    .container { padding: 20px; }
                    .step-indicator { justify-content: center; }
                    .step { margin: 3px; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🏊‍♂️ SV Freibad Dabringhausen e.V.</h1>
                    <h2>Import-System Setup</h2>
                    <p style="margin: 15px 0 0 0; opacity: 0.9;">Automatisierte Verkaufserlös-Verwaltung</p>
                </div>
                
                <div class="step-indicator">
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <div class="step <?php 
                            if ($i < $this->step) echo 'completed';
                            elseif ($i == $this->step) echo 'active';
                            else echo 'pending';
                        ?>" title="Schritt <?php echo $i; ?>"><?php echo $i; ?></div>
                    <?php endfor; ?>
                </div>
                
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo (($this->step - 1) / 6) * 100; ?>%"></div>
                </div>
        <?php
    }
    
    /**
     * HTML Footer ausgeben
     */
    private function outputFooter() {
        ?>
            </div>
            <script>
                // Smooth scrolling and form enhancements
                document.addEventListener('DOMContentLoaded', function() {
                    // Auto-focus first input
                    const firstInput = document.querySelector('input, select');
                    if (firstInput) {
                        firstInput.focus();
                    }
                    
                    // Form validation
                    const forms = document.querySelectorAll('form');
                    forms.forEach(form => {
                        form.addEventListener('submit', function(e) {
                            const requiredFields = form.querySelectorAll('[required]');
                            let hasErrors = false;
                            
                            requiredFields.forEach(field => {
                                if (!field.value.trim()) {
                                    field.style.borderColor = '#dc3545';
                                    hasErrors = true;
                                } else {
                                    field.style.borderColor = '#28a745';
                                }
                            });
                            
                            if (hasErrors) {
                                e.preventDefault();
                                alert('Bitte füllen Sie alle Pflichtfelder aus.');
                            }
                        });
                    });
                    
                    // Loading animation for form submissions
                    const buttons = document.querySelectorAll('button[type="submit"]');
                    buttons.forEach(button => {
                        button.addEventListener('click', function() {
                            const form = this.closest('form');
                            if (form.checkValidity()) {
                                this.innerHTML = '⏳ Verarbeitung...';
                                this.disabled = true;
                                
                                // Fallback: Re-enable button after 30 seconds
                                setTimeout(() => {
                                    this.disabled = false;
                                    this.innerHTML = this.innerHTML.replace('⏳ Verarbeitung...', '🔄 Erneut versuchen');
                                }, 30000);
                            }
                        });
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Willkommensseite
     */
    private function showWelcome() {
        ?>
        <h2>🎯 Willkommen zum Setup-Assistenten</h2>
        <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
            Dieses Setup-Tool installiert und konfiguriert das automatisierte Verkaufserlös-Import-System 
            für das SV Freibad Dabringhausen e.V. in wenigen einfachen Schritten.
        </p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h4>🚀 Was wird installiert</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Datenbankstruktur für Verkaufserlöse</li>
                    <li>CSV-Import-System mit Duplikat-Prüfung</li>
                    <li>Dry-Run-Modus für sichere Tests</li>
                    <li>E-Mail-Benachrichtigungen</li>
                    <li>Automatische Dateiarchivierung</li>
                    <li>Umfassendes Logging-System</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <h4>📋 Voraussetzungen</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>PHP 7.4 oder höher</li>
                    <li>MySQL/MariaDB Datenbank</li>
                    <li>Schreibrechte für Verzeichnisse</li>
                    <li>E-Mail-Server (SMTP)</li>
                </ul>
            </div>
        </div>
        
        <hr class="section-divider">
        
        <div class="alert alert-info">
            <strong>ℹ️ Setup-Prozess:</strong> Das Setup dauert etwa 5-10 Minuten und führt Sie durch 
            7 einfache Schritte. Alle Einstellungen können später in der config.php angepasst werden.
        </div>
        
        <div class="alert alert-warning">
            <strong>⚠️ Wichtiger Hinweis:</strong> Stellen Sie sicher, dass Sie Backup-Kopien Ihrer 
            Daten haben, bevor Sie fortfahren. Das Setup erstellt neue Datenbanktabellen.
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="?step=2" class="btn" style="font-size: 18px; padding: 16px 32px;">
                🚀 Setup starten
            </a>
        </div>
        <?php
    }
    
    /**
     * System-Anforderungen prüfen
     */
    private function checkRequirements() {
        ?>
        <h2>🔍 System-Anforderungen prüfen</h2>
        <p>Überprüfung der Systemvoraussetzungen für das Import-System...</p>
        
        <hr class="section-divider">
        <?php
        
        // PHP Version
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            echo '<div class="alert alert-success">✅ <strong>PHP Version:</strong> ' . PHP_VERSION . ' (Erforderlich: 7.4+)</div>';
        } else {
            echo '<div class="alert alert-danger">❌ <strong>PHP Version:</strong> ' . PHP_VERSION . ' (Mindestens 7.4 erforderlich)</div>';
            $this->errors[] = "PHP Version zu alt";
        }
        
        // Extensions
        $required_extensions = [
            'pdo' => 'PDO (Datenbankabstraktion)',
            'pdo_mysql' => 'PDO MySQL (MySQL-Verbindung)',
            'mbstring' => 'Multibyte String (UTF-8 Support)',
            'openssl' => 'OpenSSL (Verschlüsselung)',
            'curl' => 'cURL (HTTP-Requests)',
            'json' => 'JSON (Datenformat)',
            'hash' => 'Hash (Checksummen)'
        ];
        
        foreach ($required_extensions as $ext => $description) {
            if (extension_loaded($ext)) {
                echo '<div class="alert alert-success">✅ <strong>' . $description . ':</strong> Verfügbar</div>';
            } else {
                echo '<div class="alert alert-danger">❌ <strong>' . $description . ':</strong> Nicht gefunden</div>';
                $this->errors[] = "Extension fehlt: $ext";
            }
        }
        
        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->parseMemoryLimit($memory_limit);
        if ($memory_bytes >= 128 * 1024 * 1024 || $memory_limit === '-1') {
            echo '<div class="alert alert-success">✅ <strong>Memory Limit:</strong> ' . $memory_limit . '</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>Memory Limit:</strong> ' . $memory_limit . ' (Empfohlen: 256M oder höher)</div>';
            $this->warnings[] = "Memory Limit niedrig";
        }
        
        // Max Execution Time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time >= 300 || $max_execution_time == 0) {
            echo '<div class="alert alert-success">✅ <strong>Max Execution Time:</strong> ' . ($max_execution_time == 0 ? 'Unbegrenzt' : $max_execution_time . 's') . '</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>Max Execution Time:</strong> ' . $max_execution_time . 's (Empfohlen: 300s oder höher)</div>';
            $this->warnings[] = "Execution Time niedrig";
        }
        
        // Schreibrechte
        $write_test_dir = './';
        if (is_writable($write_test_dir)) {
            echo '<div class="alert alert-success">✅ <strong>Schreibrechte:</strong> Verfügbar im aktuellen Verzeichnis</div>';
        } else {
            echo '<div class="alert alert-danger">❌ <strong>Schreibrechte:</strong> Nicht verfügbar im aktuellen Verzeichnis</div>';
            $this->errors[] = "Keine Schreibrechte";
        }
        
        // File Upload
        $file_uploads = ini_get('file_uploads');
        if ($file_uploads) {
            echo '<div class="alert alert-success">✅ <strong>File Uploads:</strong> Aktiviert</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>File Uploads:</strong> Deaktiviert</div>';
        }
        
        echo '<hr class="section-divider">';
        
        if (empty($this->errors)) {
            echo '<div class="alert alert-success">
                <strong>🎉 Alle kritischen Systemanforderungen erfüllt!</strong><br>
                Das System ist bereit für die Installation.
            </div>';
            
            if (!empty($this->warnings)) {
                echo '<div class="alert alert-warning">
                    <strong>⚠️ Hinweise:</strong> Es wurden ' . count($this->warnings) . ' Warnungen gefunden. 
                    Das System funktioniert, aber optimale Performance ist nicht garantiert.
                </div>';
            }
            
            echo '<div style="text-align: center; margin-top: 30px;">
                <a href="?step=3" class="btn">Weiter zu Schritt 3 →</a>
                <a href="?step=1" class="btn btn-secondary">← Zurück</a>
            </div>';
        } else {
            echo '<div class="alert alert-danger">
                <strong>❌ Kritische Probleme gefunden!</strong><br>
                Bitte beheben Sie die ' . count($this->errors) . ' Fehler vor dem Fortfahren.
            </div>';
            echo '<div style="text-align: center; margin-top: 30px;">
                <a href="?step=2" class="btn">🔄 Erneut prüfen</a>
                <a href="?step=1" class="btn btn-secondary">← Zurück</a>
            </div>';
        }
    }
    
    /**
     * Verzeichnisse erstellen
     */
    private function handleDirectories() {
        ?>
        <h2>📁 Verzeichnisse konfigurieren</h2>
        <?php
        
        if ($_POST) {
            echo '<p>Erstelle benötigte Verzeichnisse...</p><hr class="section-divider">';
            
            // Debug-Information
            if (isset($_GET['debug'])) {
                echo '<div class="alert alert-info"><strong>Debug-Info:</strong><br>';
                echo 'PHP Version: ' . PHP_VERSION . '<br>';
                echo 'Current Working Dir: ' . getcwd() . '<br>';
                echo 'Script Dir: ' . __DIR__ . '<br>';
                echo 'POST Data: ' . htmlspecialchars(print_r($_POST, true)) . '</div>';
            }
            
            // POST-Daten validieren
            $required_fields = ['csv_input_dir', 'csv_archive_dir', 'csv_error_dir', 'log_dir'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    echo '<div class="alert alert-danger">❌ <strong>Fehler:</strong> Feld "' . $field . '" ist leer</div>';
                    $this->errors[] = "Feld leer: $field";
                    return;
                }
            }
            
            // POST-Daten verarbeiten
            $directories = [
                rtrim($_POST['csv_input_dir'], '/') . '/' => 'CSV Import Verzeichnis',
                rtrim($_POST['csv_archive_dir'], '/') . '/' => 'CSV Archiv Verzeichnis',
                rtrim($_POST['csv_error_dir'], '/') . '/' => 'CSV Fehler Verzeichnis',
                rtrim($_POST['log_dir'], '/') . '/' => 'Log Verzeichnis',
                './backups/' => 'Backup Verzeichnis'
            ];
            
            foreach ($directories as $path => $description) {
                echo '<div style="margin: 10px 0;">';
                echo '<strong>Bearbeite:</strong> ' . $description . ' (' . $path . ')<br>';
                
                // Prüfen ob Verzeichnis bereits existiert
                if (is_dir($path)) {
                    echo '<div class="alert alert-success">✅ <strong>Bereits vorhanden:</strong> ' . $description . '</div>';
                    $this->success[] = "Verzeichnis bereits vorhanden: $path";
                } else {
                    // Verzeichnis erstellen
                    echo 'Versuche Verzeichnis zu erstellen...<br>';
                    
                    // Prüfe Parent-Verzeichnis
                    $parent_dir = dirname($path);
                    if (!is_dir($parent_dir)) {
                        echo 'Parent-Verzeichnis: ' . $parent_dir . ' - ';
                        if (!is_writable(dirname($parent_dir))) {
                            echo '<span style="color: red;">Nicht beschreibbar</span><br>';
                            echo '<div class="alert alert-danger">❌ <strong>Fehler:</strong> Parent-Verzeichnis nicht beschreibbar: ' . $parent_dir . '</div>';
                            $this->errors[] = "Parent-Verzeichnis nicht beschreibbar: $parent_dir";
                            continue;
                        }
                        echo '<span style="color: green;">OK</span><br>';
                    }
                    
                    // Verzeichnis erstellen mit ausführlicher Fehlerbehandlung
                    if (@mkdir($path, 0755, true)) {
                        // Prüfen ob wirklich erstellt
                        if (is_dir($path)) {
                            echo '<div class="alert alert-success">✅ <strong>Erfolgreich erstellt:</strong> ' . $description . '</div>';
                            $this->success[] = "Verzeichnis erstellt: $path";
                        } else {
                            echo '<div class="alert alert-danger">❌ <strong>Erstellt aber nicht gefunden:</strong> ' . $path . '</div>';
                            $this->errors[] = "Verzeichnis erstellt aber nicht gefunden: $path";
                        }
                    } else {
                        // Detaillierte Fehleranalyse
                        $error = error_get_last();
                        $error_msg = $error ? $error['message'] : 'Unbekannter Fehler';
                        
                        echo '<div class="alert alert-danger">❌ <strong>Fehler beim Erstellen:</strong> ' . $description . '<br>';
                        echo '<strong>Pfad:</strong> ' . $path . '<br>';
                        echo '<strong>Fehler:</strong> ' . htmlspecialchars($error_msg) . '<br>';
                        echo '<strong>Permissions Parent:</strong> ' . substr(sprintf('%o', fileperms(dirname($path))), -4) . '</div>';
                        
                        $this->errors[] = "Kann Verzeichnis nicht erstellen: $path - " . $error_msg;
                    }
                }
                echo '</div>';
                
                // Flush output für Live-Updates
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            
            // Verzeichnisse in Session speichern für späteren Gebrauch
            session_start();
            $_SESSION['directories'] = $_POST;
            
            echo '<hr class="section-divider">';
            
            if (empty($this->errors)) {
                echo '<div class="alert alert-success">
                    <strong>🎉 Alle Verzeichnisse erfolgreich erstellt!</strong><br>
                    ' . count($this->success) . ' Verzeichnisse sind bereit für den Import.
                </div>';
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=4" class="btn">Weiter zu Schritt 4 →</a>
                </div>';
            } else {
                echo '<div class="alert alert-danger">
                    <strong>❌ Fehler beim Erstellen der Verzeichnisse!</strong><br>
                    ' . count($this->errors) . ' Probleme gefunden. Prüfen Sie die Dateiberechtigungen.
                </div>';
                
                echo '<div class="alert alert-info">
                    <strong>💡 Lösungsvorschläge:</strong>
                    <ul>
                        <li>Prüfen Sie die Berechtigungen des Web-Verzeichnisses</li>
                        <li>Erstellen Sie die Verzeichnisse manuell via FTP/SSH</li>
                        <li>Verwenden Sie absolute Pfade statt relativer Pfade</li>
                        <li>Kontaktieren Sie Ihren Hosting-Provider</li>
                    </ul>
                </div>';
                
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=3&debug=1" class="btn">🔄 Mit Debug-Info versuchen</a>
                    <a href="?step=3" class="btn btn-secondary">🔄 Erneut versuchen</a>
                </div>';
            }
            
        } else {
            // Formular anzeigen
            $defaults = $this->getConfigDefaults();
            ?>
            <p>Konfigurieren Sie die Verzeichnisse für das Import-System. Diese werden automatisch erstellt, falls sie nicht existieren.</p>
            
            <div class="alert alert-info">
                <strong>💡 Hinweis:</strong> Die Verzeichnisse werden relativ zum aktuellen Verzeichnis erstellt: <code><?php echo getcwd(); ?></code>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>📥 CSV Input Verzeichnis:</label>
                    <input type="text" name="csv_input_dir" value="<?php echo htmlspecialchars($defaults['csv_input_dir']); ?>" required>
                    <small>Hier werden neue CSV-Dateien für den Import abgelegt</small>
                </div>
                
                <div class="form-group">
                    <label>📦 CSV Archiv Verzeichnis:</label>
                    <input type="text" name="csv_archive_dir" value="<?php echo htmlspecialchars($defaults['csv_archive_dir']); ?>" required>
                    <small>Erfolgreich verarbeitete CSV-Dateien werden hier archiviert</small>
                </div>
                
                <div class="form-group">
                    <label>⚠️ CSV Fehler Verzeichnis:</label>
                    <input type="text" name="csv_error_dir" value="<?php echo htmlspecialchars($defaults['csv_error_dir']); ?>" required>
                    <small>Fehlerhafte CSV-Dateien werden hier zur Analyse abgelegt</small>
                </div>
                
                <div class="form-group">
                    <label>📋 Log Verzeichnis:</label>
                    <input type="text" name="log_dir" value="<?php echo htmlspecialchars($defaults['log_dir']); ?>" required>
                    <small>Hier werden alle System- und Import-Logs gespeichert</small>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn">📁 Verzeichnisse erstellen</button>
                    <a href="?step=2" class="btn btn-secondary">← Zurück</a>
                </div>
            </form>
            <?php
        }
    }
    
    /**
     * Datenbank-Setup
     */
    private function handleDatabase() {
        ?>
        <h2>🗄️ Datenbank-Setup</h2>
        <?php
        
        if ($_POST && isset($_POST['db_action'])) {
            echo '<p>Teste Datenbankverbindung und erstelle Tabellen...</p><hr class="section-divider">';
            
            // Datenbankverbindung testen und Tabellen erstellen
            try {
                $pdo = new PDO(
                    "mysql:host={$_POST['db_host']};charset=utf8mb4",
                    $_POST['db_user'],
                    $_POST['db_password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                echo '<div class="alert alert-success">✅ <strong>Datenbankverbindung erfolgreich</strong><br>Verbunden mit MySQL Server</div>';
                
                // Datenbank erstellen
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo '<div class="alert alert-success">✅ <strong>Datenbank bereit:</strong> ' . htmlspecialchars($_POST['db_name']) . '</div>';
                
                $pdo->exec("USE `{$_POST['db_name']}`");
                
                // Tabellen erstellen
                $this->createDatabaseTables($pdo);
                
                // Konfigurationsdaten in Session speichern
                session_start();
                $_SESSION['db_data'] = $_POST;
                
                echo '<hr class="section-divider">';
                echo '<div class="alert alert-success">
                    <strong>🎉 Datenbank-Setup erfolgreich abgeschlossen!</strong><br>
                    Alle Tabellen wurden erstellt und Beispieldaten eingefügt.
                </div>';
                
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=5" class="btn">Weiter zu Schritt 5 →</a>
                </div>';
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">
                    <strong>❌ Datenbankfehler:</strong><br>
                    <code>' . htmlspecialchars($e->getMessage()) . '</code><br><br>
                    Bitte prüfen Sie Ihre Datenbankeinstellungen und versuchen Sie es erneut.
                </div>';
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=4" class="btn">🔄 Erneut versuchen</a>
                </div>';
            }
            
        } else {
            // Formular anzeigen
            $defaults = $this->getConfigDefaults();
            ?>
            <p>Konfigurieren Sie die Datenbankverbindung. Die Datenbank wird automatisch erstellt, falls sie nicht existiert.</p>
            
            <div class="alert alert-info">
                <strong>ℹ️ Hinweis:</strong> Sie benötigen eine MySQL/MariaDB-Datenbank mit CREATE-Berechtigung. 
                Das Setup erstellt automatisch alle benötigten Tabellen.
            </div>
            
            <form method="POST">
                <input type="hidden" name="db_action" value="1">
                
                <div class="form-group">
                    <label>🌐 Datenbank Host:</label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($defaults['db_host']); ?>" required>
                    <small>IP-Adresse oder Hostname des Datenbankservers (meist "localhost")</small>
                </div>
                
                <div class="form-group">
                    <label>🗃️ Datenbank Name:</label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($defaults['db_name']); ?>" required>
                    <small>Name der Datenbank für das Import-System (wird erstellt, falls nicht vorhanden)</small>
                </div>
                
                <div class="form-group">
                    <label>👤 Datenbank Benutzer:</label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($defaults['db_user']); ?>" required>
                    <small>Benutzername mit CREATE-Berechtigung für die Datenbank</small>
                </div>
                
                <div class="form-group">
                    <label>🔑 Datenbank Passwort:</label>
                    <input type="password" name="db_password" value="<?php echo htmlspecialchars($defaults['db_pass']); ?>" autocomplete="new-password">
                    <small>Passwort für die Datenbankverbindung (leer lassen wenn kein Passwort)</small>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn">🗄️ Datenbank testen & einrichten</button>
                    <a href="?step=3" class="btn btn-secondary">← Zurück</a>
                </div>
            </form>
            <?php
        }
    }
    
    /**
     * Konfiguration erstellen
     */
    private function handleConfiguration() {
        ?>
        <h2>⚙️ System-Konfiguration</h2>
        <?php
        
        if ($_POST && isset($_POST['config_action'])) {
            echo '<p>Erstelle Konfigurationsdatei...</p><hr class="section-divider">';
            
            // Konfigurationsdatei erstellen
            session_start();
            $db_data = $_SESSION['db_data'] ?? [];
            $dir_data = $_SESSION['directories'] ?? [];
            $config_data = array_merge($db_data, $dir_data, $_POST);
            
            $config_content = $this->generateConfigFile($config_data);
            
            if (file_put_contents('config.php', $config_content)) {
                echo '<div class="alert alert-success">✅ <strong>Konfigurationsdatei erfolgreich erstellt:</strong> config.php</div>';
                
                echo '<div class="alert alert-info">
                    <strong>📄 Konfigurationsdatei Vorschau:</strong>
                    <div class="code-block" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">' 
                    . htmlspecialchars(substr($config_content, 0, 800)) . '
                    ...<br><em>Vollständige Datei in config.php gespeichert</em></div>
                </div>';
                
                // Erfolgreiche Konfiguration in Session speichern
                $_SESSION['config_complete'] = true;
                
                echo '<hr class="section-divider">';
                echo '<div class="alert alert-success">
                    <strong>🎉 Konfiguration erfolgreich erstellt!</strong><br>
                    Das System ist jetzt bereit für den ersten Test.
                </div>';
                
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=6" class="btn">Weiter zu Schritt 6 →</a>
                </div>';
            } else {
                echo '<div class="alert alert-danger">
                    <strong>❌ Fehler beim Erstellen der Konfigurationsdatei</strong><br>
                    Prüfen Sie die Schreibrechte im aktuellen Verzeichnis.
                </div>';
                echo '<div style="text-align: center; margin-top: 30px;">
                    <a href="?step=5" class="btn">🔄 Erneut versuchen</a>
                </div>';
            }
            
        } else {
            // Formular anzeigen
            $defaults = $this->getConfigDefaults();
            ?>
            <p>Konfigurieren Sie die E-Mail-Benachrichtigungen und wichtige System-Parameter für das Import-System.</p>
            
            <form method="POST">
                <input type="hidden" name="config_action" value="1">
                
                <h3 style="color: #0099cc; border-bottom: 2px solid #0099cc; padding-bottom: 10px;">📧 E-Mail-Einstellungen</h3>
                
                <div class="form-group">
                    <label>👨‍💼 Administrator E-Mail:</label>
                    <input type="email" name="admin_email" value="<?php echo htmlspecialchars($defaults['admin_email']); ?>" required>
                    <small>An diese Adresse werden Import-Berichte und Fehlermeldungen gesendet</small>
                </div>
                
                <div class="form-group">
                    <label>📤 Absender E-Mail:</label>
                    <input type="email" name="sender_email" value="<?php echo htmlspecialchars($defaults['sender_email']); ?>" required>
                    <small>Von dieser Adresse werden alle System-E-Mails versendet</small>
                </div>
                
                <h3 style="color: #0099cc; border-bottom: 2px solid #0099cc; padding-bottom: 10px; margin-top: 40px;">⚙️ System-Einstellungen</h3>
                
                <div class="form-group">
                    <label>🧪 Dry Run Modus (Testmodus):</label>
                    <select name="dry_run" required>
                        <option value="true">✅ Aktiviert (empfohlen für ersten Test)</option>
                        <option value="false">❌ Deaktiviert (für Produktionsbetrieb)</option>
                    </select>
                    <small>Im Dry-Run-Modus werden CSV-Dateien analysiert, aber keine Daten importiert</small>
                </div>
                
                <div class="form-group">
                    <label>📊 Maximale Import-Zeilen pro Datei:</label>
                    <input type="number" name="max_import_rows" value="10000" min="100" max="100000" required>
                    <small>Sicherheitslimit für große CSV-Dateien (verhindert Überlastung)</small>
                </div>
                
                <div class="form-group">
                    <label>📦 Batch-Größe:</label>
                    <input type="number" name="batch_size" value="500" min="10" max="5000" required>
                    <small>Anzahl Datensätze pro Datenbank-Transaction (höher = schneller, aber mehr Speicher)</small>
                </div>
                
                <div class="form-group">
                    <label>💾 Memory Limit:</label>
                    <select name="memory_limit">
                        <option value="256M">256M (Standard)</option>
                        <option value="512M">512M (Große CSV-Dateien)</option>
                        <option value="1G">1G (Sehr große Dateien)</option>
                    </select>
                    <small>Speicherlimit für das Import-Script</small>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn">⚙️ Konfiguration erstellen</button>
                    <a href="?step=4" class="btn btn-secondary">← Zurück</a>
                </div>
            </form>
            <?php
        }
    }
    
    /**
     * System testen
     */
    private function testSystem() {
        ?>
        <h2>🧪 System-Test</h2>
        <p>Überprüfung aller Komponenten und Erstellung einer Test-CSV-Datei...</p>
        
        <hr class="section-divider">
        <?php
        
        if (!file_exists('config.php')) {
            echo '<div class="alert alert-danger">❌ <strong>Konfigurationsdatei nicht gefunden</strong><br>Bitte kehren Sie zu Schritt 5 zurück.</div>';
            echo '<div style="text-align: center; margin-top: 30px;">
                <a href="?step=5" class="btn">← Zurück zur Konfiguration</a>
            </div>';
            return;
        }
        
        require_once 'config.php';
        
        // Datenbankverbindung testen
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo '<div class="alert alert-success">✅ <strong>Datenbankverbindung:</strong> Erfolgreich</div>';
            
            // Tabellen prüfen
            $tables = [
                'pos_import_log' => 'Import-Protokoll',
                'pos_sales' => 'Verkaufsdaten',
                'pos_products' => 'Produkte',
                'pos_payment_methods' => 'Zahlungsarten',
                'pos_import_errors' => 'Import-Fehler'
            ];
            
            foreach ($tables as $table => $description) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo '<div class="alert alert-success">✅ <strong>Tabelle ' . $description . ':</strong> Gefunden</div>';
                } else {
                    echo '<div class="alert alert-danger">❌ <strong>Tabelle ' . $description . ':</strong> Fehlt</div>';
                    $this->errors[] = "Tabelle fehlt: $table";
                }
            }
            
            // Anzahl Produkte und Zahlungsarten prüfen
            $stmt = $pdo->query("SELECT COUNT(*) FROM pos_products");
            $product_count = $stmt->fetchColumn();
            echo '<div class="alert alert-success">✅ <strong>Produkte in Datenbank:</strong> ' . $product_count . '</div>';
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM pos_payment_methods");
            $payment_count = $stmt->fetchColumn();
            echo '<div class="alert alert-success">✅ <strong>Zahlungsarten in Datenbank:</strong> ' . $payment_count . '</div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ <strong>Datenbanktest fehlgeschlagen:</strong><br><code>' . htmlspecialchars($e->getMessage()) . '</code></div>';
            $this->errors[] = "Datenbankfehler";
        }
        
        // Verzeichnisse prüfen
        $directories = [
            CSV_INPUT_DIR => 'CSV Input',
            CSV_ARCHIVE_DIR => 'CSV Archiv', 
            CSV_ERROR_DIR => 'CSV Fehler',
            LOG_DIR => 'Logs'
        ];
        
        foreach ($directories as $dir => $name) {
            if (is_dir($dir) && is_writable($dir)) {
                echo '<div class="alert alert-success">✅ <strong>Verzeichnis ' . $name . ':</strong> Bereit (' . $dir . ')</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ <strong>Verzeichnis ' . $name . ':</strong> Problem (' . $dir . ')</div>';
                $this->warnings[] = "Verzeichnis-Problem: $dir";
            }
        }
        
        // Test-CSV erstellen
        $test_csv_content = "Datum/Uhrzeit;Preis;Bezeichnung;Menge;Zahlung;Bonnr.;\n";
        $test_csv_content .= date('d.m.Y H:i:s') . ";5,50;Erw. Tageskarte;1;Bar;99999;\n";
        $test_csv_content .= date('d.m.Y H:i:s', strtotime('+1 minute')) . ";3,50;Kind Tageskarte;1;EC-Cash;99998;\n";
        $test_csv_content .= date('d.m.Y H:i:s', strtotime('+2 minutes')) . ";12,00;Fam Tageskarte;1;Bar;99997;\n";
        
        $test_file = CSV_INPUT_DIR . 'test_setup_' . date('Ymd_His') . '.csv';
        if (file_put_contents($test_file, $test_csv_content)) {
            echo '<div class="alert alert-success">✅ <strong>Test-CSV erstellt:</strong> ' . basename($test_file) . '</div>';
            echo '<div class="alert alert-info">
                <strong>📄 Test-CSV Inhalt:</strong>
                <div class="code-block">' . htmlspecialchars($test_csv_content) . '</div>
            </div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>Test-CSV:</strong> Konnte nicht erstellt werden</div>';
        }
        
        // Import-Script prüfen
        if (file_exists('import_sales.php')) {
            echo '<div class="alert alert-success">✅ <strong>Import-Script:</strong> Gefunden (import_sales.php)</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>Import-Script:</strong> Nicht gefunden (import_sales.php)</div>';
        }
        
        // Health-Check-Script prüfen
        if (file_exists('health_check.php')) {
            echo '<div class="alert alert-success">✅ <strong>Health-Check-Script:</strong> Gefunden (health_check.php)</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ <strong>Health-Check-Script:</strong> Nicht gefunden (health_check.php)</div>';
        }
        
        echo '<hr class="section-divider">';
        
        if (empty($this->errors)) {
            echo '<div class="alert alert-success">
                <strong>🎉 System-Test erfolgreich abgeschlossen!</strong><br>
                Alle Komponenten funktionieren ordnungsgemäß. Das System ist bereit für den Produktivbetrieb.
            </div>';
            
            if (!empty($this->warnings)) {
                echo '<div class="alert alert-warning">
                    <strong>⚠️ Hinweise:</strong> ' . count($this->warnings) . ' Warnungen wurden gefunden. 
                    Das System funktioniert, aber prüfen Sie die Warnungen für optimale Performance.
                </div>';
            }
            
            echo '<div style="text-align: center; margin-top: 30px;">
                <a href="?step=7" class="btn">Zum Abschluss →</a>
                <a href="?step=6" class="btn btn-secondary">🔄 Test wiederholen</a>
            </div>';
        } else {
            echo '<div class="alert alert-danger">
                <strong>❌ System-Test nicht bestanden!</strong><br>
                ' . count($this->errors) . ' kritische Probleme müssen behoben werden.
            </div>';
            echo '<div style="text-align: center; margin-top: 30px;">
                <a href="?step=6" class="btn">🔄 Test wiederholen</a>
                <a href="?step=5" class="btn btn-secondary">← Zurück zur Konfiguration</a>
            </div>';
        }
    }
    
    /**
     * Setup-Abschluss
     */
    private function showCompletion() {
        ?>
        <h2>🎉 Setup erfolgreich abgeschlossen!</h2>
        
        <div class="alert alert-success" style="text-align: center; font-size: 18px; padding: 30px;">
            <strong>🏆 Herzlichen Glückwunsch!</strong><br>
            Das SV Freibad Import-System wurde erfolgreich installiert und konfiguriert.
        </div>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h4>📋 Nächste Schritte</h4>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>SMTP-Passwort eintragen</strong> in config.php</li>
                    <li><strong>Ersten Test durchführen</strong> mit Dry-Run</li>
                    <li><strong>Cron-Job einrichten</strong> für automatischen Import</li>
                    <li><strong>CSV-Dateien bereitstellen</strong> im Input-Verzeichnis</li>
                </ol>
            </div>
            
            <div class="feature-card">
                <h4>🔧 Verfügbare Scripts</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>import_sales.php</strong> - Haupt-Import</li>
                    <li><strong>health_check.php</strong> - System-Überwachung</li>
                    <li><strong>config.php</strong> - Konfiguration</li>
                    <li><strong>web_setup.php</strong> - Dieses Setup</li>
                </ul>
            </div>
        </div>
        
        <hr class="section-divider">
        
        <h3>💻 Kommandozeilen-Befehle</h3>
        <div class="code-block">
<strong># Ersten Dry-Run Test durchführen</strong>
php import_sales.php --dry-run

<strong># Health-Check ausführen</strong>
php health_check.php

<strong># Produktiv-Import starten</strong>
php import_sales.php

<strong># Cron-Job einrichten (täglich um 22:00 Uhr)</strong>
0 22 * * * /usr/bin/php <?php echo realpath('.'); ?>/import_sales.php
        </div>
        
        <h3>📁 Wichtige Verzeichnisse</h3>
        <?php if (defined('CSV_INPUT_DIR')): ?>
        <div class="feature-grid">
            <div class="feature-card">
                <h4>📥 Input-Verzeichnis</h4>
                <code><?php echo CSV_INPUT_DIR; ?></code>
                <p>CSV-Dateien hier ablegen</p>
            </div>
            <div class="feature-card">
                <h4>📦 Archiv-Verzeichnis</h4>
                <code><?php echo CSV_ARCHIVE_DIR; ?></code>
                <p>Verarbeitete Dateien</p>
            </div>
            <div class="feature-card">
                <h4>⚠️ Fehler-Verzeichnis</h4>
                <code><?php echo CSV_ERROR_DIR; ?></code>
                <p>Fehlerhafte Dateien</p>
            </div>
            <div class="feature-card">
                <h4>📋 Log-Verzeichnis</h4>
                <code><?php echo LOG_DIR; ?></code>
                <p>System-Logs</p>
            </div>
        </div>
        <?php endif; ?>
        
        <hr class="section-divider">
        
        <div class="alert alert-warning">
            <strong>🔒 Wichtige Sicherheitshinweise:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>Löschen Sie web_setup.php</strong> aus Sicherheitsgründen nach dem Setup</li>
                <li><strong>Prüfen Sie die Berechtigungen</strong> der config.php (sollte nicht öffentlich lesbar sein)</li>
                <li><strong>Tragen Sie das SMTP-Passwort</strong> in die config.php ein</li>
                <li><strong>Aktivieren Sie HTTPS</strong> für den Produktivbetrieb</li>
                <li><strong>Führen Sie regelmäßige Backups</strong> der Datenbank durch</li>
            </ul>
        </div>
        
        <h3>🧪 Erste Tests</h3>
        <p>Führen Sie diese Tests durch, um sicherzustellen, dass alles funktioniert:</p>
        <div class="code-block">
<strong>1. Test-Import mit der erstellten CSV-Datei:</strong>
php import_sales.php --dry-run

<strong>2. System-Status prüfen:</strong>
php health_check.php

<strong>3. Log-Dateien überwachen:</strong>
tail -f <?php echo defined('LOG_DIR') ? LOG_DIR : './logs/'; ?>*.log

<strong>4. Test-CSV manuell prüfen:</strong>
ls -la <?php echo defined('CSV_INPUT_DIR') ? CSV_INPUT_DIR : './csv_import/'; ?>
        </div>
        
        <hr class="section-divider">
        
        <h3>📞 Support & Wartung</h3>
        <div class="alert alert-info">
            <strong>Bei Problemen:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Prüfen Sie die Log-Dateien im Log-Verzeichnis</li>
                <li>Führen Sie den Health-Check aus</li>
                <li>Verwenden Sie den Dry-Run-Modus für Tests</li>
                <li>Kontrollieren Sie die Datei-Berechtigungen</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <?php if (file_exists('import_sales.php')): ?>
            <a href="import_sales.php" class="btn btn-success" style="font-size: 18px;">
                🚀 Import-System testen
            </a>
            <?php endif; ?>
            
            <?php if (file_exists('health_check.php')): ?>
            <a href="health_check.php" class="btn" style="font-size: 18px;">
                🏥 Health-Check ausführen
            </a>
            <?php endif; ?>
            
            <a href="?step=1" class="btn btn-secondary">
                🔄 Setup erneut durchführen
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="color: #0099cc;">🏊‍♂️ SV Freibad Dabringhausen e.V.</h4>
            <p style="margin: 0; color: #666;">Import-System erfolgreich installiert • Version 1.0</p>
        </div>
        <?php
    }
    
    /**
     * Standard-Konfigurationswerte laden
     */
    private function getConfigDefaults() {
        $defaults = [
            'db_host' => 'localhost',
            'db_name' => 'sv_freibad_db',
            'db_user' => 'freibad_user',
            'db_pass' => '',
            'admin_email' => 'admin@sv-freibad-dabringhausen.de',
            'sender_email' => 'noreply@sv-freibad-dabringhausen.de',
            'csv_input_dir' => './csv_import/',
            'csv_archive_dir' => './csv_archive/',
            'csv_error_dir' => './csv_errors/',
            'log_dir' => './logs/'
        ];
        
        // Wenn config.php bereits existiert, Werte daraus laden
        if (file_exists('config.php')) {
            include 'config.php';
            
            if (defined('DB_HOST')) $defaults['db_host'] = DB_HOST;
            if (defined('DB_NAME')) $defaults['db_name'] = DB_NAME;
            if (defined('DB_USER')) $defaults['db_user'] = DB_USER;
            if (defined('DB_PASS')) $defaults['db_pass'] = DB_PASS;
            if (defined('ADMIN_EMAIL')) $defaults['admin_email'] = ADMIN_EMAIL;
            if (defined('SENDER_EMAIL')) $defaults['sender_email'] = SENDER_EMAIL;
            if (defined('CSV_INPUT_DIR')) $defaults['csv_input_dir'] = CSV_INPUT_DIR;
            if (defined('CSV_ARCHIVE_DIR')) $defaults['csv_archive_dir'] = CSV_ARCHIVE_DIR;
            if (defined('CSV_ERROR_DIR')) $defaults['csv_error_dir'] = CSV_ERROR_DIR;
            if (defined('LOG_DIR')) $defaults['log_dir'] = LOG_DIR;
        }
        
        return $defaults;
    }
    
    /**
     * Datenbank-Tabellen erstellen
     */
    private function createDatabaseTables($pdo) {
        $tables_sql = [
            // Import Log Tabelle
            "CREATE TABLE IF NOT EXISTS `pos_import_log` (
                `import_id` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(255) NOT NULL,
                `file_hash` varchar(64) NOT NULL,
                `import_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `total_rows` int(11) NOT NULL DEFAULT 0,
                `imported_rows` int(11) NOT NULL DEFAULT 0,
                `error_rows` int(11) NOT NULL DEFAULT 0,
                `status` enum('SUCCESS','ERROR','PARTIAL') NOT NULL,
                `dry_run` tinyint(1) NOT NULL DEFAULT 0,
                `error_message` text DEFAULT NULL,
                PRIMARY KEY (`import_id`),
                UNIQUE KEY `file_hash` (`file_hash`),
                KEY `import_date` (`import_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Payment Methods Tabelle
            "CREATE TABLE IF NOT EXISTS `pos_payment_methods` (
                `payment_method_id` int(11) NOT NULL AUTO_INCREMENT,
                `method_name` varchar(50) NOT NULL,
                PRIMARY KEY (`payment_method_id`),
                UNIQUE KEY `method_name` (`method_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Products Tabelle
            "CREATE TABLE IF NOT EXISTS `pos_products` (
                `product_id` int(11) NOT NULL AUTO_INCREMENT,
                `description` text NOT NULL,
                `subcategory_id` int(11) DEFAULT NULL,
                PRIMARY KEY (`product_id`),
                KEY `description` (`description`(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Sales Tabelle
            "CREATE TABLE IF NOT EXISTS `pos_sales` (
                `sale_id` int(11) NOT NULL AUTO_INCREMENT,
                `transaction_date` datetime NOT NULL,
                `price` decimal(10,2) NOT NULL,
                `product_id` int(11) DEFAULT NULL,
                `product_description` text NOT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `payment_method_id` int(11) DEFAULT NULL,
                `payment_method` varchar(50) NOT NULL,
                `receipt_number` varchar(20) NOT NULL,
                `import_id` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`sale_id`),
                KEY `transaction_date` (`transaction_date`),
                KEY `receipt_number` (`receipt_number`),
                KEY `product_id` (`product_id`),
                KEY `payment_method_id` (`payment_method_id`),
                KEY `import_id` (`import_id`),
                FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`product_id`) ON DELETE SET NULL,
                FOREIGN KEY (`payment_method_id`) REFERENCES `pos_payment_methods` (`payment_method_id`) ON DELETE SET NULL,
                FOREIGN KEY (`import_id`) REFERENCES `pos_import_log` (`import_id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // Import Errors Tabelle
            "CREATE TABLE IF NOT EXISTS `pos_import_errors` (
                `error_id` int(11) NOT NULL AUTO_INCREMENT,
                `import_id` int(11) NOT NULL,
                `row_number` int(11) NOT NULL,
                `csv_data` text NOT NULL,
                `error_message` text NOT NULL,
                `error_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`error_id`),
                KEY `import_id` (`import_id`),
                FOREIGN KEY (`import_id`) REFERENCES `pos_import_log` (`import_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        $table_names = [
            'Import-Protokoll',
            'Zahlungsarten',
            'Produkte', 
            'Verkaufsdaten',
            'Import-Fehler'
        ];
        
        foreach ($tables_sql as $i => $sql) {
            try {
                $pdo->exec($sql);
                echo '<div class="alert alert-success">✅ <strong>Tabelle erstellt:</strong> ' . $table_names[$i] . '</div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">❌ <strong>Fehler bei Tabelle ' . $table_names[$i] . ':</strong><br><code>' . htmlspecialchars($e->getMessage()) . '</code></div>';
                $this->errors[] = "Tabellenfehler: " . $table_names[$i];
            }
        }
        
        // Beispieldaten einfügen
        $this->insertSampleData($pdo);
    }
    
    /**
     * Beispieldaten einfügen
     */
    private function insertSampleData($pdo) {
        // Zahlungsarten
        $payment_methods = [
            ['payment_method_id' => 1, 'method_name' => 'Bar'],
            ['payment_method_id' => 2, 'method_name' => 'EC-Cash'],
            ['payment_method_id' => 3, 'method_name' => 'Kreditkarte'],
            ['payment_method_id' => 4, 'method_name' => 'PayPal']
        ];
        
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO pos_payment_methods (payment_method_id, method_name) VALUES (?, ?)");
            foreach ($payment_methods as $method) {
                $stmt->execute([$method['payment_method_id'], $method['method_name']]);
            }
            echo '<div class="alert alert-success">✅ <strong>Zahlungsarten eingefügt:</strong> ' . count($payment_methods) . ' Methoden</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-warning">⚠️ <strong>Zahlungsarten-Fehler:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // Beispielprodukte aus der ursprünglichen DDL
        $products = [
            'Erw. Saisoinkarte - Besucher -',
            'Familienkarte          6 Wochen      - Besucher -',
            'Einlage: Anfangsbestand als Einzahlung eingebucht. Kurt Genz 11:06 am 5.7.2024',
            'Saisonkarte Erwachsen',
            'Kind Tageskarte',
            'Zehnerkarte    Ermäßigt',
            'Saisonkarte ermäßigt',
            'Saisonkarte Familie 6W',
            'Entnahme: Entnahme',
            'Saisonkarte Familie 3W',
            'Wasserspritze',
            'Fam Tageskarte',
            'Wasserball',
            'Erw. Tageskarte',
            'Zehnerkarte Erwachsen',
            'Kind Zehnerkarte - Besucher -',
            'Frei',
            'Fam Tageskarte      - Besucher -',
            'Erw. Tageskarte ab 18 Uhr',
            'Kind Tageskarte ab 18 Uhr',
            'RGA Familienkart 15 €',
            'Stadtpass bis 18 Jahre',
            'Stadtpass ab 18 Jahre',
            'Freikart KiJUPa',
            'Begleitung Behinderte              - frei -',
            'Erw. Zehnerkarte - Besucher -',
            'Fam Tageskarte erm. Kinderkarte (2,50 )',
            'Schwimmflügel alle',
            'Schwimminsel Babys',
            'Schwimmnudel',
            'Wasserspielring',
            'Freikarte Weihnachtsmarkt',
            'Kind Saisonkarte - Besucher -',
            'Kleinkind            - Besucher -',
            'Wasserpistole groß'
        ];
        
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO pos_products (description) VALUES (?)");
            $inserted = 0;
            foreach ($products as $product) {
                if ($stmt->execute([$product])) {
                    $inserted++;
                }
            }
            echo '<div class="alert alert-success">✅ <strong>Beispielprodukte eingefügt:</strong> ' . $inserted . ' von ' . count($products) . ' Produkten</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-warning">⚠️ <strong>Produkte-Fehler:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    /**
     * Konfigurationsdatei generieren
     */
    private function generateConfigFile($config) {
        $dry_run_value = ($config['dry_run'] === 'true') ? 'true' : 'false';
        $memory_limit = $config['memory_limit'] ?? '256M';
        
        return "<?php
/**
 * Konfigurationsdatei für SV Freibad Dabringhausen e.V. - Verkaufserlös Import
 * Automatisch generiert am: " . date('Y-m-d H:i:s') . "
 * Setup Version: 1.0
 */

// Datenbankverbindung
define('DB_HOST', '" . addslashes($config['db_host']) . "');
define('DB_NAME', '" . addslashes($config['db_name']) . "');
define('DB_USER', '" . addslashes($config['db_user']) . "');
define('DB_PASS', '" . addslashes($config['db_password']) . "');
define('DB_CHARSET', 'utf8mb4');

// Dateipfade
define('CSV_INPUT_DIR', '" . addslashes($config['csv_input_dir'] ?? './csv_import/') . "');
define('CSV_ARCHIVE_DIR', '" . addslashes($config['csv_archive_dir'] ?? './csv_archive/') . "');
define('CSV_ERROR_DIR', '" . addslashes($config['csv_error_dir'] ?? './csv_errors/') . "');
define('LOG_DIR', '" . addslashes($config['log_dir'] ?? './logs/') . "');

// CSV-Einstellungen
define('CSV_DELIMITER', ';');
define('CSV_ENCLOSURE', '\"');
define('CSV_ESCAPE', '\\\\');

// Dry Run Modus
define('DRY_RUN_MODE', $dry_run_value);

// E-Mail Konfiguration
define('ADMIN_EMAIL', '" . addslashes($config['admin_email']) . "');
define('SENDER_EMAIL', '" . addslashes($config['sender_email']) . "');
define('SMTP_HOST', 'smtp.strato.de');
define('SMTP_PORT', 587);
define('SMTP_USER', '" . addslashes($config['sender_email']) . "');
define('SMTP_PASS', 'smtp_password_hier_eintragen');
define('SMTP_SECURE', 'tls');

// Logging-Einstellungen
define('LOG_LEVEL', 'INFO');
define('LOG_MAX_SIZE', 10485760); // 10MB
define('LOG_ROTATE_COUNT', 5);

// Import-Einstellungen
define('MAX_IMPORT_ROWS', " . intval($config['max_import_rows'] ?? 10000) . ");
define('BATCH_SIZE', " . intval($config['batch_size'] ?? 500) . ");

// Zeitzone
define('TIMEZONE', 'Europe/Berlin');

// Archivierung
define('ARCHIVE_RETENTION_DAYS', 365);

// Fehlerbehandlung
define('CONTINUE_ON_ERROR', true);
define('MAX_ERRORS_PER_FILE', 50);

// Validierung
define('VALIDATE_DATES', true);
define('VALIDATE_PRICES', true);
define('ALLOW_NEGATIVE_PRICES', true);

// Duplikat-Prüfung
define('CHECK_DUPLICATES', true);
define('DUPLICATE_CHECK_METHODS', ['file_hash', 'bonnr_check']);

// Performance
define('MEMORY_LIMIT', '" . addslashes($memory_limit) . "');
define('MAX_EXECUTION_TIME', 300);

// Debug-Modus
define('DEBUG_MODE', false);
define('VERBOSE_LOGGING', false);

// Sicherheit
define('ALLOWED_FILE_EXTENSIONS', ['csv', 'txt']);
define('MAX_FILE_SIZE', 5242880); // 5MB
?>";
    }
    
    /**
     * Memory Limit parsen
     */
    private function parseMemoryLimit($limit) {
        if ($limit === '-1') return PHP_INT_MAX;
        
        $limit = strtolower(trim($limit));
        $bytes = intval($limit);
        
        if (strpos($limit, 'g') !== false) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (strpos($limit, 'm') !== false) {
            $bytes *= 1024 * 1024;
        } elseif (strpos($limit, 'k') !== false) {
            $bytes *= 1024;
        }
        
        return $bytes;
    }
}

// Session für mehrstufiges Setup starten
session_start();

// Setup ausführen
$setup = new WebSystemSetup();
$setup->runWebSetup();

?>