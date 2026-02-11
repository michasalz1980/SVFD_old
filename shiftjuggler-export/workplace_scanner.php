<?php
$config = require 'config.php';
require 'lib/logger.php';

function apiGet($service, $action, $config) {
    $url = $config['api']['base_url'] . "$service.$action";
    $auth = base64_encode($config['api']['user'] . ':' . $config['api']['password']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth
        ]
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        logAction("❌ GET Error: $err");
        return null;
    }

    return json_decode($response, true);
}

// 🔍 Fetch & print Location + Workplace + ShiftGroup info
$result = apiGet('location', 'getCompleteLocationWorkplaceAndShiftGroupList', $config);

if (!$result || !isset($result['locations'])) {
    echo "❌ Keine Locations gefunden.\n";
    exit;
}

echo "🌍 Verfügbare Locations und Workplaces:\n";
foreach ($result['locations'] as $loc) {
    echo "\n📍 Location: {$loc['name']} (ID: {$loc['id']})\n";
    foreach ($loc['workplaces'] as $wp) {
        echo "  🏢 Workplace: {$wp['name']} (ID: {$wp['id']})\n";
        if (!empty($wp['shiftGroups'])) {
            foreach ($wp['shiftGroups'] as $sg) {
                echo "    ⏱️ ShiftGroup: {$sg['name']} (ID: {$sg['id']})\n";
            }
        }
    }
}
