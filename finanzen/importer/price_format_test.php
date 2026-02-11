<?php
/**
 * Test-Script für Preis-Formatierung
 * Überprüft ob deutsche und internationale Zahlenformate korrekt interpretiert werden
 */

// Neue parsePrice Funktion (korrigiert)
function parsePrice($priceString) {
    // Leerzeichen entfernen
    $priceString = str_replace(' ', '', $priceString);
    
    // KRITISCHER FIX: Deutsche Zahlenformate korrekt verarbeiten
    // Beispiel: "-1.019,00" = -1019,00 Euro (nicht -1,019!)
    
    // Prüfen ob es ein deutsches Format ist (Punkt als Tausender, Komma als Dezimal)
    if (preg_match('/^-?\d{1,3}(\.\d{3})*,\d{2}$/', $priceString)) {
        // Deutsches Format: 1.234.567,89
        // Punkte (Tausendertrennzeichen) entfernen
        $priceString = str_replace('.', '', $priceString);
        // Komma durch Punkt ersetzen (für float conversion)
        $priceString = str_replace(',', '.', $priceString);
    } elseif (preg_match('/^-?\d+,\d{1,2}$/', $priceString)) {
        // Einfaches deutsches Format: 123,45
        $priceString = str_replace(',', '.', $priceString);
    } elseif (preg_match('/^-?\d{1,3}(\,\d{3})*\.\d{2}$/', $priceString)) {
        // Amerikanisches Format: 1,234,567.89
        $priceString = str_replace(',', '', $priceString);
    }
    
    // Nur Ziffern, Punkt, Minus und Plus behalten
    $priceString = preg_replace('/[^0-9.\-+]/', '', $priceString);
    
    // Validierung
    if (empty($priceString) || !is_numeric($priceString)) {
        return false;
    }
    
    $price = (float)$priceString;
    
    // Plausibilitätsprüfung (erweitert für größere Beträge)
    if (abs($price) > 999999.99) {
        return false;
    }
    
    return $price;
}

// Test-Daten (aus Ihrem CSV)
$testPrices = [
    // Ihr Beispiel
    '-1.019,00',     // Sollte: -1019.00 (deutsche Tausender.Dezimal,format)
    
    // Weitere deutsche Formate
    '1.234,56',      // Sollte: 1234.56
    '12.345,67',     // Sollte: 12345.67  
    '123.456,78',    // Sollte: 123456.78
    '-2.500,00',     // Sollte: -2500.00
    
    // Einfache deutsche Formate
    '123,45',        // Sollte: 123.45
    '-45,67',        // Sollte: -45.67
    '5,00',          // Sollte: 5.00
    
    // Amerikanische Formate
    '1,234.56',      // Sollte: 1234.56
    '-2,500.00',     // Sollte: -2500.00
    
    // Einfache Formate
    '123.45',        // Sollte: 123.45
    '-67.89',        // Sollte: -67.89
    '100',           // Sollte: 100.00
    '-50',           // Sollte: -50.00
];

echo "=== PREIS-FORMAT TEST ===\n\n";
echo sprintf("%-15s | %-10s | %s\n", "Original", "Ergebnis", "Interpretation");
echo str_repeat("-", 50) . "\n";

foreach ($testPrices as $testPrice) {
    $result = parsePrice($testPrice);
    $interpretation = '';
    
    if ($result === false) {
        $interpretation = '❌ FEHLER';
        $result = 'FEHLER';
    } else {
        // Prüfen ob es korrekt interpretiert wurde
        if ($testPrice === '-1.019,00' && $result == -1019.00) {
            $interpretation = '✅ Korrekt (deutsche Tausender)';
        } elseif ($testPrice === '1.234,56' && $result == 1234.56) {
            $interpretation = '✅ Korrekt (deutsche Tausender)';
        } elseif ($testPrice === '123,45' && $result == 123.45) {
            $interpretation = '✅ Korrekt (deutsches Dezimal)';
        } elseif ($testPrice === '1,234.56' && $result == 1234.56) {
            $interpretation = '✅ Korrekt (amerikanisch)';
        } else {
            $interpretation = '✅ Interpretiert';
        }
    }
    
    echo sprintf("%-15s | %-10s | %s\n", 
        $testPrice, 
        is_numeric($result) ? number_format($result, 2) : $result,
        $interpretation
    );
}

echo "\n=== SPEZIAL-TEST: IHR CSV-BEISPIEL ===\n";
$csvLine = "07.07.2024 19:35:12;-1.019,00;Entnahme: Entnahme;1;Bar;00115";
$parts = explode(';', $csvLine);
$priceFromCsv = $parts[1]; // -1.019,00

echo "CSV-Zeile: $csvLine\n";
echo "Preis-Teil: '$priceFromCsv'\n";
$parsedPrice = parsePrice($priceFromCsv);
echo "Interpretiert als: " . number_format($parsedPrice, 2) . " EUR\n";

if ($parsedPrice == -1019.00) {
    echo "✅ KORREKT: -1.019,00 wurde als -1019,00 EUR interpretiert (deutsche Formatierung)\n";
} else {
    echo "❌ FEHLER: Falsche Interpretation!\n";
}

echo "\n=== EMPFEHLUNG ===\n";
echo "Für deutsche CSV-Dateien verwenden Sie:\n";
echo "- Tausendertrennzeichen: Punkt (.)\n";
echo "- Dezimaltrennzeichen: Komma (,)\n";
echo "- Beispiel: 1.234,56 = eintausendzweihundertvierunddreißig Euro sechsundfünfzig\n";
?>
