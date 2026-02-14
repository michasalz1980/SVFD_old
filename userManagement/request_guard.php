<?php
/**
 * request_guard.php
 * Globaler Login-Guard via auto_prepend_file (.user.ini).
 * Ziel: #8 (einfacher Vollzugriff nach Login).
 */

if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    return;
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}

$publicExactPaths = [
    '/userManagement/login.php',
    '/userManagement/login_error.php',
    '/userManagement/logout.php',
];

$publicPrefixes = [
    // Technische Endpunkte, die für Betrieb/Cron ohne Browser-Login erreichbar bleiben.
    '/jobs/',
    '/jobs/python/',
    '/tools/serviceMonitoring.php',
    '/webcam/ftp.php',
    '/finanzen/importer/',
];

if (in_array($path, $publicExactPaths, true)) {
    return;
}
foreach ($publicPrefixes as $prefix) {
    if (strpos($path, $prefix) === 0) {
        return;
    }
}

require_once __DIR__ . '/auth_freibad.php';
$auth = new FreibadDabringhausenAuth();
if ($auth->isLoggedIn()) {
    return;
}

header('Location: /userManagement/login.php?redirect=' . urlencode($requestUri));
exit;
