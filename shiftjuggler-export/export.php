<?php
$config = require 'config.php';
require 'lib/db.php';
require 'lib/logger.php';
require 'lib/shiftjuggler_api.php';
require 'lib/mapper.php';

$db = connectDB($config);
$dryRun = $config['dry_run'];

// Mitarbeiter abrufen
$users = $db->query("SELECT * FROM user")->fetchAll(PDO::FETCH_ASSOC);

// Mapping prüfen
$existing = $db->query("SELECT local_user_id FROM shiftjuggler_user_map")->fetchAll(PDO::FETCH_COLUMN);

$allowedTypes = $config['allowed_user_types'] ?? ['aushilfe'];

foreach ($users as $user) {
    $uid = $user['id'];
    $type = $user['type'] ?? null;

    if (!in_array($type, $allowedTypes)) {
        logAction("⏭️ User $uid ({$user['firstname']} {$user['surname']}) übersprungen wegen type=$type");
        continue;
    }

    if (in_array($uid, $existing)) {
        logAction("✅ Mapping vorhanden: user_id=$uid");
        continue;
    }

    $payload = mapUser($user, $config);
    $res = apiRequest('employee', 'create', $payload, $config, $dryRun);

    if (!$dryRun && isset($res['id'])) {
        $stmt = $db->prepare("INSERT INTO shiftjuggler_user_map (local_user_id, shiftjuggler_employee_id) VALUES (?, ?)");
        $stmt->execute([$uid, $res['id']]);
        logAction("👤 Created employee: {$user['firstname']} {$user['surname']}");
    } else {
        logAction("🧪 DRY-RUN: Would POST employee.create for user_id=$uid");
    }
}
