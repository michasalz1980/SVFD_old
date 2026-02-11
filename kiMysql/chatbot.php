<?php
header('Content-Type: application/json');
session_start();

$config = require 'config.php';
$openai_api_key = $config['openai_api_key'];
$db_config = $config['db'];

$user_question = $_POST['question'] ?? '';
if (empty($user_question)) {
    echo json_encode(['answer' => 'Bitte gib eine Frage ein.']);
    exit;
}

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass']);
} catch (PDOException $e) {
    echo json_encode(['answer' => 'Datenbankfehler: ' . $e->getMessage()]);
    exit;
}

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$structure = "";
foreach ($tables as $table) {
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $structure .= "Tabelle $table:\n";
    foreach ($columns as $col) {
        $structure .= "- {$col['Field']} ({$col['Type']})\n";
    }
}

$metadata_json = file_get_contents(__DIR__ . '/metadata.json');
$prompt_template = $config['prompt'];

$prompt = str_replace(
    ['{metadata_json}', '{question}'],
    [$metadata_json, $user_question],
    $prompt_template
);

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openai_api_key",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "gpt-4",
        "messages" => [
            ["role" => "system", "content" => "Du bist ein SQL-Experte."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0
    ])
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$raw_response = $data['choices'][0]['message']['content'] ?? '';
file_put_contents('debug_prompt.txt', $raw_response);
preg_match('/SELECT .*?;\s*/is', $raw_response, $matches);
$sql = $matches[0] ?? '';

$timestamp = date('[Y-m-d H:i:s]');
$logId = substr(bin2hex(random_bytes(3)), 0, 6);
$logFile = __DIR__ . '/log/chatbot.log';

if ($sql === '') {
    $text = 'Es wurde kein gültiger SELECT-Befehl erkannt.';
} else {
    try {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            $text = 'Keine Ergebnisse gefunden.';
        } else {
            $_SESSION['last_results'] = $_SESSION['last_results'] ?? [];
            array_unshift($_SESSION['last_results'], $rows);
            $_SESSION['last_results'] = array_slice($_SESSION['last_results'], 0, 3);

            $text = "<table id='resultTable' class='table table-bordered'><thead><tr>";
            foreach (array_keys($rows[0]) as $col) {
                $text .= "<th>$col</th>";
            }
            $text .= "</tr></thead><tbody>";
            foreach ($rows as $row) {
                $text .= "<tr>";
                foreach ($row as $val) {
                    $text .= "<td>" . htmlspecialchars($val) . "</td>";
                }
                $text .= "</tr>";
            }
            $text .= "</tbody></table>";
        }
    } catch (PDOException $e) {
        $text = 'SQL-Fehler: ' . $e->getMessage();
    }
}

$logEntry = "$timestamp [ID: $logId] Frage: $user_question\n";
$logEntry .= "$timestamp [ID: $logId] GPT-Antwort (roh):\n" . str_replace("\n", "\n$timestamp [ID: $logId] ", trim($raw_response)) . "\n";
$logEntry .= "$timestamp [ID: $logId] SQL: $sql\n";
$logEntry .= "$timestamp [ID: $logId] Antwort: " . strip_tags(substr($text, 0, 500)) . "\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo json_encode(['answer' => $text]);
?>
