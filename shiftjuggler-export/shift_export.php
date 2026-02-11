<?php
$config = require 'config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/merge_slots.php';
require_once __DIR__ . '/lib/mapper.php';
require_once __DIR__ . '/lib/shiftjuggler_api.php';

try {
    $pdo = connectDB($config);
} catch (PDOException $e) {
    logAction("❌ DB-Verbindung fehlgeschlagen: " . $e->getMessage());
    exit("DB Fehler");
}

// Konfig & Flags
$dryRun = $config['dry_run'] ?? true;
$allowedTypes = $config['allowed_user_types'] ?? ['aushilfe'];

// Hole relevante Slots mit Userdaten
$query = "
    SELECT s.*, u.type, u.firstname, u.surname, u.username AS email
    FROM schedule s
    JOIN user u ON u.id = s.user_id
	WHERE type = 'aushilfe' AND approved = 'true';
";
$statement = $pdo->query($query);
$allSlots = $statement->fetchAll(PDO::FETCH_ASSOC);
logAction("⏳ Rohdaten geladen: " . count($allSlots));

// Filtere nach user.type
$allSlots = array_filter($allSlots, fn($s) => in_array($s['type'], $allowedTypes));

// Merge zusammenhängende Slots
$mergedSlots = mergeApprovedSlots($allSlots);
logAction("🚀 Starte Shift-Export mit " . count($mergedSlots) . " Slots");
// Verarbeite jeden Slot
foreach ($mergedSlots as $slot) {
    $userId = $slot['user_id'];

    // Hole Employee-ID aus Mapping
    $stmt = $pdo->prepare("SELECT shiftjuggler_employee_id FROM shiftjuggler_user_map WHERE local_user_id = ?");
    $stmt->execute([$userId]);
    $employeeId = $stmt->fetchColumn();

    if (!$employeeId) {
        logAction("⏭️ Kein Mapping für user_id={$userId} ({$slot['start_time']}–{$slot['end_time']}) → skip");
        continue;
    }

    // API Payload erzeugen
    $payload = mapShift($slot, $employeeId, $config);

    // DRY-RUN oder Live senden
    $response = sendToShiftJuggler('shift.create', $payload, $dryRun);
    logAction("🧪 DRY-RUN: Shift erstellen für schedule_id={$slot['id']}");

    // Speichere Mapping (nur bei Live)
    if (!$dryRun && isset($response['id'])) {
        $insert = $pdo->prepare("
            INSERT INTO shiftjuggler_shift_map (local_schedule_id, shiftjuggler_shift_id, hash, status, exported_at)
            VALUES (?, ?, ?, 'created', NOW())
            ON DUPLICATE KEY UPDATE shiftjuggler_shift_id = VALUES(shiftjuggler_shift_id), status = 'created', exported_at = NOW()
        ");

        $hash = hash('sha256', json_encode($payload));
        $insert->execute([$slot['id'], $response['id'], $hash]);

        logAction("✅ Shift exportiert: schedule_id={$slot['id']} → SJ-ID={$response['id']}");
    }
}
