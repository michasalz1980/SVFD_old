<?php
// Konfigurationsdatei einbinden
$config = require 'config.php';

// Datenbankverbindungsinformationen aus der Konfigurationsdatei
$dbHost = $config['database']['host'];
$dbUsername = $config['database']['username'];
$dbPassword = $config['database']['password'];
$dbName = $config['database']['name'];

// Verbindung erstellen
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Verbindung überprüfen
if ($conn->connect_error) {
    http_response_code(500);
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Pfad zur CSV-Datei
$csvFile = 'data/Lastgang.csv';

// CSV-Datei öffnen
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Erste Zeile (Header) überspringen
    fgetcsv($handle, 1000, ";");

    // Erfolgsvariable initialisieren
    $success = true;

    // Daten einlesen und in die Datenbank einfügen
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Datum ins MySQL-Format (YYYY-MM-DD) konvertieren
        $date = DateTime::createFromFormat('d.m.Y', $data[0])->format('Y-m-d');

        // Zeit unverändert übernehmen
        $time = $data[1];

        // Wert von Komma- in Punkt-Notation umwandeln
        $value = str_replace(',', '.', $data[2]);

        // SQL-REPLACE-INTO-Anweisung vorbereiten
        $sql = "REPLACE INTO ffd_lastgang (date, time, value) VALUES ('$date', '$time', '$value')";

        // Einfügen der Daten
        if ($conn->query($sql) !== TRUE) {
            $success = false;
            echo "Fehler beim Einfügen: " . $sql . "<br>" . $conn->error;
            break;
        }
    }

    // Datei schließen
    fclose($handle);

    // Erfolgs- oder Fehlermeldung zurückgeben
    if ($success) {
        http_response_code(200);
        echo "Daten erfolgreich importiert.";
    } else {
        http_response_code(500);
        echo "Ein Fehler ist beim Import der Daten aufgetreten.";
    }
} else {
    http_response_code(500);
    echo "Fehler beim Öffnen der Datei.";
}

// Verbindung schließen
$conn->close();
?>
