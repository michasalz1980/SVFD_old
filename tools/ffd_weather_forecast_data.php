<?php
$config = require 'config.php';
$dbConfig = $config['database'];

$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$dbname = $dbConfig['name'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$csvFile = fopen('ffd_weather_forecast_data.csv', 'r');

$batchSize = 50; // Größe des Bulks
$batchData = [];
$batchCount = 0;

while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
    $batchData[] = [
        $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]
    ];
    
    if (count($batchData) === $batchSize) {
        bulkInsert($conn, $batchData);
        echo date("Y-m-d H:i:s") . ": Erfolgreich importiert\n";
        $batchData = []; // Reset the batch data
    }
}

// Insert any remaining data
if (count($batchData) > 0) {
    bulkInsert($conn, $batchData);
    echo date("Y-m-d H:i:s") . ": Erfolgreich importiert\n";
}

fclose($csvFile);
$conn->close();

function bulkInsert($conn, $data) {
    $values = [];
    $params = [];
    $types = '';

    foreach ($data as $row) {
        $rowParams = [];
        foreach ($row as $index => $value) {
            $rowParams[] = '?';
            $params[] = $value;
            switch (gettype($value)) {
                case 'double':
                    $types .= 'd';
                    break;
                case 'integer':
                    $types .= 'i';
                    break;
                case 'string':
                default:
                    $types .= 's';
                    break;
            }
        }
        $values[] = '(' . implode(',', $rowParams) . ')';
    }
    
    $sql = "REPLACE INTO ffd_weather_forecast (dateTime, temp, feels_like, temp_min, temp_max, pressure, humidity, cloud, created) VALUES " . implode(',', $values);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
}
?>
