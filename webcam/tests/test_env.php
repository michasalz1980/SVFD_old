#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../ftp.php';

function assertSame($expected, $actual, string $msg): void {
    if ($expected !== $actual) {
        throw new RuntimeException($msg . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
    }
}

$tmp = sys_get_temp_dir() . '/svfd_webcam_env_' . bin2hex(random_bytes(4));
file_put_contents($tmp, "# comment\nFOO=bar\nQUOTED=\"hello\"\nSINGLE='world'\nEMPTY=\n");

$vars = parseDotEnvFile($tmp);
unlink($tmp);

assertSame('bar', $vars['FOO'] ?? null, 'FOO parse');
assertSame('hello', $vars['QUOTED'] ?? null, 'QUOTED parse');
assertSame('world', $vars['SINGLE'] ?? null, 'SINGLE parse');
assertSame('', $vars['EMPTY'] ?? null, 'EMPTY parse');

echo "OK: env parsing tests passed.\n";
