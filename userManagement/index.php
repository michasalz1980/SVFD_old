<?php
/**
 * index.php - Haupt-Handler für Freibad Dabringhausen
 * Zentrale Weiterleitung und Authentifizierung
 */

require_once 'auth_freibad.php';

// Freibad-Auth-System initialisieren
$auth = new FreibadDabringhausenAuth();

// Request-Informationen sammeln
$area = $_GET['area'] ?? '';
$path = $_GET['path'] ?? '';
$fullPath = $area ? ($path ? "$area/$path" : $area) : $path;

// Content-Type setzen
header('Content-Type: text/html; charset=UTF-8');

// Spezielle Behandlung für API-Calls
if (strpos($fullPath, 'api/') === 0) {
    header('Content-Type: application/json');
    handleApiRequest($fullPath, $auth);
    exit;
}

// Öffentliche Bereiche ohne Authentifizierung
if (empty($fullPath) || $fullPath === 'public' || strpos($fullPath, 'public/') === 0) {
    handlePublicArea($fullPath);
    exit;
}

// Authentifizierung prüfen
if (!$auth->isLoggedIn()) {
    // Nicht angemeldet - zur Login-Seite weiterleiten
    $redirectUrl = $_SERVER['REQUEST_URI'];
    header('Location: /userManagement/login.php?redirect=' . urlencode($redirectUrl));
    exit;
}

// Berechtigung prüfen
if (!$auth->checkAccess($fullPath)) {
    // Keine Berechtigung - 403 Seite anzeigen
    http_response_code(403);
    showErrorPage(403, 'Zugriff verweigert', 'Sie haben keine Berechtigung für diesen Bereich.');
    exit;
}

// Erfolgreiche Authentifizierung und Berechtigung - Inhalt laden
handleProtectedArea($fullPath, $auth);

/**
 * Öffentliche Bereiche behandeln
 */
function handlePublicArea($path) {
    $publicPath = empty($path) ? 'public/index.html' : $path;
    
    // Entferne 'public/' Präfix falls vorhanden
    if (strpos($publicPath, 'public/') === 0) {
        $publicPath = substr($publicPath, 7);
    }
    
    $filePath = "public/$publicPath";
    
    // Standard-Datei wenn Verzeichnis aufgerufen wird
    if (is_dir($filePath)) {
        $indexFiles = ['index.html', 'index.php'];
        foreach ($indexFiles as $indexFile) {
            if (file_exists("$filePath/$indexFile")) {
                $filePath = "$filePath/$indexFile";
                break;
            }
        }
    }
    
    // Datei existiert?
    if (!file_exists($filePath)) {
        http_response_code(404);
        showErrorPage(404, 'Seite nicht gefunden', 'Die angeforderte Seite wurde nicht gefunden.');
        return;
    }
    
    // Korrekten MIME-Type setzen und Datei ausliefern
    $mimeType = getMimeType($filePath);
    header("Content-Type: $mimeType");
    
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        include $filePath;
    } else {
        readfile($filePath);
    }
}

/**
 * Geschützte Bereiche behandeln
 */
function handleProtectedArea($path, $auth) {
    $user = $auth->getCurrentUser();
    
    // Pfad in Bereich und Datei aufteilen
    $pathParts = explode('/', $path, 2);
    $area = $pathParts[0];
    $file = isset($pathParts[1]) ? $pathParts[1] : '';
    
    // Bereich-spezifische Behandlung
    switch ($area) {
        case 'mitglieder':
            handleMembersArea($file, $user);
            break;
            
        case 'schwimmkurse':
            handleSwimmingArea($file, $user);
            break;
            
        case 'rettungsdienst':
            handleLifeguardArea($file, $user);
            break;
            
        case 'technik':
        case 'wartung':
            handleTechnicalArea($area, $file, $user);
            break;
            
        case 'finanzen':
            handleFinanceArea($file, $user);
            break;
            
        case 'vorstand':
            handleBoardArea($file, $user);
            break;
            
        case 'admin':
            handleAdminArea($file, $user);
            break;
            
        default:
            handleGenericArea($area, $file, $user);
    }
}

/**
 * Mitgliederbereich
 */
function handleMembersArea($file, $user) {
    $basePath = "protected/mitglieder/";
    
    if (empty($file)) {
        // Mitglieder-Dashboard
        showMembersDashboard($user);
    } else {
        // Spezifische Datei
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Schwimmkurs-Bereich
 */
function handleSwimmingArea($file, $user) {
    $basePath = "protected/schwimmkurse/";
    
    if (empty($file)) {
        showSwimmingDashboard($user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Rettungsdienst-Bereich
 */
function handleLifeguardArea($file, $user) {
    $basePath = "protected/rettungsdienst/";
    
    if (empty($file)) {
        showLifeguardDashboard($user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Technik-Bereiche
 */
function handleTechnicalArea($area, $file, $user) {
    $basePath = "protected/$area/";
    
    if (empty($file)) {
        showTechnicalDashboard($area, $user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Finanz-Bereich
 */
function handleFinanceArea($file, $user) {
    $basePath = "protected/finanzen/";
    
    if (empty($file)) {
        showFinanceDashboard($user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Vorstandsbereich
 */
function handleBoardArea($file, $user) {
    $basePath = "protected/vorstand/";
    
    if (empty($file)) {
        showBoardDashboard($user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Admin-Bereich
 */
function handleAdminArea($file, $user) {
    if (empty($file)) {
        // Weiterleitung zur Admin-Seite
        header('Location: /admin.php');
        exit;
    } else {
        serveProtectedFile("protected/admin/$file", $user);
    }
}

/**
 * Generische Bereichsbehandlung
 */
function handleGenericArea($area, $file, $user) {
    $basePath = "protected/$area/";
    
    if (empty($file)) {
        showGenericDashboard($area, $user);
    } else {
        serveProtectedFile($basePath . $file, $user);
    }
}

/**
 * Geschützte Datei ausliefern
 */
function serveProtectedFile($filePath, $user) {
    if (!file_exists($filePath)) {
        http_response_code(404);
        showErrorPage(404, 'Datei nicht gefunden', 'Die angeforderte Datei wurde nicht gefunden.');
        return;
    }
    
    // MIME-Type bestimmen
    $mimeType = getMimeType($filePath);
    header("Content-Type: $mimeType");
    
    // Sicherheits-Header für Downloads
    if (strpos($mimeType, 'application/') === 0) {
        $filename = basename($filePath);
        header("Content-Disposition: attachment; filename=\"$filename\"");
    }
    
    // PHP-Dateien einbinden, andere ausliefern
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        // Benutzer-Kontext für PHP-Dateien verfügbar machen
        $currentUser = $user;
        include $filePath;
    } else {
        readfile($filePath);
    }
}

/**
 * Dashboard-Funktionen
 */
function showMembersDashboard($user) {
    include 'templates/members_dashboard.php';
}

function showSwimmingDashboard($user) {
    include 'templates/swimming_dashboard.php';
}

function showLifeguardDashboard($user) {
    include 'templates/lifeguard_dashboard.php';
}

function showTechnicalDashboard($area, $user) {
    include 'templates/technical_dashboard.php';
}

function showFinanceDashboard($user) {
    include 'templates/finance_dashboard.php';
}

function showBoardDashboard($user) {
    include 'templates/board_dashboard.php';
}

function showGenericDashboard($area, $user) {
    include 'templates/generic_dashboard.php';
}

/**
 * Fehlerseite anzeigen
 */
function showErrorPage($code, $title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?> - Freibad Dabringhausen</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-code { font-size: 4rem; color: #dc3545; margin: 0; }
            .error-title { font-size: 1.5rem; color: #333; margin: 20px 0; }
            .error-message { color: #666; margin-bottom: 30px; }
            .back-link { display: inline-block; background: #2c5aa0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            .back-link:hover { background: #1e3a8a; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-code"><?= $code ?></div>
            <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
            <a href="/dashboard" class="back-link">Zurück zum Dashboard</a>
        </div>
    </body>
    </html>
    <?php
}

/**
 * API-Anfragen behandeln
 */
function handleApiRequest($path, $auth) {
    // API erfordert gültige Session
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }
    
    $endpoint = substr($path, 4); // 'api/' entfernen
    $user = $auth->getCurrentUser();
    
    // Einfache API-Endpoints
    switch ($endpoint) {
        case 'user':
            echo json_encode($user);
            break;
            
        case 'areas':
            echo json_encode($auth->getAccessibleAreas());
            break;
            
        case 'season':
            echo json_encode($auth->getSeasonInfo());
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

/**
 * MIME-Type bestimmen
 */
function getMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'zip' => 'application/zip',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?>
