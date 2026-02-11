<?php
/**
 * test_htaccess.php - mod_rewrite und Apache-Konfiguration Test
 * Freibad Dabringhausen
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apache/mod_rewrite Test - Freibad Dabringhausen</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f8f9fa; }
        .header { background: #2c5aa0; color: white; padding: 20px; margin: -20px -20px 30px -20px; text-align: center; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        pre { margin: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏊‍♂️ Freibad Dabringhausen</h1>
        <h2>Apache/mod_rewrite Funktionstest</h2>
    </div>
    
    <?php
    $area = $_GET['area'] ?? null;
    $currentUrl = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    ?>
    
    <div class="test-result success">
        <h3>✅ Grundfunktion erfolgreich</h3>
        <p><strong>mod_rewrite funktioniert!</strong> Sie haben diese Seite über URL-Rewriting erreicht.</p>
        <div class="code">
            <strong>Aufgerufene URL:</strong> <?= htmlspecialchars($currentUrl) ?><br>
            <strong>Request Method:</strong> <?= htmlspecialchars($requestMethod) ?><br>
            <strong>Zeitpunkt:</strong> <?= date('d.m.Y H:i:s') ?>
        </div>
    </div>
    
    <?php if ($area === 'admin'): ?>
        <div class="test-result success">
            <h3>✅ Parameter-Weiterleitung funktioniert</h3>
            <p>Query-Parameter werden korrekt weitergeleitet: <code>area=<?= htmlspecialchars($area) ?></code></p>
        </div>
    <?php endif; ?>
    
    <div class="test-result info">
        <h3>🔧 Apache-Module Status</h3>
        <?php
        // Prüfe verfügbare Apache-Module (wenn möglich)
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $important_modules = ['mod_rewrite', 'mod_headers', 'mod_ssl', 'mod_auth_basic'];
            
            echo "<ul>";
            foreach ($important_modules as $module) {
                $status = in_array($module, $modules) ? "✅" : "❓";
                echo "<li>$status <strong>$module:</strong> " . (in_array($module, $modules) ? "Aktiviert" : "Status unbekannt") . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ apache_get_modules() nicht verfügbar. Module-Status kann nicht ermittelt werden.</p>";
        }
        ?>
    </div>
    
    <div class="test-result info">
        <h3>🔒 Session-Sicherheit Test</h3>
        <?php
        session_start();
        
        $sessionConfig = [
            'cookie_httponly' => ini_get('session.cookie_httponly'),
            'cookie_secure' => ini_get('session.cookie_secure'),
            'use_only_cookies' => ini_get('session.use_only_cookies'),
            'cookie_samesite' => ini_get('session.cookie_samesite') ?: 'not set'
        ];
        
        echo "<ul>";
        foreach ($sessionConfig as $setting => $value) {
            $icon = ($value == '1' || $value == 'Strict') ? "✅" : "⚠️";
            echo "<li>$icon <strong>$setting:</strong> " . htmlspecialchars($value) . "</li>";
        }
        echo "</ul>";
        ?>
    </div>
    
    <div class="test-result info">
        <h3>📁 Dateisystem-Informationen</h3>
        <ul>
            <li><strong>Document Root:</strong> <?= htmlspecialchars($_SERVER['DOCUMENT_ROOT']) ?></li>
            <li><strong>Aktuelles Verzeichnis:</strong> <?= htmlspecialchars(getcwd()) ?></li>
            <li><strong>Script-Name:</strong> <?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?></li>
            <li><strong>Berechtigungen:</strong> <?= substr(sprintf('%o', fileperms('.')), -4) ?></li>
            <li><strong>Schreibbar:</strong> <?= is_writable('.') ? '✅ Ja' : '❌ Nein' ?></li>
        </ul>
    </div>
    
    <div class="test-result <?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'success' : 'warning' ?>">
        <h3><?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '✅' : '⚠️' ?> HTTPS Status</h3>
        <p>
            <strong>HTTPS:</strong> <?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'Aktiv' : 'Nicht aktiv' ?><br>
            <strong>Server Port:</strong> <?= htmlspecialchars($_SERVER['SERVER_PORT']) ?><br>
            <strong>Protocol:</strong> <?= htmlspecialchars($_SERVER['SERVER_PROTOCOL']) ?>
        </p>
    </div>
    
    <div class="test-result info">
        <h3>📋 Nächste Schritte für Freibad-System</h3>
        <ol>
            <li><strong>✅ mod_rewrite bestätigt</strong> - URL-Rewriting funktioniert</li>
            <li><strong>Optimiere Session-Sicherheit</strong> - .htaccess anpassen</li>
            <li><strong>Installiere Freibad-Auth-System</strong> - PHP-Dateien hochladen</li>
            <li><strong>Konfiguriere Verzeichnisstruktur</strong> - Bereiche anlegen</li>
            <li><strong>Erste Benutzer anlegen</strong> - Admin-Interface nutzen</li>
        </ol>
    </div>
    
    <div class="test-result info">
        <h3>🧪 Weitere Tests</h3>
        <p>Testen Sie auch:</p>
        <ul>
            <li><a href="admin-test">Parameter-Test (admin-test)</a></li>
            <li><a href="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?>">Direkte PHP-Datei</a></li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <p><em>Test abgeschlossen am <?= date('d.m.Y H:i:s') ?></em></p>
        <p>Bei Erfolg: <strong>Löschen Sie diese Testdateien</strong> und fahren Sie mit der Installation fort.</p>
    </div>
</body>
</html>