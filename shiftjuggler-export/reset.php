<?php
$config = require 'config.php';
require 'lib/db.php';
require 'lib/logger.php';
require 'lib/shiftjuggler_api.php';

$db = connectDB($config);
$dryRun = $config['dry_run'];

$ids = $db->query("SELECT shiftjuggler_employee_id FROM shiftjuggler_user_map")->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    $res = apiRequest('employee', 'delete', ['id' => $id], $config, $dryRun);

    if ($dryRun) {
        logAction("🧪 DRY-RUN: Would delete employee ID $id");
    } elseif (isset($res['error'])) {
        $msg = $res['error']['message'] ?? 'Unbekannter Fehler';
        logAction("❌ Fehler beim Löschen von employee ID $id: $msg");
    } else {
        logAction("🧹 Erfolgreich gelöscht: employee ID $id");
    }
}

if (!$dryRun) {
    $db->exec("TRUNCATE TABLE shiftjuggler_user_map");
    logAction("🗑️ shiftjuggler_user_map geleert.");
} else {
    logAction("🔍 DRY-RUN: Mapping-Tabelle würde geleert.");
}
