#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../ftp.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function makeTempDir(): string
{
    $dir = sys_get_temp_dir() . '/svfd_webcam_test_' . bin2hex(random_bytes(4));
    if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Temp directory could not be created.');
    }
    return $dir;
}

function testInvalidJpegKeepsExistingOutput(): void
{
    $dir = makeTempDir();
    $source = $dir . '/bad_original.jpg';
    $output = $dir . '/bad.jpg';
    file_put_contents($source, "not-a-jpeg");
    file_put_contents($output, "existing-image");

    $result = processWebcamImage($source, $output, 100, 60);
    assertTrue($result['status'] === 'skipped', 'Invalid JPEG should be skipped.');
    assertTrue(is_file($output), 'Existing output file must remain.');
}

function testValidJpegIsScaled(): void
{
    if (!function_exists('imagecreatetruecolor')) {
        throw new RuntimeException('GD extension missing, cannot run valid JPEG test.');
    }

    $dir = makeTempDir();
    $source = $dir . '/original.jpg';
    $output = $dir . '/scaled.jpg';

    $sourceImage = imagecreatetruecolor(20, 10);
    if ($sourceImage === false) {
        throw new RuntimeException('Could not create source image.');
    }
    $black = imagecolorallocate($sourceImage, 0, 0, 0);
    imagefilledrectangle($sourceImage, 0, 0, 20, 10, $black);
    imagejpeg($sourceImage, $source, 90);
    imagedestroy($sourceImage);

    $result = processWebcamImage($source, $output, 120, 80);
    assertTrue($result['status'] === 'ok', 'Valid JPEG should be processed.');
    assertTrue(is_file($output), 'Scaled output file missing.');

    $size = getimagesize($output);
    assertTrue($size !== false, 'Scaled output is not a valid image.');
    assertTrue($size[0] === 120 && $size[1] === 80, 'Scaled output dimensions are wrong.');
}

try {
    testInvalidJpegKeepsExistingOutput();
    testValidJpegIsScaled();
    echo "OK: webcam ftp tests passed.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
