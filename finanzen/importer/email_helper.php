<?php
/**
 * E-Mail Helper für erweiterte SMTP-Funktionen
 * SV Freibad Dabringhausen e.V.
 */

class EmailHelper {
    
    /**
     * E-Mail über SMTP senden (erweiterte Version)
     */
    public static function sendSMTPMail($to, $subject, $message, $isHTML = false) {
        // Für erweiterte SMTP-Funktionen können hier Libraries wie PHPMailer eingebunden werden
        // Fallback auf die einfache mail() Funktion
        
        $headers = [
            'From: ' . SENDER_EMAIL,
            'Reply-To: ' . SENDER_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if ($isHTML) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * HTML-E-Mail-Template für Reports
     */
    public static function createHTMLReport($stats, $dry_run = false) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Import-Bericht SV Freibad</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #003366; color: white; padding: 15px; border-radius: 5px; }
        .stats { background-color: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background-color: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; }
        .success { background-color: #e8f5e8; border-left: 4px solid #4caf50; padding: 10px; margin: 10px 0; }
        .dry-run { background-color: #fff3e0; border: 2px solid #ff9800; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏊‍♂️ SV Freibad Dabringhausen e.V.</h1>
        <h2>Verkaufserlös Import-Bericht</h2>
    </div>';
    
        if ($dry_run) {
            $html .= '<div class="dry-run">
                <h3>⚠️ DRY RUN MODUS</h3>
                <p>Dieser Bericht zeigt nur eine Simulation. Es wurden keine Daten in die Datenbank importiert.</p>
            </div>';
        }
        
        $success_rate = round(($stats['imported_rows'] / max($stats['total_rows'], 1)) * 100, 2);
        $status_class = $success_rate >= 95 ? 'success' : ($success_rate >= 80 ? 'stats' : 'error');
        
        $html .= '<div class="' . $status_class . '">
            <h3>📊 Import-Statistiken</h3>
            <table>
                <tr><th>Verarbeitete Dateien</th><td>' . $stats['processed_files'] . '</td></tr>
                <tr><th>Gesamtzeilen</th><td>' . number_format($stats['total_rows']) . '</td></tr>
                <tr><th>Importierte Zeilen</th><td>' . number_format($stats['imported_rows']) . '</td></tr>
                <tr><th>Fehlerhafte Zeilen</th><td>' . number_format($stats['error_rows']) . '</td></tr>
                <tr><th>Übersprungene Duplikate</th><td>' . number_format($stats['skipped_rows']) . '</td></tr>
                <tr><th>Erfolgsrate</th><td>' . $success_rate . '%</td></tr>
            </table>
        </div>';
        
        if (!empty($stats['errors'])) {
            $html .= '<div class="error">
                <h3>❌ Aufgetretene Fehler</h3>
                <ul>';
            foreach ($stats['errors'] as $error) {
                $html .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul></div>';
        }
        
        $html .= '<div class="stats">
            <p><strong>Zeitpunkt:</strong> ' . date('d.m.Y H:i:s') . '</p>
            <p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] ?? 'Unbekannt' . '</p>
        </div>
        
        <hr>
        <p style="font-size: 12px; color: #666;">
            Automatisch generiert vom Import-System des SV Freibad Dabringhausen e.V.
        </p>
    </body>
    </html>';
        
        return $html;
    }
}
?>