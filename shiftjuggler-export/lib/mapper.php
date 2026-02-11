<?php
require_once 'logger.php';

/**
 * Mappt lokale User-Daten in ShiftJuggler-Format
 */
function mapUser(array $user, array $config): array {
    $email = $user['email'] ?? null;
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = "no-reply+user{$user['id']}@example.com";
        logAction("⚠️ Kein E-Mail für userID={$user['id']}. Fallback gesetzt: $email");
    }

    return [
        'firstname'          => $user['firstname'],
        'lastname'           => $user['surname'],
        'email'              => $email,
        'isOfflineUser'      => false,
        'assignedWorkplaces' => (string)($config['workplace_id'] ?? 10)
    ];
}

/**
 * Mappt zusammengeführte Schicht in API-kompatiblen Shift-Objekt
 */
function mapShift(array $shift, string|int $employeeId, array $config): array {
    $start = $shift['start_time'] ?? null;
    $end   = $shift['end_time'] ?? null;

    if (empty($start) || strtotime($start) === false) {
        logAction("❌ Kein gültiger start_time-Wert in schedule_id={$shift['id']}");
        $formattedStart = "1970-01-01 00:00:00";
    } else {
        $formattedStart = date('Y-m-d H:i:s', strtotime($start));
    }

    // Schedule-Units berechnen in Stunden
    $scheduleUnits = 1;
    if (!empty($start) && !empty($end)) {
        $durationSecs = strtotime($end) - strtotime($start);
        $scheduleUnits = max(1, ceil($durationSecs / 3600)); // 1 Unit = 1 Stunde
    }

    return [
        'userID'         => (int)$employeeId,
        'startDate'      => $formattedStart,
        'startTime'      => null,
        'breakTime'      => 30,
        'scheduleUnits'  => $scheduleUnits,
        'workplaceID'    => (int)($config['workplace_id'] ?? 10),
        'status'         => 'published',
        'information'    => $shift['note'] ?? null,
    ];
}

/**
 * Optional: Absence-Mapping (Urlaub, Krankmeldung, etc.)
 */
function mapAbsence(array $absence, string|int $employeeId): array {
    return [
        'userID'    => (int)$employeeId,
        'typeID'    => (int)($absence['absence_type_id']),
        'startDate' => date('Y-m-d', strtotime($absence['start_date'])),
        'endDate'   => date('Y-m-d', strtotime($absence['end_date'])),
        'status'    => 'approved',
    ];
}
