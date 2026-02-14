<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$resources = [
    'WebCam' => 'https://iykjlt0jy435sqad.myfritz.net:8088',
    'FritzBox' => 'https://iykjlt0jy435sqad.myfritz.net',
    'Wechselrichter' => 'https://iykjlt0jy435sqad.myfritz.net:8086',
    'MBUS Gateway' => 'http://iykjlt0jy435sqad.myfritz.net:8089',
];

$config = [
    'per_resource_connect_timeout' => 2,
    'per_resource_timeout' => 6,
    'ssl_verify_peer' => false,
    'ssl_verify_host' => false,
    'send_mail_always' => false,
    'send_mail_on_error' => true,
    'mail_cooldown_seconds' => 3600,
    'mail_to' => 'michasalz@gmail.com',
    'mail_from' => 'info@freibad-dabringhausen.de',
    'log_file' => __DIR__ . '/monitoring_log.txt',
    'state_file' => __DIR__ . '/monitoring_state.json',
    'max_log_lines' => 1000,
];

function asBool(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function normalizeStatus(int $httpCode, string $curlError, int $curlErrno): string
{
    if ($curlError !== '' || $curlErrno !== 0) {
        return 'Error';
    }
    return ($httpCode >= 200 && $httpCode < 400) ? 'OK' : 'Error';
}

function checkResourcesParallel(array $resources, array $config): array
{
    $results = [];

    if (!function_exists('curl_multi_init') || !function_exists('curl_init')) {
        foreach ($resources as $name => $url) {
            $results[$name] = [
                'url' => $url,
                'status' => 'Error',
                'detail' => 'cURL extension missing',
                'http_code' => 0,
                'duration_ms' => 0,
            ];
        }
        return $results;
    }

    $multi = curl_multi_init();
    $handles = [];

    foreach ($resources as $name => $url) {
        $ch = curl_init($url);
        if ($ch === false) {
            $results[$name] = [
                'url' => $url,
                'status' => 'Error',
                'detail' => 'curl_init failed',
                'http_code' => 0,
                'duration_ms' => 0,
            ];
            continue;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => (int) $config['per_resource_connect_timeout'],
            CURLOPT_TIMEOUT => (int) $config['per_resource_timeout'],
            CURLOPT_SSL_VERIFYPEER => asBool($config['ssl_verify_peer']),
            CURLOPT_SSL_VERIFYHOST => asBool($config['ssl_verify_host']) ? 2 : 0,
            CURLOPT_USERAGENT => 'SVFD-ServiceMonitoring/2',
        ];
        curl_setopt_array($ch, $options);

        curl_multi_add_handle($multi, $ch);
        $handles[(int) $ch] = [
            'name' => $name,
            'url' => $url,
            'handle' => $ch,
            'started_at' => microtime(true),
        ];
    }

    if (count($handles) > 0) {
        $running = null;
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0 && $status === CURLM_OK) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);
    }

    foreach ($handles as $meta) {
        $ch = $meta['handle'];
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $durationMs = (int) round((microtime(true) - $meta['started_at']) * 1000);
        $status = normalizeStatus($httpCode, $curlError, $curlErrno);
        $detail = ($curlError !== '') ? $curlError : ('HTTP ' . $httpCode);
        if ($curlErrno !== 0 && $curlError === '') {
            $detail = 'cURL error ' . $curlErrno;
        } elseif ($curlErrno !== 0) {
            $detail .= ' (errno ' . $curlErrno . ')';
        }

        $results[$meta['name']] = [
            'url' => $meta['url'],
            'status' => $status,
            'detail' => $detail,
            'curl_errno' => $curlErrno,
            'http_code' => $httpCode,
            'duration_ms' => $durationMs,
        ];

        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }

    curl_multi_close($multi);
    ksort($results);

    return $results;
}

function checkResourcesSequential(array $resources, array $config): array
{
    $results = [];
    foreach ($resources as $name => $url) {
        $ch = curl_init($url);
        if ($ch === false) {
            $results[$name] = [
                'url' => $url,
                'status' => 'Error',
                'detail' => 'curl_init failed',
                'curl_errno' => 0,
                'http_code' => 0,
                'duration_ms' => 0,
            ];
            continue;
        }

        $startedAt = microtime(true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => (int) $config['per_resource_connect_timeout'],
            CURLOPT_TIMEOUT => (int) $config['per_resource_timeout'],
            CURLOPT_SSL_VERIFYPEER => asBool($config['ssl_verify_peer']),
            CURLOPT_SSL_VERIFYHOST => asBool($config['ssl_verify_host']) ? 2 : 0,
            CURLOPT_USERAGENT => 'SVFD-ServiceMonitoring/2',
        ]);

        curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $status = normalizeStatus($httpCode, $curlError, $curlErrno);
        $detail = ($curlError !== '') ? $curlError : ('HTTP ' . $httpCode);
        if ($curlErrno !== 0 && $curlError === '') {
            $detail = 'cURL error ' . $curlErrno;
        } elseif ($curlErrno !== 0) {
            $detail .= ' (errno ' . $curlErrno . ')';
        }

        $results[$name] = [
            'url' => $url,
            'status' => $status,
            'detail' => $detail,
            'curl_errno' => $curlErrno,
            'http_code' => $httpCode,
            'duration_ms' => $durationMs,
        ];
        curl_close($ch);
    }

    ksort($results);
    return $results;
}

function hasOnlyEmptyTransportSignals(array $results): bool
{
    if (count($results) === 0) {
        return false;
    }
    foreach ($results as $row) {
        $http = (int) ($row['http_code'] ?? -1);
        $errno = (int) ($row['curl_errno'] ?? -1);
        if (!($http === 0 && $errno === 0)) {
            return false;
        }
    }
    return true;
}

function loadState(string $stateFile): array
{
    if (!is_file($stateFile)) {
        return [];
    }
    $json = @file_get_contents($stateFile);
    if ($json === false || $json === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveState(string $stateFile, array $state): void
{
    @file_put_contents($stateFile, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function shouldSendMail(bool $hasError, array $config, array $state): bool
{
    if (asBool($config['send_mail_always'])) {
        return true;
    }
    if (!asBool($config['send_mail_on_error']) || !$hasError) {
        return false;
    }

    $now = time();
    $lastMailTs = isset($state['last_mail_ts']) ? (int) $state['last_mail_ts'] : 0;
    $lastHasError = isset($state['last_has_error']) ? asBool($state['last_has_error']) : false;
    $cooldown = (int) $config['mail_cooldown_seconds'];

    if (!$lastHasError) {
        return true;
    }

    return ($now - $lastMailTs) >= $cooldown;
}

function buildHtml(array $payload): string
{
    $rows = [];
    foreach ($payload['resources'] as $name => $data) {
        $color = ($data['status'] === 'OK') ? 'green' : 'red';
        $rows[] = sprintf(
            "<tr><td>%s</td><td><a href='%s' target='_blank' rel='noopener noreferrer'>%s</a></td><td style='color:%s'>%s</td><td>%s</td><td>%d</td></tr>",
            htmlspecialchars((string) $name, ENT_QUOTES),
            htmlspecialchars((string) $data['url'], ENT_QUOTES),
            htmlspecialchars((string) $data['url'], ENT_QUOTES),
            $color,
            htmlspecialchars((string) $data['status'], ENT_QUOTES),
            htmlspecialchars((string) $data['detail'], ENT_QUOTES),
            (int) $data['duration_ms']
        );
    }

    return
        "<h2>SVFD Service Monitoring</h2>" .
        "<p>Status: <strong>" . htmlspecialchars((string) $payload['overall_status'], ENT_QUOTES) . "</strong> | " .
        "checked_at: " . htmlspecialchars((string) $payload['checked_at'], ENT_QUOTES) . " | " .
        "duration_ms: " . (int) $payload['duration_ms'] . "</p>" .
        "<table border='1' cellpadding='5' cellspacing='0'>" .
        "<tr><th>Ressource</th><th>URL</th><th>Status</th><th>Detail</th><th>Dauer (ms)</th></tr>" .
        implode('', $rows) .
        "</table>";
}

function appendLogLine(string $logFile, int $maxLogLines, array $payload): void
{
    $line = json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if ($line === false) {
        return;
    }

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    $content = @file($logFile);
    if (!is_array($content)) {
        return;
    }

    if (count($content) > $maxLogLines) {
        $content = array_slice($content, -$maxLogLines);
        @file_put_contents($logFile, implode('', $content), LOCK_EX);
    }
}

function writeJsonResponse(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($statusCode);
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

try {
    $scriptStartedAt = microtime(true);
    $results = checkResourcesParallel($resources, $config);
    $usedFallback = false;
    if (hasOnlyEmptyTransportSignals($results)) {
        $results = checkResourcesSequential($resources, $config);
        $usedFallback = true;
    }
    $totalDurationMs = (int) round((microtime(true) - $scriptStartedAt) * 1000);

    $okCount = 0;
    $errorCount = 0;
    foreach ($results as $data) {
        if ($data['status'] === 'OK') {
            $okCount++;
        } else {
            $errorCount++;
        }
    }
    $hasError = $errorCount > 0;

    $payload = [
        'overall_status' => $hasError ? 'degraded' : 'ok',
        'checked_at' => gmdate('c'),
        'duration_ms' => $totalDurationMs,
        'summary' => [
            'resource_count' => count($results),
            'ok_count' => $okCount,
            'error_count' => $errorCount,
        ],
        'resources' => $results,
        'meta' => [
            'parallel_fallback_used' => $usedFallback,
        ],
    ];

    $state = loadState((string) $config['state_file']);
    $mailAttempted = shouldSendMail($hasError, $config, $state);
    $mailSent = false;

    if ($mailAttempted) {
        $subjectPrefix = $hasError ? '#SVFD | Monitoring Fehler erkannt' : 'SVFD | Monitoring Status OK';
        $subject = $subjectPrefix . ' - ' . date('Y-m-d H:i:s');
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . $config['mail_from'] . "\r\n";
        $mailSent = @mail((string) $config['mail_to'], $subject, buildHtml($payload), $headers);
    }

    $state['last_has_error'] = $hasError;
    $state['last_checked_at'] = date('c');
    if ($mailSent) {
        $state['last_mail_ts'] = time();
    }
    saveState((string) $config['state_file'], $state);

    $payload['mail'] = [
        'attempted' => $mailAttempted,
        'sent' => $mailSent,
    ];

    appendLogLine((string) $config['log_file'], (int) $config['max_log_lines'], $payload);

    $format = $_GET['format'] ?? '';
    if ($format === 'html') {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            http_response_code(200);
        }
        echo buildHtml($payload);
    } else {
        writeJsonResponse($payload, 200);
    }

    exit($hasError ? 1 : 0);
} catch (Throwable $e) {
    $payload = [
        'overall_status' => 'error',
        'checked_at' => gmdate('c'),
        'error' => 'service_monitoring_runtime_error',
        'detail' => $e->getMessage(),
    ];

    @error_log('serviceMonitoring.php failed: ' . $e->getMessage());
    writeJsonResponse($payload, 500);
    exit(1);
}
