<?php
$config = require 'config.php'; // Load the configuration array.

$database = $config['database']; // Database configuration.
$weatherApi = $config['weather_api']; // Weather API configuration.

function beautifyOutput($var) {
    echo "<pre>";
    print_r($var);
    echo "</pre>";   
}

function logError($message) {
    error_log($message, 3, '/logs/jobs.log'); // Adjust the log file path as needed
    http_response_code(500); // Set HTTP response code to 500 for error
    die($message); // Terminate the script and output the error message
}

// Construct API URL using config details
$url_api = "{$weatherApi['base_url']}?lat={$weatherApi['lat']}&lon={$weatherApi['lon']}&units=metric&APPID={$weatherApi['api_key']}";

$response = @file_get_contents($url_api);
if ($response === FALSE) {
    logError("Error fetching weather data: " . error_get_last()['message']);
}

$response = json_decode($response);
if (json_last_error() !== JSON_ERROR_NONE) {
    logError("JSON decode error: " . json_last_error_msg());
}

// Create database connection
$conn = new mysqli($database['host'], $database['username'], $database['password'], $database['name']);

if ($conn->connect_error) {
    logError("Connection failed: " . $conn->connect_error);
}

$success = true;

foreach ($response->list as $key => $value) {
    $sql = 'INSERT INTO ffd_weather_forecast (dateTime, temp, feels_like, temp_min, temp_max, pressure, humidity, cloud, created) VALUES (';
    $sql .= join(',', [
        '"' . $value->dt_txt . '"',
        $value->main->temp, 
        $value->main->feels_like, 
        $value->main->temp_min, 
        $value->main->temp_max,
        $value->main->pressure,
        $value->main->humidity, 
        $value->clouds->all,
        '"' . date('Y-m-d H:i:s') . '"'
    ]);
    $sql .= ')';
    
    if ($conn->query($sql) === TRUE) {
       // beautifyOutput(date('Y-m-d H:i:s') . "New record created successfully");
    } else {
        $success = false;
        // beautifyOutput(date('Y-m-d H:i:s') . "Error: " . $sql . "<br>" . $conn->error);
        logError("SQL error: " . $conn->error . " - Query: " . $sql);
    }
}

mysqli_close($conn);

if ($success) {
    http_response_code(200); // Set HTTP response code to 200 for success
    echo "All records inserted successfully.";
} else {
    http_response_code(500); // Set HTTP response code to 500 for error
    echo "There were errors inserting some records.";
}
?>
