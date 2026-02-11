<?php
/**
 * CSV Debug Helper für SV Freibad Import-System
 * 
 * Analysiert CSV-Dateien auf häufige Probleme:
 * - Encoding-Probleme (UTF-8, ISO-8859-1, Windows-1252)
 * - Abschließende Semikola / leere Felder
 * - Spaltenanzahl-Probleme
 * - Ungültige Zeichen
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 1.0
 */

require_once 'config.php';

class CSVDebugHelper {
    
    private $file_path;
    private $delimiter;
    
    public function __construct($file_path, $delimiter = ';') {
        $this->file_path = $file_path;
        $this->delimiter = $delimiter;
    }
    
    /**
     * Vollständige CSV-Analyse durchführen
     */
    public function analyzeCSV() {
        echo "🔍 CSV Debug-Analyse\n";
        echo "=" . str_repeat("=", 40) . "\n";
        echo "Datei: " . basename($this->file_path) . "\n";
        echo "Pfad: " . $this->file_path . "\n\n";
        
        if (!file_exists($this->file_path)) {
            echo "❌ Datei nicht gefunden!\n";
            return false;
        }
        
        $this->checkFileInfo();
        $this->checkEncoding();
        $this->analyzeStructure();
        $this->checkForProblems();
        $this->showSampleData();
        
        return true;
    }
    
    /**
     * Datei-Informationen anzeigen
     */
    private function checkFileInfo() {
        echo "📁 DATEI-INFORMATIONEN\n";
        echo str_repeat("-", 25) . "\n";
        
        $size = filesize($this->file_path);
        $size_mb = round($size / 1024 / 1024, 2);
        
        echo "Größe: " . number_format($size) . " Bytes ($size_mb MB)\n";
        echo "Letzte Änderung: " . date('Y-m-d H:i:s', filemtime($this->file_path)) . "\n";
        echo "Berechtigung: " . (is_readable($this->file_path) ? '✅ Lesbar' : '❌ Nicht lesbar') . "\n";
        echo "\n";
    }
    
    /**
     * Encoding-Erkennung und -Analyse
     */
    private function checkEncoding() {
        echo "🔤 ENCODING-ANALYSE\n";
        echo str_repeat("-", 20) . "\n";
        
        $content = file_get_contents($this->file_path);
        $first_bytes = substr($content, 0, 20);
        
        // BOM-Erkennung
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            echo "✅ UTF-8 BOM erkannt\n";
        } else {
            echo "ℹ️  Kein UTF-8 BOM gefunden\n";
        }
        
        // Encoding-Erkennung
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
        $detected = mb_detect_encoding($content, $encodings, true);
        
        echo "Erkanntes Encoding: " . ($detected ?: 'Unbekannt') . "\n";
        
        // Erste Bytes anzeigen
        echo "Erste 20 Bytes (hex): ";
        for ($i = 0; $i < min(20, strlen($first_bytes)); $i++) {
            echo sprintf('%02X ', ord($first_bytes[$i]));
        }
        echo "\n";
        
        // Problematische Zeichen suchen
        $problematic_chars = [];
        for ($i = 0; $i < strlen($content); $i++) {
            $byte = ord($content[$i]);
            if ($byte > 127 && $byte < 160) { // Problematische Windows-1252 Zeichen
                $char = $content[$i];
                if (!isset($problematic_chars[$char])) {
                    $problematic_chars[$char] = 0;
                }
                $problematic_chars[$char]++;
            }
        }
        
        if (!empty($problematic_chars)) {
            echo "⚠️  Problematische Zeichen gefunden:\n";
            foreach ($problematic_chars as $char => $count) {
                echo "   Zeichen: '" . $char . "' (0x" . sprintf('%02X', ord($char)) . ") - $count mal\n";
            }
        } else {
            echo "✅ Keine problematischen Zeichen gefunden\n";
        }
        
        echo "\n";
    }
    
    /**
     * CSV-Struktur analysieren
     */
    private function analyzeStructure() {
        echo "📊 STRUKTUR-ANALYSE\n";
        echo str_repeat("-", 20) . "\n";
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            echo "❌ Kann Datei nicht öffnen\n";
            return;
        }
        
        $line_count = 0;
        $column_counts = [];
        $empty_lines = 0;
        $max_columns = 0;
        $min_columns = PHP_INT_MAX;
        
        while (($line = fgets($handle)) !== false) {
            $line_count++;
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                $empty_lines++;
                continue;
            }
            
            // Spalten zählen (einfache Methode)
            $columns = explode($this->delimiter, $line);
            $column_count = count($columns);
            
            // Leere Spalten am Ende nicht mitzählen
            while ($column_count > 0 && trim($columns[$column_count - 1]) === '') {
                $column_count--;
            }
            
            if (!isset($column_counts[$column_count])) {
                $column_counts[$column_count] = 0;
            }
            $column_counts[$column_count]++;
            
            $max_columns = max($max_columns, $column_count);
            $min_columns = min($min_columns, $column_count);
        }
        
        fclose($handle);
        
        echo "Gesamtzeilen: $line_count\n";
        echo "Leere Zeilen: $empty_lines\n";
        echo "Min. Spalten: $min_columns\n";
        echo "Max. Spalten: $max_columns\n";
        echo "Spaltenverteilung:\n";
        
        ksort($column_counts);
        foreach ($column_counts as $columns => $count) {
            $percentage = round(($count / ($line_count - $empty_lines)) * 100, 1);
            echo "   $columns Spalten: $count Zeilen ($percentage%)\n";
        }
        
        echo "\n";
    }
    
    /**
     * Spezifische Probleme suchen
     */
    private function checkForProblems() {
        echo "⚠️  PROBLEM-ANALYSE\n";
        echo str_repeat("-", 18) . "\n";
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            echo "❌ Kann Datei nicht öffnen\n";
            return;
        }
        
        $line_number = 0;
        $problems = [];
        $trailing_semicolons = 0;
        $inconsistent_columns = 0;
        
        // Erwartete Header
        $expected_header = ['Datum/Uhrzeit', 'Preis', 'Bezeichnung', 'Menge', 'Zahlung', 'Bonnr.'];
        
        while (($line = fgets($handle)) !== false) {
            $line_number++;
            $trimmed = trim($line);
            
            if (empty($trimmed)) continue;
            
            // Abschließendes Semikolon prüfen
            if (substr($trimmed, -1) === $this->delimiter) {
                $trailing_semicolons++;
            }
            
            // CSV parsen
            $columns = str_getcsv($trimmed, $this->delimiter);
            
            // Header-Zeile prüfen
            if ($line_number === 1) {
                // Leere Spalten am Ende entfernen für Vergleich
                $clean_columns = $columns;
                while (count($clean_columns) > 0 && trim(end($clean_columns)) === '') {
                    array_pop($clean_columns);
                }
                
                if (count($clean_columns) !== count($expected_header)) {
                    $problems[] = "Zeile $line_number: Unerwartete Anzahl Header-Spalten (" . count($clean_columns) . " statt " . count($expected_header) . ")";
                }
                
                for ($i = 0; $i < min(count($clean_columns), count($expected_header)); $i++) {
                    if (trim($clean_columns[$i]) !== $expected_header[$i]) {
                        $problems[] = "Zeile $line_number: Header-Spalte $i stimmt nicht überein: '" . trim($clean_columns[$i]) . "' != '" . $expected_header[$i] . "'";
                    }
                }
                continue;
            }
            
            // Spaltenanzahl prüfen (für Datenzeilen)
            $data_columns = count($columns);
            
            // Leere Spalten am Ende entfernen
            while ($data_columns > 0 && trim($columns[$data_columns - 1]) === '') {
                $data_columns--;
            }
            
            if ($data_columns < 6) {
                $problems[] = "Zeile $line_number: Zu wenige Spalten ($data_columns, benötigt: 6)";
            } elseif ($data_columns > 6) {
                // Nur erste 10 Probleme anzeigen
                if (count($problems) < 10) {
                    $problems[] = "Zeile $line_number: Zu viele Spalten ($data_columns, erwartet: 6)";
                }
                $inconsistent_columns++;
            }
            
            // Nur erste 50 Zeilen detailliert prüfen
            if ($line_number > 50) break;
        }
        
        fclose($handle);
        
        echo "Zeilen mit abschließendem Semikolon: $trailing_semicolons\n";
        echo "Zeilen mit zu vielen Spalten: $inconsistent_columns\n";
        
        if (empty($problems)) {
            echo "✅ Keine strukturellen Probleme gefunden\n";
        } else {
            echo "❌ Gefundene Probleme:\n";
            foreach (array_slice($problems, 0, 10) as $problem) {
                echo "   $problem\n";
            }
            if (count($problems) > 10) {
                echo "   ... und " . (count($problems) - 10) . " weitere\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Beispieldaten anzeigen
     */
    private function showSampleData() {
        echo "📋 BEISPIELDATEN (erste 5 Zeilen)\n";
        echo str_repeat("-", 35) . "\n";
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            echo "❌ Kann Datei nicht öffnen\n";
            return;
        }
        
        $line_number = 0;
        while (($line = fgets($handle)) !== false && $line_number < 5) {
            $line_number++;
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                echo "Zeile $line_number: [LEER]\n";
                continue;
            }
            
            echo "Zeile $line_number: ";
            
            // Zeile in Spalten aufteilen
            $columns = str_getcsv($trimmed, $this->delimiter);
            
            echo "(" . count($columns) . " Spalten)\n";
            for ($i = 0; $i < count($columns); $i++) {
                $value = trim($columns[$i]);
                $display_value = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                echo "   [$i] '" . $display_value . "'" . (empty($value) ? ' [LEER]' : '') . "\n";
            }
            echo "\n";
        }
        
        fclose($handle);
    }
    
    /**
     * Bereinigte CSV-Datei erstellen
     */
    public function createCleanedCSV($output_file = null) {
        if (!$output_file) {
            $pathinfo = pathinfo($this->file_path);
            $output_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_cleaned.' . $pathinfo['extension'];
        }
        
        echo "🧹 BEREINIGUNG\n";
        echo str_repeat("-", 12) . "\n";
        echo "Eingabe: " . basename($this->file_path) . "\n";
        echo "Ausgabe: " . basename($output_file) . "\n\n";
        
        $input_handle = fopen($this->file_path, 'r');
        $output_handle = fopen($output_file, 'w');
        
        if (!$input_handle || !$output_handle) {
            echo "❌ Kann Dateien nicht öffnen\n";
            return false;
        }
        
        $line_number = 0;
        $cleaned_lines = 0;
        
        while (($line = fgets($input_handle)) !== false) {
            $line_number++;
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                continue; // Leere Zeilen überspringen
            }
            
            // CSV parsen
            $columns = str_getcsv($trimmed, $this->delimiter);
            
            // Leere Spalten am Ende entfernen
            while (count($columns) > 0 && trim(end($columns)) === '') {
                array_pop($columns);
            }
            
            // Nur Zeilen mit mindestens 6 Spalten (oder Header) behalten
            if (count($columns) >= 6 || $line_number === 1) {
                // Nur die ersten 6 Spalten verwenden
                $output_columns = array_slice($columns, 0, 6);
                
                // Wieder als CSV schreiben
                fputcsv($output_handle, $output_columns, $this->delimiter);
                $cleaned_lines++;
            }
        }
        
        fclose($input_handle);
        fclose($output_handle);
        
        echo "✅ Bereinigte CSV erstellt\n";
        echo "   Verarbeitete Zeilen: $line_number\n";
        echo "   Bereinigte Zeilen: $cleaned_lines\n";
        echo "   Datei: $output_file\n";
        
        return $output_file;
    }
}

// Kommandozeilen-Verwendung
if (isset($argv[1])) {
    $csv_file = $argv[1];
} else {
    // Suche CSV-Dateien im Input-Verzeichnis
    $csv_files = glob(CSV_INPUT_DIR . '*.csv');
    if (empty($csv_files)) {
        echo "❌ Keine CSV-Dateien gefunden in " . CSV_INPUT_DIR . "\n";
        echo "Verwendung: php csv_debug_helper.php [pfad/zu/datei.csv]\n";
        exit(1);
    }
    $csv_file = $csv_files[0];
}

echo "📁 Verwende CSV-Datei: $csv_file\n\n";

$debugger = new CSVDebugHelper($csv_file);
if ($debugger->analyzeCSV()) {
    echo "\n🧹 Möchten Sie eine bereinigte Version erstellen? (j/n): ";
    $input = trim(fgets(STDIN));
    if (strtolower($input) === 'j' || strtolower($input) === 'y') {
        $debugger->createCleanedCSV();
    }
}

echo "\n✅ CSV-Debug abgeschlossen\n";
?>