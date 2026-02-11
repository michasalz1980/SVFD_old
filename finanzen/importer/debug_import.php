<?php
/**
 * Debug-Import für SV Freibad - vereinfachte Version ohne Duplikat-Checks
 */

require_once 'config.php';

class DebugImporter {
    private $pdo;
    
    public function __construct() {
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "✅ Datenbankverbindung hergestellt\n";
    }
    
    public function simpleImport($filename) {
        echo "🚀 Debug-Import: $filename\n";
        
        // CSV öffnen
        $handle = fopen($filename, 'r');
        if (!$handle) {
            die("❌ Kann Datei nicht öffnen: $filename\n");
        }
        
        // Header lesen
        $header = fgetcsv($handle, 0, ';');
        echo "📋 Header: " . implode(', ', $header) . "\n";
        
        // Datenbank-Status prüfen
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pos_sales");
        $existing_count = $stmt->fetchColumn();
        echo "📊 Vorhandene Datensätze: $existing_count\n";
        
        // SQL vorbereiten (ohne Duplikat-Checks)
        $sql = "INSERT INTO pos_sales (transaction_date, price, product_description, quantity, payment_method, receipt_number) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        $imported = 0;
        $errors = 0;
        $line_number = 1;
        
        // Zeilen einzeln verarbeiten (erste 100 zum Test)
        while (($row = fgetcsv($handle, 0, ';')) !== false && $line_number <= 100) {
            $line_number++;
            
            // Leere Felder am Ende entfernen
            while (count($row) > 6 && trim(end($row)) === '') {
                array_pop($row);
            }
            
            if (count($row) < 6) {
                echo "⚠️ Zeile $line_number übersprungen (zu wenige Spalten)\n";
                continue;
            }
            
            try {
                // Datum parsen
                $datetime = DateTime::createFromFormat('d.m.Y H:i:s', trim($row[0]));
                if (!$datetime) {
                    throw new Exception("Ungültiges Datum: " . $row[0]);
                }
                
                // Preis parsen
                $price = floatval(str_replace(',', '.', trim($row[1])));
                
                // Daten einfügen
                $stmt->execute([
                    $datetime->format('Y-m-d H:i:s'),
                    $price,
                    trim($row[2]), // Produktbeschreibung
                    intval(trim($row[3])), // Menge
                    trim($row[4]), // Zahlungsart
                    trim($row[5])  // Belegnummer
                ]);
                
                $imported++;
                
                if ($imported % 10 == 0) {
                    echo "✅ $imported Zeilen importiert...\n";
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "❌ Fehler in Zeile $line_number: " . $e->getMessage() . "\n";
                
                if ($errors > 10) {
                    echo "🛑 Zu viele Fehler - Abbruch\n";
                    break;
                }
            }
        }
        
        fclose($handle);
        
        // Final-Status
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pos_sales");
        $final_count = $stmt->fetchColumn();
        
        echo "\n📊 ERGEBNIS:\n";
        echo "   Importiert: $imported Zeilen\n";
        echo "   Fehler: $errors\n";
        echo "   Vorher in DB: $existing_count\n";
        echo "   Nachher in DB: $final_count\n";
        echo "   Differenz: " . ($final_count - $existing_count) . "\n";
        
        if ($final_count > $existing_count) {
            echo "✅ Import erfolgreich!\n";
        } else {
            echo "❌ Keine Daten hinzugefügt - Problem identifiziert!\n";
        }
    }
}

// CSV-Datei suchen
$csv_files = glob(CSV_INPUT_DIR . '*.csv');
if (empty($csv_files)) {
    die("❌ Keine CSV-Dateien in " . CSV_INPUT_DIR . " gefunden\n");
}

$csv_file = $csv_files[0];
echo "📁 Verwende Datei: $csv_file\n\n";

$importer = new DebugImporter();
$importer->simpleImport($csv_file);
?>