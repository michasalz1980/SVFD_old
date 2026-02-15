<?php
declare(strict_types=1);

const WEBCAM_SOURCE_FILE = __DIR__ . '/bad_original.jpg';
const WEBCAM_OUTPUT_FILE = __DIR__ . '/bad.jpg';
const WEBCAM_WIDTH = 1280;
const WEBCAM_HEIGHT = 720;

function webcamLog(string $level, string $message, array $context = []): void
{
    // Never log secrets (URLs with credentials, passwords, tokens).
    unset($context['password'], $context['token'], $context['url']);

    $payload = [
        'level' => $level,
        'message' => $message,
        'context' => $context,
    ];

    error_log('[webcam/ftp.php] ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
}

function parseDotEnvFile(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $vars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }

        // Strip optional single/double quotes.
        $first = $value[0] ?? '';
        $last = $value[strlen($value) - 1] ?? '';
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function loadDotEnvIfPresent(string $path, bool $override = false): void
{
    $vars = parseDotEnvFile($path);
    foreach ($vars as $k => $v) {
        $already = getenv($k);
        if ($already !== false && !$override) {
            continue;
        }
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
    }
}

function envString(string $key, string $default = ''): string
{
    $v = getenv($key);
    if ($v === false) {
        return $default;
    }
    return (string) $v;
}

function envInt(string $key, int $default): int
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    $i = filter_var($v, FILTER_VALIDATE_INT);
    return ($i === false) ? $default : (int) $i;
}

function envBool(string $key, bool $default = false): bool
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }

    $v = strtolower(trim((string) $v));
    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return $default;
}

function buildReolinkSnapshotUrl(string $baseUrl, string $user, string $password, int $channel = 0): string
{
    $baseUrl = rtrim($baseUrl, '/');
    $rs = bin2hex(random_bytes(4));

    $query = http_build_query([
        'cmd' => 'Snap',
        'channel' => $channel,
        'rs' => $rs,
        'user' => $user,
        'password' => $password,
    ]);

    return $baseUrl . '/cgi-bin/api.cgi?' . $query;
}

function isLikelyJpeg(string $path): bool
{
    if (!is_file($path) || !is_readable($path)) {
        return false;
    }

    $size = filesize($path);
    if (!is_int($size) || $size < 1024) {
        return false;
    }

    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return false;
    }

    try {
        $head = fread($fh, 2);
        if ($head !== "\xFF\xD8") {
            return false;
        }
        return true;
    } finally {
        fclose($fh);
    }
}

function httpDownloadToFile(string $url, string $destFile, int $timeoutSec, bool $insecureTls): array
{
    // Returns: [ok(bool), http_code(int), error(string)]

    if (function_exists('curl_init')) {
        $fp = fopen($destFile, 'wb');
        if ($fp === false) {
            return [false, 0, 'Could not open destination file for writing'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            fclose($fp);
            return [false, 0, 'curl_init failed'];
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSec);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SVFD-webcam-pull/1.0');

        if ($insecureTls) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $ok = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = $ok ? '' : (string) curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if ($ok === false) {
            return [false, $httpCode, $err !== '' ? $err : 'curl_exec failed'];
        }

        return [($httpCode >= 200 && $httpCode < 300), $httpCode, ''];
    }

    // Fallback: file_get_contents
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSec,
            'header' => "User-Agent: SVFD-webcam-pull/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => !$insecureTls,
            'verify_peer_name' => !$insecureTls,
        ],
    ];

    $ctx = stream_context_create($opts);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        return [false, 0, 'file_get_contents failed'];
    }

    $written = file_put_contents($destFile, $data);
    if ($written === false) {
        return [false, 0, 'file_put_contents failed'];
    }

    return [true, 200, ''];
}

function pullWebcamSourceIfEnabled(string $sourceFile): void
{
    // Load optional per-directory .env (ignored by git).
    loadDotEnvIfPresent(__DIR__ . '/.env');

    if (!envBool('SVFD_WEBCAM_PULL_ENABLED', false)) {
        return;
    }

    $baseUrl = envString('SVFD_REOLINK_BASE_URL', '');
    $user = envString('SVFD_REOLINK_USER', '');
    $password = envString('SVFD_REOLINK_PASSWORD', '');
    $channel = envInt('SVFD_REOLINK_CHANNEL', 0);
    $timeout = envInt('SVFD_WEBCAM_PULL_TIMEOUT', 10);
    $insecure = envBool('SVFD_REOLINK_INSECURE', true);

    if ($baseUrl === '' || $user === '' || $password === '') {
        webcamLog('WARNING', 'Pull enabled but missing env vars', [
            'has_base' => $baseUrl !== '',
            'has_user' => $user !== '',
            'has_password' => $password !== '',
        ]);
        return;
    }

    $host = (string) (parse_url($baseUrl, PHP_URL_HOST) ?? '');
    $tmp = tempnam(dirname($sourceFile), 'snap_');
    if ($tmp === false) {
        webcamLog('ERROR', 'Failed to create temp file for snapshot');
        return;
    }

    $url = buildReolinkSnapshotUrl($baseUrl, $user, $password, $channel);
    [$ok, $httpCode, $err] = httpDownloadToFile($url, $tmp, $timeout, $insecure);

    if (!$ok) {
        @unlink($tmp);
        webcamLog('WARNING', 'Snapshot pull failed', [
            'host' => $host,
            'http_code' => $httpCode,
            'error' => $err,
        ]);
        return;
    }

    if (!isLikelyJpeg($tmp)) {
        @unlink($tmp);
        webcamLog('WARNING', 'Snapshot pull returned non-JPEG payload', [
            'host' => $host,
            'http_code' => $httpCode,
        ]);
        return;
    }

    if (!@rename($tmp, $sourceFile)) {
        @unlink($tmp);
        webcamLog('ERROR', 'Failed to move snapshot into place', ['dest' => basename($sourceFile)]);
        return;
    }

    @chmod($sourceFile, 0644);
    webcamLog('INFO', 'Snapshot pulled successfully', ['host' => $host]);
}

function loadJpegWithCapturedWarning(string $sourceFile): array
{
    $warning = null;
    set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
        $warning = $message;
        return true;
    });

    try {
        $image = imagecreatefromjpeg($sourceFile);
    } finally {
        restore_error_handler();
    }

    return [$image, $warning];
}

function createPlaceholderImage(string $outputFile, int $width, int $height): bool
{
    $placeholder = imagecreatetruecolor($width, $height);
    if ($placeholder === false) {
        return false;
    }

    $background = imagecolorallocate($placeholder, 36, 36, 36);
    $foreground = imagecolorallocate($placeholder, 255, 255, 255);
    imagefilledrectangle($placeholder, 0, 0, $width, $height, $background);
    imagestring($placeholder, 5, 30, 30, 'Webcam Bild nicht verfuegbar', $foreground);
    imagestring($placeholder, 3, 30, 55, date('Y-m-d H:i:s'), $foreground);

    $written = imagejpeg($placeholder, $outputFile, 90);
    imagedestroy($placeholder);
    return $written;
}

function processWebcamImage(
    string $sourceFile = WEBCAM_SOURCE_FILE,
    string $outputFile = WEBCAM_OUTPUT_FILE,
    int $newWidth = WEBCAM_WIDTH,
    int $newHeight = WEBCAM_HEIGHT
): array {
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagesx')) {
        webcamLog('ERROR', 'GD extension not available', ['source_file' => $sourceFile]);
        return ['status' => 'error', 'message' => 'ERROR: GD extension missing'];
    }

    // Optional: pull fresh snapshot from Reolink before processing.
    pullWebcamSourceIfEnabled($sourceFile);

    if (!is_file($sourceFile) || !is_readable($sourceFile) || filesize($sourceFile) === 0) {
        webcamLog('WARNING', 'Source file missing or unreadable', ['source_file' => $sourceFile]);
        if (is_file($outputFile)) {
            return ['status' => 'skipped', 'message' => 'SKIPPED: Quelle nicht lesbar, vorhandenes Bild bleibt aktiv.'];
        }

        if (createPlaceholderImage($outputFile, $newWidth, $newHeight)) {
            return ['status' => 'skipped', 'message' => 'SKIPPED: Platzhalterbild erstellt.'];
        }

        return ['status' => 'error', 'message' => 'ERROR: Quelle ungueltig und Platzhalter konnte nicht erstellt werden.'];
    }

    [$sourceImage, $jpegWarning] = loadJpegWithCapturedWarning($sourceFile);
    if ($sourceImage === false) {
        webcamLog('WARNING', 'Invalid JPEG source image', [
            'source_file' => $sourceFile,
            'warning' => $jpegWarning,
        ]);
        if (is_file($outputFile)) {
            return ['status' => 'skipped', 'message' => 'SKIPPED: Ungueltiges JPEG, vorhandenes Bild bleibt aktiv.'];
        }
        if (createPlaceholderImage($outputFile, $newWidth, $newHeight)) {
            return ['status' => 'skipped', 'message' => 'SKIPPED: Ungueltiges JPEG, Platzhalterbild erstellt.'];
        }
        return ['status' => 'error', 'message' => 'ERROR: Ungueltiges JPEG und kein Fallback verfuegbar.'];
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        imagedestroy($sourceImage);
        webcamLog('WARNING', 'Source image has invalid dimensions', [
            'source_file' => $sourceFile,
            'width' => $sourceWidth,
            'height' => $sourceHeight,
        ]);
        return ['status' => 'skipped', 'message' => 'SKIPPED: Ungueltige Bildabmessungen.'];
    }

    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($resizedImage === false) {
        imagedestroy($sourceImage);
        webcamLog('ERROR', 'Failed to allocate destination image', ['width' => $newWidth, 'height' => $newHeight]);
        return ['status' => 'error', 'message' => 'ERROR: Zielbild konnte nicht erstellt werden.'];
    }

    $resampleOk = imagecopyresampled(
        $resizedImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $sourceWidth,
        $sourceHeight
    );
    $writeOk = $resampleOk && imagejpeg($resizedImage, $outputFile, 90);

    imagedestroy($sourceImage);
    imagedestroy($resizedImage);

    if (!$writeOk) {
        webcamLog('ERROR', 'Failed to write resized output image', ['output_file' => $outputFile]);
        return ['status' => 'error', 'message' => 'ERROR: Skaliertes Bild konnte nicht gespeichert werden.'];
    }

    return ['status' => 'ok', 'message' => 'OK: Bild erfolgreich skaliert.'];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $result = processWebcamImage();
    $httpCode = ($result['status'] === 'error') ? 500 : 200;
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code($httpCode);
    }
    echo $result['message'];
}
