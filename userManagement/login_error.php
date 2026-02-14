<?php
/**
 * login_error.php - Fehlerseite für fehlgeschlagene Logins
 */

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

$reason = (string) ($_GET['reason'] ?? 'invalid');
$redirect = sanitizeRedirectTarget((string) ($_GET['redirect'] ?? '/dashboard'));

$message = 'Die Anmeldung ist fehlgeschlagen.';
if ($reason === 'empty') {
    $message = 'Bitte Benutzername und Passwort eingeben.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login fehlgeschlagen - Freibad Dabringhausen</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .card {
            max-width: 520px;
            width: 100%;
            margin: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 1.5rem;
            color: #b00020;
        }
        p {
            margin: 0 0 18px;
            color: #333;
            line-height: 1.5;
        }
        a.btn {
            display: inline-block;
            text-decoration: none;
            background: #005ea8;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            font-weight: 600;
        }
        small {
            color: #666;
            display: block;
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Login fehlgeschlagen</h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
        <a class="btn" href="/userManagement/login.php?redirect=<?= urlencode($redirect) ?>">Zurück zum Login</a>
        <small>Aus Sicherheitsgründen werden keine Details zum Fehler offengelegt.</small>
    </div>
</body>
</html>
