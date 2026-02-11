<?php

/**
 * mergeApprovedSlots
 * Führt alle approved=1 Slots pro user_id und Tag zusammen, wenn sie direkt aufeinanderfolgen
 * Ergebnis: reduzierte Slot-Menge mit konsolidierten Zeiträumen
 */
function mergeApprovedSlots(array $slots): array {
    $merged = [];

    // Gruppieren nach user_id + Tag
    $grouped = [];
    foreach ($slots as $slot) {
        # if ((int)$slot['approved'] !== 1) continue;

        $userId = $slot['user_id'];
        $day = date('Y-m-d', strtotime($slot['start_time']));
        $grouped["{$userId}_{$day}"][] = $slot;
    }

    // Gruppierte Slots pro Tag mergen
    foreach ($grouped as $key => $daySlots) {
        // Nach Startzeit sortieren
        usort($daySlots, function ($a, $b) {
            return strtotime($a['start_time']) <=> strtotime($b['start_time']);
        });

        $current = null;
        foreach ($daySlots as $slot) {
            if (!$current) {
                $current = $slot;
                continue;
            }

            $prevEnd = strtotime($current['end_time']);
            $nextStart = strtotime($slot['start_time']);

            // Direkt anschließender Slot (keine Lücke)
            if ($prevEnd === $nextStart) {
                $current['end_time'] = $slot['end_time'];
            } else {
                $merged[] = $current;
                $current = $slot;
            }
        }

        // Letzten Block speichern
        if ($current) {
            $merged[] = $current;
        }
    }

    return $merged;
}
