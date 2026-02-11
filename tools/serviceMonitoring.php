<?php

// Konfiguration
$resources = [
    'WebCam' => 'https://iykjlt0jy435sqad.myfritz.net:8088',
    'FritzBox' => 'https://iykjlt0jy435sqad.myfritz.net',
    'Wechselrichter' => 'https://iykjlt0jy435sqad.myfritz.net:8086',
    'MBUS Gateway' => 'http://iykjlt0jy435sqad.myfritz.net:8089'
];

$sendMailAlways = false; // true = immer Mail schicken, false = nur bei Fehler
$mailTo = 'michasalz@gmail.com';
$mailFrom = 'info@freibad-dabringhausen.de';
$logFile = __DIR__ . '/monitoring_log.txt';
$maxLogLines = 1000; // maximale Anzahl an Zeilen im Logfile

// Funktion: Status abrufen
function checkResource($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Nur Header
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error && strpos($error, 'certificate subject name') === false) {
        return ['status' => 'Error', 'detail' => $error];
    }

    return ['status' => ($httpCode >= 200 && $httpCode < 400) ? 'OK' : 'Error', 'detail' => 'HTTP ' . $httpCode];
}

// Monitoring ausführen
$results = [];
$hasError = false;
foreach ($resources as $name => $url) {
    $status = checkResource($url);
    if ($status['status'] === 'Error') {
        $hasError = true;
    }
    $results[$name] = [
        'url' => $url,
        'status' => $status['status'],
        'detail' => $status['detail']
    ];
}

// Ausgabe vorbereiten
ob_start();
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Ressource</th><th>URL</th><th>Status</th><th>Detail</th></tr>";
foreach ($results as $name => $data) {
    $color = ($data['status'] === 'OK') ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$name}</td>";
    echo "<td><a href='{$data['url']}' target='_blank'>{$data['url']}</a></td>";
    echo "<td style='color: {$color};'>{$data['status']}</td>";
    echo "<td>{$data['detail']}</td>";
    echo "</tr>";
}
echo "</table>";
$htmlOutput = ob_get_clean();

// Logs schreiben
$logEntry = date('Y-m-d H:i:s') . "\n";
foreach ($results as $name => $data) {
    $logEntry .= "{$name}: {$data['status']} ({$data['detail']})\n";
}
$logEntry .= "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Logfile auf max. $maxLogLines begrenzen
$logContent = file($logFile);
if (count($logContent) > $maxLogLines) {
    $logContent = array_slice($logContent, -$maxLogLines);
    file_put_contents($logFile, implode('', $logContent));
}

// Mail verschicken, wenn nötig
if ($sendMailAlways || $hasError) {
    $timestamp = date('Y-m-d H:i:s');
    $subject = ($hasError ? '#SVFD | Monitoring Fehler erkannt' : 'SVFD | Monitoring Status OK') . " - {$timestamp}";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: {$mailFrom}" . "\r\n";

    mail($mailTo, $subject, $htmlOutput, $headers);
}

// Ausgabe auf der Seite
echo $htmlOutput;

// Exit Code setzen
if ($hasError) {
    exit(1);
} else {
    exit(0);
}
/*
NEXT: Repeater, Frischwasserzähler, aufnehmen
*/
?>