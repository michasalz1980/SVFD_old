<?php
session_start();
$id = $_GET['id'] ?? 0;
$id = (int) $id;

if (!isset($_SESSION['last_results'][$id])) {
    die("Kein Ergebnis vorhanden.");
}

$data = $_SESSION['last_results'][$id];

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="svfd_kimysql_' . $id . '.csv"');

$output = fopen('php://output', 'w');

if (!empty($data)) {
    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
}
fclose($output);
exit;
?>
