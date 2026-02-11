<?php
function apiRequest($service, $action, $data, $config, $dryRun = false) {
    $url = $config['api']['base_url'] . "$service.$action";
    $auth = base64_encode($config['api']['user'] . ':' . $config['api']['password']);

    if ($dryRun) {
        logAction("DRY-RUN: Would POST to $url with payload: " . json_encode($data));
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth
        ]
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        logAction("❌ API Error: $err");
        return null;
    }

    return json_decode($response, true);
}

/**
 * Führt einen POST-Request an die ShiftJuggler API aus
 *
 * @param string $endpoint z. B. 'shift.create'
 * @param array $payload JSON-Daten die gesendet werden
 * @param bool $dryRun ob wirklich senden oder nur loggen
 * @return array JSON-dekodierte API-Antwort oder Dummy
 */
function sendToShiftJuggler(string $endpoint, array $payload, bool $dryRun = true): array {
    $url = "https://freibad-dabringhausen-api.shiftjuggler.com/api/{$endpoint}";

    if ($dryRun) {
        logAction("DRY-RUN: Would POST to $url with payload: " . json_encode($payload));
        return ['dryRun' => true];
    }

    global $config;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_USERPWD, "{$config['api_user']}:{$config['api_password']}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logAction("❌ CURL-Fehler bei $endpoint: $error");
        return ['success' => false, 'error' => $error];
    }

    $decoded = json_decode($result, true);
    $decoded['httpCode'] = $httpCode;

    if ($httpCode >= 400) {
        logAction("❌ API-Fehler bei $endpoint [HTTP $httpCode]: $result");
    }

    return $decoded ?? ['success' => false, 'error' => 'Invalid JSON response'];
}