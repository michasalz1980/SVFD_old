<?php
require 'config.php'; // Load the configuration file
$config = require 'config.php'; // Load the configuration into a variable

// Get the database configuration from the configuration file
$database = $config['database'];

// Function to handle errors and set the response code
function handleError($message) {
    error_log($message, 3, '/logs/jobs.log'); // Adjust the log file path as needed
    http_response_code(500); // Set HTTP response code to 500 for error
    die($message); // Terminate the script and output the error message
}

// Establish a database connection
$connection = new mysqli($database['host'], $database['username'], $database['password'], $database['name']);

// Check if the connection was successful
if ($connection->connect_error) {
    handleError('Verbindung zur Datenbank fehlgeschlagen: ' . $connection->connect_error);
}

// Retrieve all entries in Tagesprotokoll where Zaehlerstand_Wasserleitungsnetz is 0
$sql = 'SELECT * FROM Tagesprotokoll WHERE Zaehlerstand_Wasserleitungsnetz = 0';
$result = $connection->query($sql);

if ($result === false) {
    handleError('Fehler bei der Abfrage: ' . $connection->error);
}

if ($result->num_rows > 0) {
    while ($tagesprotokoll = $result->fetch_assoc()) {
        $datum = $tagesprotokoll['Datum'];

        // Retrieve the Zaehlerstand_Wasserleitungsnetz from ffd_frischwasser
        // Note: Adapted to the datetime field
        $sql_frischwasser = 'SELECT counter FROM ffd_frischwasser WHERE datetime LIKE ?';
        $stmt = $connection->prepare($sql_frischwasser);
        $datum_zeit_muster = $datum . ' 09:%'; // Combine date and time
        $stmt->bind_param('s', $datum_zeit_muster);
        $stmt->execute();
        $stmt->bind_result($zaehlerstand);
        $stmt->fetch();
        $stmt->close();

        // Check if the Zaehlerstand_Wasserleitungsnetz is greater than 0
        $zaehlerstand = $zaehlerstand/1000;
        if ($zaehlerstand > 0) {
            // Update the Zaehlerstand_Wasserleitungsnetz in Tagesprotokoll
            $update_sql = 'UPDATE Tagesprotokoll SET Zaehlerstand_Wasserleitungsnetz = ? WHERE Datum = ?';
            $stmt_update = $connection->prepare($update_sql);
            $stmt_update->bind_param('is', $zaehlerstand, $datum);
            $stmt_update->execute();
            $stmt_update->close();

            // echo 'Zaehlerstand_Wasserleitungsnetz für Datum ' . $datum . " aktualisiert.<br/>\n";
        } else {
            // echo 'Der Zaehlerstand_Wasserleitungsnetz für Datum ' . $datum . " ist weiterhin 0.<br/>\n";
        }
    }
    http_response_code(200); // Set HTTP response code to 200 for success
} else {
    echo 'Es wurden keine Einträge im Tagesprotokoll mit einem Zaehlerstand_Wasserleitungsnetz von 0 gefunden.<br>';
    http_response_code(204); // Set HTTP response code to 204 for no content
}

// Close the database connection
$connection->close();
?>
