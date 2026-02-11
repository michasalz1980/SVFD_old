<?php
/**
 * Optimierte Freibad Dabringhausen Authentifizierung
 * auth_freibad.php - Speziell angepasst für Ihre Server-Umgebung
 */

class FreibadDabringhausenAuth {
    private $configPath = '/var/www/vhosts/personal.freibad-dabringhausen.de/httpdocs/config/';
    private $usersFile;
    private $permissionsFile;
    private $logFile;
    
    private $freibadRoles = [
        'gast' => 0,
        'mitglied' => 1,
        'schwimmkurs' => 2,          // Neue Rolle für Schwimmkursleiter
        'rettungsschwimmer' => 2,    // Neue Rolle für Rettungsschwimmer
        'redaktion' => 3,
        'technik' => 3,
        'kassenwart' => 4,
        'vorstand' => 5,
        'admin' => 6
    ];
    
    // Freibad-spezifische Bereiche
    private $freibadAreas = [
        'public' => 'Öffentlicher Bereich',
        'mitglieder' => 'Mitgliederbereich',
        'schwimmkurse' => 'Schwimmkurse & Training',
        'rettungsdienst' => 'Rettungsdienst',
        'veranstaltungen' => 'Veranstaltungsplanung',
        'technik' => 'Technische Anlagen',
        'wartung' => 'Wartung & Instandhaltung',
        'finanzen' => 'Finanzverwaltung',
        'vorstand' => 'Vorstandsbereich',
        'admin' => 'Systemverwaltung'
    ];
    
    public function __construct() {
        // Session mit optimalen Sicherheitseinstellungen für Ihren Server
        $this->initializeSecureSession();
        
        // Pfade setzen
        $this->usersFile = $this->configPath . 'users.json';
        $this->permissionsFile = $this->configPath . 'permissions.json';
        $this->logFile = $this->configPath . 'logs/freibad.log';
        
        // Konfiguration erstellen falls nicht vorhanden
        $this->setupFreibadConfig();
    }
    
    private function initializeSecureSession() {
        // Optimiert für Ihre Server-Konfiguration (PHP 8.2, Apache, HTTPS)
        if (session_status() === PHP_SESSION_NONE) {
            // Session-Konfiguration für HTTPS-Environment
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', '28800'); // 8 Stunden
            
            // Session-Name für Freibad
            session_name('FREIBAD_DABRINGHAUSEN_SESSION');
            session_start();
        }
    }
    
    private function setupFreibadConfig() {
        // Config-Verzeichnis erstellen
        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0755, true);
            $this->logActivity('CONFIG_DIR_CREATED', 'system');
        }
        
        // Logs-Verzeichnis
        if (!is_dir($this->configPath . 'logs')) {
            mkdir($this->configPath . 'logs', 0755, true);
        }
        
        // Schutz für config-Verzeichnis
        $htaccessPath = $this->configPath . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
        
        // Standard-Benutzer für Freibad erstellen
        if (!file_exists($this->usersFile)) {
            $this->createDefaultFreibadUsers();
        }
        
        // Standard-Berechtigungen erstellen
        if (!file_exists($this->permissionsFile)) {
            $this->createDefaultPermissions();
        }
    }
    
    private function createDefaultFreibadUsers() {
        $defaultUsers = [
            'admin' => [
                'password' => password_hash('Freibad2024!Admin', PASSWORD_DEFAULT),
                'role' => 'admin',
                'name' => 'System Administrator',
                'email' => 'admin@freibad-dabringhausen.de',
                'created' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'active' => true,
                'department' => 'Systemverwaltung'
            ],
            'vorstand1' => [
                'password' => password_hash('Vorstand2024!', PASSWORD_DEFAULT),
                'role' => 'vorstand',
                'name' => '1. Vorsitzende/r',
                'email' => 'vorstand@freibad-dabringhausen.de',
                'created' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'active' => true,
                'department' => 'Vereinsführung'
            ],
            'kassenwart' => [
                'password' => password_hash('Kasse2024!', PASSWORD_DEFAULT),
                'role' => 'kassenwart',
                'name' => 'Kassenwart/in',
                'email' => 'kasse@freibad-dabringhausen.de',
                'created' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'active' => true,
                'department' => 'Finanzen'
            ],
            'technik' => [
                'password' => password_hash('Technik2024!', PASSWORD_DEFAULT),
                'role' => 'technik',
                'name' => 'Technischer Leiter',
                'email' => 'technik@freibad-dabringhausen.de',
                'created' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'active' => true,
                'department' => 'Technik & Wartung'
            ]
        ];
        
        file_put_contents($this->usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
        $this->logActivity('DEFAULT_USERS_CREATED', 'system');
    }
    
    private function createDefaultPermissions() {
        $defaultPermissions = [
            // Öffentliche Bereiche
            'public' => ['gast', 'mitglied', 'schwimmkurs', 'rettungsschwimmer', 'redaktion', 'technik', 'kassenwart', 'vorstand', 'admin'],
            
            // Mitgliederbereiche
            'mitglieder' => ['mitglied', 'schwimmkurs', 'rettungsschwimmer', 'redaktion', 'technik', 'kassenwart', 'vorstand', 'admin'],
            
            // Spezielle Freibad-Bereiche
            'schwimmkurse' => ['schwimmkurs', 'rettungsschwimmer', 'vorstand', 'admin'],
            'rettungsdienst' => ['rettungsschwimmer', 'vorstand', 'admin'],
            
            // Betriebsbereiche
            'veranstaltungen' => ['redaktion', 'vorstand', 'admin'],
            'technik' => ['technik', 'vorstand', 'admin'],
            'wartung' => ['technik', 'admin'],
            
            // Verwaltung
            'finanzen' => ['kassenwart', 'vorstand', 'admin'],
            'vorstand' => ['vorstand', 'admin'],
            'admin' => ['admin']
        ];
        
        file_put_contents($this->permissionsFile, json_encode($defaultPermissions, JSON_PRETTY_PRINT));
        $this->logActivity('DEFAULT_PERMISSIONS_CREATED', 'system');
    }
    
    public function authenticate($username, $password) {
        $users = $this->loadUsers();
        
        if (!isset($users[$username]) || !$users[$username]['active']) {
            $this->logActivity('LOGIN_FAILED', $username, 'User not found or inactive');
            return false;
        }
        
        $user = $users[$username];
        
        if (password_verify($password, $user['password'])) {
            // Erfolgreiche Anmeldung
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['department'] = $user['department'] ?? '';
            $_SESSION['login_time'] = time();
            $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];
            
            // Last Login aktualisieren
            $users[$username]['last_login'] = date('Y-m-d H:i:s');
            $this->saveUsers($users);
            
            $this->logActivity('LOGIN_SUCCESS', $username);
            
            // Session-ID aus Sicherheitsgründen erneuern
            session_regenerate_id(true);
            
            return true;
        }
        
        $this->logActivity('LOGIN_FAILED', $username, 'Wrong password');
        return false;
    }
    
    public function checkAccess($path) {
        // Öffentliche Bereiche
        if (strpos($path, 'public/') === 0 || $path === 'public' || $path === '') {
            return true;
        }
        
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'];
        $permissions = $this->loadPermissions();
        
        // Finde den entsprechenden Bereich
        foreach ($permissions as $area => $allowedRoles) {
            if (strpos($path, $area . '/') === 0 || $path === $area) {
                return in_array($userRole, $allowedRoles);
            }
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return false;
        }
        
        // Session-Timeout prüfen (8 Stunden)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function logout() {
        if (isset($_SESSION['username'])) {
            $this->logActivity('LOGOUT', $_SESSION['username']);
        }
        
        session_destroy();
        session_start();
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name'],
            'department' => $_SESSION['department'] ?? '',
            'login_time' => $_SESSION['login_time'],
            'role_level' => $this->freibadRoles[$_SESSION['role']] ?? 0
        ];
    }
    
    public function getAccessibleAreas() {
        if (!$this->isLoggedIn()) {
            return ['public'];
        }
        
        $userRole = $_SESSION['role'];
        $permissions = $this->loadPermissions();
        $accessibleAreas = [];
        
        foreach ($permissions as $area => $allowedRoles) {
            if (in_array($userRole, $allowedRoles)) {
                $accessibleAreas[$area] = $this->freibadAreas[$area] ?? ucfirst($area);
            }
        }
        
        return $accessibleAreas;
    }
    
    public function addUser($username, $password, $role, $name, $email, $department = '') {
        if (!$this->isLoggedIn() || $_SESSION['role'] !== 'admin') {
            return false;
        }
        
        $users = $this->loadUsers();
        
        if (isset($users[$username])) {
            return false;
        }
        
        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'name' => $name,
            'email' => $email,
            'department' => $department,
            'created' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'],
            'last_login' => null,
            'active' => true
        ];
        
        $success = $this->saveUsers($users);
        if ($success) {
            $this->logActivity('USER_CREATED', $_SESSION['username'], "Created user: $username ($role)");
        }
        
        return $success;
    }
    
    private function loadUsers() {
        if (!file_exists($this->usersFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->usersFile), true) ?: [];
    }
    
    private function saveUsers($users) {
        return file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false;
    }
    
    private function loadPermissions() {
        if (!file_exists($this->permissionsFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->permissionsFile), true) ?: [];
    }
    
    private function logActivity($action, $username = null, $details = '') {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $username = $username ?? ($_SESSION['username'] ?? 'system');
        
        $logEntry = [
            'timestamp' => $timestamp,
            'action' => $action,
            'username' => $username,
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 200),
            'details' => $details
        ];
        
        file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public function getFreibadRoles() {
        return array_keys($this->freibadRoles);
    }
    
    public function getFreibadAreas() {
        return $this->freibadAreas;
    }
    
    // Saisonale Funktionen für Freibad
    public function isSeason() {
        $month = (int)date('n');
        return $month >= 4 && $month <= 10; // April bis Oktober
    }
    
    public function getSeasonInfo() {
        return [
            'is_season' => $this->isSeason(),
            'season_start' => date('Y') . '-04-01',
            'season_end' => date('Y') . '-10-31',
            'current_month' => date('F'),
            'days_until_season' => $this->isSeason() ? 0 : $this->getDaysUntilSeason()
        ];
    }
    
    private function getDaysUntilSeason() {
        $now = new DateTime();
        $seasonStart = new DateTime(date('Y') . '-04-01');
        
        if ($now > $seasonStart) {
            $seasonStart = new DateTime((date('Y') + 1) . '-04-01');
        }
        
        return $now->diff($seasonStart)->days;
    }
}

// Globale Instanz für einfache Verwendung
$freibadAuth = new FreibadDabringhausenAuth();
?>