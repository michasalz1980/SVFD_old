<?php
declare(strict_types=1);

const WEBCAM_SOURCE_FILE = __DIR__ . '/bad_original.jpg';
const WEBCAM_OUTPUT_FILE = __DIR__ . '/bad.jpg';
const WEBCAM_WIDTH = 1280;
const WEBCAM_HEIGHT = 720;

function webcamLog(string $level, string $message, array $context = []): void
{
    $payload = [
        'level' => $level,
        'message' => $message,
        'context' => $context,
    ];

    error_log('[webcam/ftp.php] ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
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
