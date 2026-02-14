<?php
/**
 * login.php - Anmeldung für Freibad Dabringhausen
 */
require_once 'auth_freibad.php';

$auth = new FreibadDabringhausenAuth();

function sanitizeRedirectTarget(string $target): string {
    if ($target === '') {
        return '/dashboard';
    }
    if (preg_match('#^https?://#i', $target)) {
        return '/dashboard';
    }
    if ($target[0] !== '/') {
        return '/dashboard';
    }
    return $target;
}

// Bereits angemeldet? Weiterleitung
if ($auth->isLoggedIn()) {
    $redirect = sanitizeRedirectTarget((string) ($_GET['redirect'] ?? '/dashboard'));
    header('Location: ' . $redirect);
    exit;
}

// Logout-Behandlung
if (isset($_GET['logout'])) {
    $auth->logout();
    $logoutMessage = 'Sie wurden erfolgreich abgemeldet.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = sanitizeRedirectTarget((string) ($_GET['redirect'] ?? '/dashboard'));
    
    if (empty($username) || empty($password)) {
        header('Location: /userManagement/login_error.php?reason=empty&redirect=' . urlencode($redirect));
        exit;
    } else {
        if ($auth->authenticate($username, $password)) {
            header('Location: ' . $redirect);
            exit;
        } else {
            header('Location: /userManagement/login_error.php?reason=invalid&redirect=' . urlencode($redirect));
            exit;
        }
    }
}

// Saisoninformationen
$seasonInfo = $auth->getSeasonInfo();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung - Freibad Dabringhausen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #4facfe, #00f2fe, #4facfe);
            border-radius: 22px;
            z-index: -1;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .logo h1 {
            color: #2c5aa0;
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .logo .subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .season-info {
            background: <?= $seasonInfo['is_season'] ? '#e8f5e8' : '#fff3cd' ?>;
            color: <?= $seasonInfo['is_season'] ? '#2e7d32' : '#856404' ?>;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border: 1px solid <?= $seasonInfo['is_season'] ? '#c8e6c9' : '#ffeaa7' ?>;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }
        
        .form-group .input-icon {
            position: absolute;
            right: 1rem;
            top: 2.2rem;
            color: #999;
            font-size: 1.2rem;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #fcc;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #c8e6c9;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .test-accounts {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        
        .test-accounts h4 {
            color: #2c5aa0;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .test-account {
            background: white;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            border-radius: 8px;
            border: 1px solid #e1e5e9;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .test-account:hover {
            background: #f0f8ff;
            border-color: #4facfe;
        }
        
        .test-account strong {
            color: #2c5aa0;
        }
        
        .test-account code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .back-to-public {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }
        
        .back-to-public a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-to-public a:hover {
            color: #4facfe;
            text-decoration: underline;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #999;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                margin: 10px;
            }
            
            .logo h1 {
                font-size: 1.6rem;
            }
            
            .logo-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="logo-icon">🏊‍♂️</span>
            <h1>Freibad Dabringhausen</h1>
            <p class="subtitle">Mitglieder- und Verwaltungsbereich</p>
        </div>
        
        <div class="season-info">
            <?php if ($seasonInfo['is_season']): ?>
                🌊 <strong>Badesaison 2024</strong> - Das Freibad ist geöffnet!
            <?php else: ?>
                ❄️ <strong>Winterpause</strong> - Noch <?= $seasonInfo['days_until_season'] ?> Tage bis zur neuen Saison
            <?php endif; ?>
        </div>
        
        <?php if (isset($logoutMessage)): ?>
            <div class="success"><?= htmlspecialchars($logoutMessage) ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="on">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       required 
                       autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Ihr Benutzername">
                <span class="input-icon">👤</span>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="Ihr Passwort">
                <span class="input-icon">🔒</span>
            </div>
            
            <button type="submit" class="btn">Anmelden</button>
        </form>
        
        <div class="test-accounts">
            <h4>🧪 Test-Zugänge (nur für Entwicklung):</h4>
            
            <div class="test-account" onclick="fillLogin('admin', 'Freibad2024!Admin')">
                <strong>Administrator:</strong><br>
                Benutzer: <code>admin</code> | Passwort: <code>Freibad2024!Admin</code>
            </div>
            
            <div class="test-account" onclick="fillLogin('vorstand1', 'Vorstand2024!')">
                <strong>Vorstand:</strong><br>
                Benutzer: <code>vorstand1</code> | Passwort: <code>Vorstand2024!</code>
            </div>
            
            <div class="test-account" onclick="fillLogin('kassenwart', 'Kasse2024!')">
                <strong>Kassenwart:</strong><br>
                Benutzer: <code>kassenwart</code> | Passwort: <code>Kasse2024!</code>
            </div>
            
            <div class="test-account" onclick="fillLogin('technik', 'Technik2024!')">
                <strong>Technik:</strong><br>
                Benutzer: <code>technik</code> | Passwort: <code>Technik2024!</code>
            </div>
        </div>
        
        <div class="back-to-public">
            <a href="/public/">⬅ Zurück zur öffentlichen Website</a>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> Freibad Dabringhausen | Sicher & Verschlüsselt</p>
        </div>
    </div>
    
    <script>
        function fillLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Visual feedback
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            usernameField.style.background = '#e8f5e8';
            passwordField.style.background = '#e8f5e8';
            
            setTimeout(() => {
                usernameField.style.background = '';
                passwordField.style.background = '';
            }, 1000);
        }
        
        // Auto-focus auf Username-Feld
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Enter-Taste im Username-Feld wechselt zu Passwort
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>
