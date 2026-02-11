<?php
header('Content-Type: application/json');

// DB-Zugangsdaten aus config.php
$config = require 'config.php';
$db_config = $config['db'];

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB-Verbindung fehlgeschlagen: ' . $e->getMessage()]);
    exit;
}

// Daten aus der Metadaten-Tabelle abrufen
$sql = "SELECT tabellenname, spaltenname, bedeutung FROM Metadaten ORDER BY tabellenname, spaltenname";
$stmt = $pdo->query($sql);
$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Struktur in GPT-freundliches JSON transformieren
$result = [];

foreach ($meta as $row) {
    $tabelle = $row['tabellenname'];
    $spalte = $row['spaltenname'];
    $beschreibung = $row['bedeutung'];
    if (!isset($result[$tabelle])) {
        $result[$tabelle] = [];
    }
    $result[$tabelle][$spalte] = $beschreibung;
}

// Ausgabe als JSON
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
