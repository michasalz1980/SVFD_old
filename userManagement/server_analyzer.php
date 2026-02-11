<?php
/**
 * Server-Umgebung Analyzer für Freibad Dabringhausen
 * Analysiert verfügbare Technologien und Konfigurationsmöglichkeiten
 */

echo "<h1>Server-Umgebung Analyse</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .section{background:#f5f5f5;padding:15px;margin:10px 0;border-radius:5px;} .ok{color:green;} .warning{color:orange;} .error{color:red;}</style>";

// PHP-Informationen
echo "<div class='section'>";
echo "<h2>PHP-Umgebung</h2>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Server API:</strong> " . php_sapi_name() . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "</div>";

// Webserver-Typ erkennen
echo "<div class='section'>";
echo "<h2>Webserver-Typ</h2>";
$server = $_SERVER['SERVER_SOFTWARE'];
if (strpos($server, 'Apache') !== false) {
    echo "<span class='ok'>✓ Apache erkannt</span><br>";
    echo "<strong>mod_rewrite:</strong> " . (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()) ? "<span class='ok'>Verfügbar</span>" : "<span class='warning'>Unbekannt</span>") . "<br>";
} elseif (strpos($server, 'nginx') !== false) {
    echo "<span class='ok'>✓ NGINX erkannt</span><br>";
} else {
    echo "<span class='warning'>⚠ Webserver: " . $server . "</span><br>";
}
echo "</div>";

// .htaccess Test
echo "<div class='section'>";
echo "<h2>.htaccess Support</h2>";
if (file_exists('.htaccess')) {
    echo "<span class='ok'>✓ .htaccess Datei vorhanden</span><br>";
} else {
    echo "<span class='warning'>⚠ Keine .htaccess Datei gefunden</span><br>";
}
echo "</div>";

// PHP-Erweiterungen prüfen
echo "<div class='section'>";
echo "<h2>Wichtige PHP-Erweiterungen</h2>";
$extensions = ['openssl', 'session', 'json', 'hash', 'fileinfo', 'curl', 'zip'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "<span class='ok'>✓</span>" : "<span class='error'>✗</span>";
    echo "<strong>$ext:</strong> $status<br>";
}
echo "</div>";

// Dateisystem-Berechtigungen
echo "<div class='section'>";
echo "<h2>Dateisystem-Berechtigungen</h2>";
$testDir = 'test_permissions';
if (!is_dir($testDir)) {
    $canCreate = mkdir($testDir, 0755);
    echo "<strong>Verzeichnis erstellen:</strong> " . ($canCreate ? "<span class='ok'>✓ Möglich</span>" : "<span class='error'>✗ Nicht möglich</span>") . "<br>";
    if ($canCreate) rmdir($testDir);
}

$testFile = 'test_write.txt';
$canWrite = file_put_contents($testFile, 'test') !== false;
echo "<strong>Datei schreiben:</strong> " . ($canWrite ? "<span class='ok'>✓ Möglich</span>" : "<span class='error'>✗ Nicht möglich</span>") . "<br>";
if ($canWrite) unlink($testFile);

// Aktuelle Berechtigungen
echo "<strong>Aktuelle Verzeichnis-Berechtigungen:</strong> " . substr(sprintf('%o', fileperms('.')), -4) . "<br>";
echo "</div>";

// Session-Unterstützung
echo "<div class='section'>";
echo "<h2>Session-Management</h2>";
session_start();
echo "<strong>Session-ID:</strong> " . session_id() . "<br>";
echo "<strong>Session-Speicherpfad:</strong> " . session_save_path() . "<br>";
echo "<strong>Session-Cookie-Parameter:</strong><br>";
$params = session_get_cookie_params();
foreach ($params as $key => $value) {
    echo "&nbsp;&nbsp;<strong>$key:</strong> " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "<br>";
}
echo "</div>";

// Sicherheitsfeatures
echo "<div class='section'>";
echo "<h2>Sicherheitsfeatures</h2>";
echo "<strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "<span class='ok'>✓ Aktiv</span>" : "<span class='warning'>⚠ Nicht aktiv</span>") . "<br>";
echo "<strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? "<span class='warning'>⚠ Aktiviert</span>" : "<span class='ok'>✓ Deaktiviert</span>") . "<br>";
echo "<strong>register_globals:</strong> " . (ini_get('register_globals') ? "<span class='error'>✗ Aktiviert (unsicher)</span>" : "<span class='ok'>✓ Deaktiviert</span>") . "<br>";
echo "</div>";

// Empfohlene nächste Schritte
echo "<div class='section'>";
echo "<h2>Empfohlene nächste Schritte</h2>";
echo "<ol>";
echo "<li>Speichern Sie diese Ausgabe für die weitere Planung</li>";
echo "<li>Prüfen Sie, ob .htaccess-Dateien funktionieren (Test unten)</li>";
echo "<li>Entscheiden Sie sich für eine Authentifizierungsmethode basierend auf den verfügbaren Features</li>";
echo "</ol>";
echo "</div>";

// .htaccess Test-Code
echo "<div class='section'>";
echo "<h2>.htaccess Funktionstest</h2>";
echo "<p>Erstellen Sie eine Testdatei <code>test_htaccess.php</code> mit folgendem Inhalt:</p>";
echo "<pre style='background:#eee;padding:10px;'>";
echo htmlspecialchars('<?php echo "htaccess funktioniert!"; ?>');
echo "</pre>";
echo "<p>Und eine <code>.htaccess</code> im gleichen Verzeichnis:</p>";
echo "<pre style='background:#eee;padding:10px;'>";
echo "RewriteEngine On\n";
echo "RewriteRule ^test$ test_htaccess.php [L]\n";
echo "</pre>";
echo "<p>Testen Sie dann den Aufruf: <code>ihr-domain.de/test</code></p>";
echo "</div>";

?>