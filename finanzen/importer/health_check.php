<?php
/**
 * Health Check Script für SV Freibad Import-System
 * 
 * Überwacht Systemstatus, Datenbankverbindung und Verzeichnisse
 * Sendet Benachrichtigungen bei Problemen
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 1.0
 */

require_once 'config.php';

class HealthChecker {
    
    private $issues = [];
    private $warnings = [];
    private $info = [];
    
    public function __construct() {
        date_default_timezone_set(TIMEZONE);
    }
    
    /**
     * Vollständigen Health Check durchführen
     */
    public function performHealthCheck() {
        echo "🏥 SV Freibad Import-System Health Check\n";
        echo "=" . str_repeat("=", 45) . "\n";
        echo "Gestartet: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->checkDatabase();
        $this->checkDirectories();
        $this->checkDiskSpace();
        $this->checkLogFiles();
        $this->checkImportStatus();
        $this->checkConfigFiles();
        $this->checkPermissions();
        $this->checkCronJobs();
        
        $this->generateReport();
        $this->sendNotificationIfNeeded();
    }
    
    /**
     * Datenbankverbindung prüfen
     */
    private function checkDatabase() {
        echo "🔍 Datenbankverbindung prüfen...\n";
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Verbindung testen
            $stmt = $pdo->query("SELECT 1");
            
            // Tabellenstruktur prüfen
            $required_tables = ['pos_sales', 'pos_import_log', 'pos_products', 'pos_payment_methods'];
            foreach ($required_tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    $this->issues[] = "Tabelle '$table' nicht gefunden";
                }
            }
            
            // Letzte Imports prüfen
            $stmt = $pdo->query("SELECT COUNT(*) as count, MAX(import_date) as last_import 
                                FROM pos_import_log 
                                WHERE import_date > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                $this->warnings[] = "Keine Imports in den letzten 7 Tagen";
            } else {
                $this->info[] = "Letzte Imports: {$result['count']} in 7 Tagen, zuletzt: {$result['last_import']}";
            }
            
            echo "✅ Datenbankverbindung OK\n";
            
        } catch (PDOException $e) {
            $this->issues[] = "Datenbankfehler: " . $e->getMessage();
            echo "❌ Datenbankverbindung fehlgeschlagen\n";
        }
    }
    
    /**
     * Verzeichnisse prüfen
     */
    private function checkDirectories() {
        echo "📁 Verzeichnisse prüfen...\n";
        
        $directories = [
            'CSV Input' => CSV_INPUT_DIR,
            'CSV Archive' => CSV_ARCHIVE_DIR,
            'CSV Errors' => CSV_ERROR_DIR,
            'Logs' => LOG_DIR
        ];
        
        foreach ($directories as $name => $path) {
            if (!is_dir($path)) {
                $this->issues[] = "Verzeichnis '$name' nicht gefunden: $path";
            } elseif (!is_writable($path)) {
                $this->issues[] = "Verzeichnis '$name' nicht beschreibbar: $path";
            } else {
                // Dateien im Verzeichnis zählen
                $files = glob($path . '*');
                $count = count($files);
                $this->info[] = "$name: $count Dateien";
                
                // Warnungen für ungewöhnliche Zustände
                if ($name === 'CSV Input' && $count > 10) {
                    $this->warnings[] = "Viele Dateien im Input-Verzeichnis ($count) - möglicherweise Import-Rückstau";
                }
                if ($name === 'CSV Errors' && $count > 5) {
                    $this->warnings[] = "Viele Fehler-Dateien ($count) - System prüfen";
                }
            }
        }
        
        echo "✅ Verzeichnisse geprüft\n";
    }
    
    /**
     * Speicherplatz prüfen
     */
    private function checkDiskSpace() {
        echo "💾 Speicherplatz prüfen...\n";
        
        $paths = [
            'Root' => '/',
            'Log Directory' => LOG_DIR,
            'CSV Directory' => CSV_INPUT_DIR
        ];
        
        foreach ($paths as $name => $path) {
            if (is_dir($path)) {
                $bytes = disk_free_space($path);
                $total = disk_total_space($path);
                
                if ($bytes !== false && $total !== false) {
                    $percent_free = ($bytes / $total) * 100;
                    $free_gb = round($bytes / 1024 / 1024 / 1024, 2);
                    
                    $this->info[] = "$name: {$free_gb}GB frei (" . round($percent_free, 1) . "%)";
                    
                    if ($percent_free < 10) {
                        $this->issues[] = "Kritisch wenig Speicherplatz in $name: nur {$percent_free}% frei";
                    } elseif ($percent_free < 20) {
                        $this->warnings[] = "Wenig Speicherplatz in $name: nur {$percent_free}% frei";
                    }
                }
            }
        }
        
        echo "✅ Speicherplatz geprüft\n";
    }
    
    /**
     * Log-Dateien prüfen
     */
    private function checkLogFiles() {
        echo "📋 Log-Dateien prüfen...\n";
        
        $log_files = glob(LOG_DIR . '*.log');
        
        foreach ($log_files as $log_file) {
            $filename = basename($log_file);
            $size = filesize($log_file);
            $age_days = (time() - filemtime($log_file)) / 86400;
            
            $this->info[] = "Log '$filename': " . round($size/1024) . "KB, " . round($age_days, 1) . " Tage alt";
            
            // Größe prüfen
            if ($size > LOG_MAX_SIZE) {
                $this->warnings[] = "Log-Datei '$filename' zu groß: " . round($size/1024/1024, 1) . "MB";
            }
            
            // Auf Fehler in aktueller Log-Datei prüfen
            if ($age_days < 1) {
                $content = file_get_contents($log_file);
                $error_count = substr_count($content, '[ERROR]');
                $warning_count = substr_count($content, '[WARNING]');
                
                if ($error_count > 0) {
                    $this->warnings[] = "Log '$filename': $error_count Fehler heute";
                }
                if ($warning_count > 5) {
                    $this->warnings[] = "Log '$filename': $warning_count Warnungen heute";
                }
            }
        }
        
        echo "✅ Log-Dateien geprüft\n";
    }
    
    /**
     * Import-Status der letzten 24h prüfen
     */
    private function checkImportStatus() {
        echo "📊 Import-Status prüfen...\n";
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Imports der letzten 24 Stunden
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_imports,
                    SUM(imported_rows) as total_rows,
                    SUM(error_rows) as error_rows,
                    COUNT(CASE WHEN status = 'ERROR' THEN 1 END) as failed_imports
                FROM pos_import_log 
                WHERE import_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $stats = $stmt->fetch();
            
            $this->info[] = "Letzte 24h: {$stats['total_imports']} Imports, {$stats['total_rows']} Zeilen";
            
            if ($stats['failed_imports'] > 0) {
                $this->warnings[] = "{$stats['failed_imports']} fehlgeschlagene Imports in den letzten 24h";
            }
            
            if ($stats['error_rows'] > 0) {
                $error_rate = ($stats['error_rows'] / max($stats['total_rows'], 1)) * 100;
                if ($error_rate > 5) {
                    $this->warnings[] = "Hohe Fehlerrate: " . round($error_rate, 2) . "%";
                }
            }
            
        } catch (Exception $e) {
            $this->warnings[] = "Konnte Import-Status nicht prüfen: " . $e->getMessage();
        }
        
        echo "✅ Import-Status geprüft\n";
    }
    
    /**
     * Konfigurationsdateien prüfen
     */
    private function checkConfigFiles() {
        echo "⚙️ Konfiguration prüfen...\n";
        
        $config_files = [
            'config.php' => 'config.php'
        ];
        
        foreach ($config_files as $name => $path) {
            if (!file_exists($path)) {
                $this->issues[] = "Konfigurationsdatei nicht gefunden: $path";
            } else {
                $perms = fileperms($path);
                if (($perms & 0x0004) || ($perms & 0x0020)) {
                    $this->warnings[] = "Konfigurationsdatei '$name' für andere lesbar (Sicherheitsrisiko)";
                }
                
                $this->info[] = "Konfigurationsdatei '$name' gefunden";
            }
        }
        
        // Kritische Konfigurationswerte prüfen
        if (!defined('DB_HOST') || empty(DB_HOST)) {
            $this->issues[] = "DB_HOST nicht konfiguriert";
        }
        
        if (!defined('ADMIN_EMAIL') || empty(ADMIN_EMAIL)) {
            $this->warnings[] = "ADMIN_EMAIL nicht konfiguriert";
        }
        
        echo "✅ Konfiguration geprüft\n";
    }
    
    /**
     * Dateiberechtigungen prüfen
     */
    private function checkPermissions() {
        echo "🔐 Berechtigungen prüfen...\n";
        
        $paths = [
            CSV_INPUT_DIR,
            CSV_ARCHIVE_DIR,
            CSV_ERROR_DIR,
            LOG_DIR
        ];
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                if (!is_readable($path)) {
                    $this->issues[] = "Verzeichnis nicht lesbar: $path";
                }
                if (!is_writable($path)) {
                    $this->issues[] = "Verzeichnis nicht beschreibbar: $path";
                }
            }
        }
        
        // Script-Ausführungsberechtigungen
        if (!is_readable(__FILE__)) {
            $this->issues[] = "Health-Check-Script nicht lesbar";
        }
        
        if (file_exists('import_sales.php') && !is_readable('import_sales.php')) {
            $this->issues[] = "Import-Script nicht lesbar";
        }
        
        echo "✅ Berechtigungen geprüft\n";
    }
    
    /**
     * Cron-Jobs prüfen (falls möglich)
     */
    private function checkCronJobs() {
        echo "⏰ Cron-Jobs prüfen...\n";
        
        // Versuche Crontab zu lesen (funktioniert nur mit entsprechenden Berechtigungen)
        $crontab = shell_exec('crontab -l 2>/dev/null');
        
        if ($crontab === null) {
            $this->warnings[] = "Kann Crontab nicht lesen - möglicherweise keine Berechtigung";
        } else {
            if (strpos($crontab, 'import_sales.php') !== false) {
                $this->info[] = "Import-Cron-Job gefunden";
            } else {
                $this->warnings[] = "Kein Import-Cron-Job gefunden";
            }
        }
        
        echo "✅ Cron-Jobs geprüft\n";
    }
    
    /**
     * Bericht generieren
     */
    private function generateReport() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📋 HEALTH CHECK BERICHT\n";
        echo str_repeat("=", 50) . "\n";
        
        $total_checks = count($this->issues) + count($this->warnings) + count($this->info);
        $health_score = 100;
        
        if (!empty($this->issues)) {
            $health_score -= count($this->issues) * 20;
            echo "❌ KRITISCHE PROBLEME (" . count($this->issues) . "):\n";
            foreach ($this->issues as $issue) {
                echo "   - $issue\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            $health_score -= count($this->warnings) * 5;
            echo "⚠️  WARNUNGEN (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "   - $warning\n";
            }
            echo "\n";
        }
        
        if (!empty($this->info)) {
            echo "ℹ️  INFORMATIONEN (" . count($this->info) . "):\n";
            foreach ($this->info as $info) {
                echo "   - $info\n";
            }
            echo "\n";
        }
        
        $health_score = max(0, min(100, $health_score));
        
        echo "🏥 SYSTEM-GESUNDHEIT: $health_score%\n";
        
        if ($health_score >= 90) {
            echo "✅ System läuft optimal\n";
        } elseif ($health_score >= 70) {
            echo "⚠️  System funktioniert mit kleineren Problemen\n";
        } else {
            echo "❌ System benötigt Aufmerksamkeit\n";
        }
        
        echo "\nHealth Check abgeschlossen: " . date('Y-m-d H:i:s') . "\n";
    }
    
    /**
     * Benachrichtigung senden falls nötig
     */
    private function sendNotificationIfNeeded() {
        if (!empty($this->issues) || count($this->warnings) > 3) {
            $this->sendHealthAlert();
        }
    }
    
    /**
     * Health Alert E-Mail senden
     */
    private function sendHealthAlert() {
        $subject = '🏥 SV Freibad - System Health Alert';
        
        $message = "System Health Check Alert\n";
        $message .= "========================\n\n";
        $message .= "Zeit: " . date('Y-m-d H:i:s') . "\n\n";
        
        if (!empty($this->issues)) {
            $message .= "KRITISCHE PROBLEME:\n";
            foreach ($this->issues as $issue) {
                $message .= "❌ $issue\n";
            }
            $message .= "\n";
        }
        
        if (!empty($this->warnings)) {
            $message .= "WARNUNGEN:\n";
            foreach ($this->warnings as $warning) {
                $message .= "⚠️  $warning\n";
            }
            $message .= "\n";
        }
        
        $message .= "Bitte prüfen Sie das System umgehend.\n\n";
        $message .= "Automatisch generiert vom Health Check System";
        
        // E-Mail senden
        $headers = [
            'From: ' . SENDER_EMAIL,
            'X-Priority: 1',
            'X-MSMail-Priority: High',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        mail(ADMIN_EMAIL, $subject, $message, implode("\r\n", $headers));
        
        echo "\n📧 Health Alert E-Mail gesendet an " . ADMIN_EMAIL . "\n";
    }
}

// Health Check ausführen
$healthChecker = new HealthChecker();
$healthChecker->performHealthCheck();

?>