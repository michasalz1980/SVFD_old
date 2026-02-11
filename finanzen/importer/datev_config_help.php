<?php
/**
 * DATEV Konfigurationshilfe für SV Freibad Dabringhausen e.V.
 * 
 * WICHTIGE ANPASSUNGEN VOR DER NUTZUNG:
 * 
 * 1. ÖFFNEN Sie datev_export.php
 * 2. SUCHEN Sie diese Zeilen (ca. Zeile 37-42):
 * 
 * define('DATEV_BERATER_NR', '99999');     // ← ÄNDERN!
 * define('DATEV_MANDANT_NR', '001');       // ← ÄNDERN!
 * define('DATEV_WJ_BEGINN', '0101');       // ← ggf. ÄNDERN!
 * define('DATEV_KONTENRAHMEN', 'SKR03');   // ← ggf. ÄNDERN!
 * define('DATEV_KASSE_KONTO', '1200');     // ← ggf. ÄNDERN!
 * 
 * 3. ANPASSUNGEN vornehmen:
 */

// BEISPIEL-KONFIGURATION - ANPASSEN!
$datev_config = [
    // Ihre DATEV-Zugangsdaten (WICHTIG!)
    'BERATER_NR' => '12345',          // Ihre echte Beraternummer
    'MANDANT_NR' => '001',            // Ihre Mandantennummer
    
    // Buchungsperiode
    'WJ_BEGINN' => '0101',            // 1. Januar = 0101, 1. Juli = 0107
    
    // Kontenrahmen und -struktur
    'KONTENRAHMEN' => 'SKR03',        // SKR03 oder SKR04
    'SACHKONTEN_LAENGE' => '4',       // 4-stellige Konten
    
    // Konten-Zuordnung
    'KASSE_KONTO' => '1200',          // 1200 = Kasse, 1210 = Bank
    'EINNAHMEN_KONTO' => '43000',     // Kassenumsätze/Erlöse
    'ENTNAHME_BAR_KONTO' => '13720',  // Privatentnahme Bar
    'ENTNAHME_EC_KONTO' => '13721',   // Privatentnahme EC-Cash
];

/**
 * SCHRITT-FÜR-SCHRITT ANLEITUNG:
 * 
 * 1. Ihre DATEV-Zugangsdaten ermitteln:
 *    → Öffnen Sie DATEV Rechnungswesen
 *    → Schauen Sie unter: Stammdaten > Beraterdaten
 *    → Notieren Sie Beraternummer und Mandantennummer
 * 
 * 2. Wirtschaftsjahr prüfen:
 *    → Beginnt Ihr Wirtschaftsjahr am 1. Januar? → '0101'
 *    → Beginnt es zu einem anderen Datum? → Entsprechend anpassen
 *    → Beispiele: 1. Juli = '0107', 1. Oktober = '0110'
 * 
 * 3. Kontenrahmen bestätigen:
 *    → Nutzen Sie SKR03 oder SKR04?
 *    → In DATEV: Stammdaten > Sachkontenstamm anschauen
 * 
 * 4. Konten überprüfen:
 *    → Kasse: Meist 1200 (SKR03) oder 1600 (SKR04)
 *    → Erlöse: 43000 für Kassenumsätze
 *    → Entnahmen: 13720 (Bar), 13721 (EC-Cash)
 * 
 * 5. Export-Modi verstehen:
 *    → "Einfaches CSV": Für ASCII-Import in DATEV
 *    → "Vollständiges DATEV": EXTF-Format für Stapelverarbeitung
 */

// TYPISCHE KONTO-ZUORDNUNGEN:

// SKR03 (Standard):
$skr03_konten = [
    'Kasse' => '1200',
    'Bank' => '1800',
    'Kassenumsätze' => '43000',
    'Privatentnahmen' => '13720',
    'EC-Entnahmen' => '13721'
];

// SKR04 (Alternative):
$skr04_konten = [
    'Kasse' => '1600',
    'Bank' => '1200', 
    'Kassenumsätze' => '43000',
    'Privatentnahmen' => '13720',
    'EC-Entnahmen' => '13721'
];

/**
 * IMPORT IN DATEV:
 * 
 * Variante 1 - ASCII-Import (Einfaches CSV):
 * 1. DATEV Rechnungswesen öffnen
 * 2. Bestand > Importieren > ASCII-Daten
 * 3. CSV-Datei auswählen
 * 4. Spalten zuordnen:
 *    - Währung → Währung
 *    - VorzBetrag → Umsatz
 *    - RechNr → Belegnummer
 *    - BelegDatum → Belegdatum
 *    - Belegtext → Buchungstext
 *    - Gegenkonto → Gegenkonto
 * 5. Import starten
 * 
 * Variante 2 - DATEV-Format (EXTF):
 * 1. DATEV Rechnungswesen öffnen
 * 2. Bestand > Importieren > Stapelverarbeitung
 * 3. EXTF-Datei auswählen
 * 4. Import automatisch durchgeführt
 */

/**
 * HÄUFIGE PROBLEME UND LÖSUNGEN:
 * 
 * Problem: "Beraternummer stimmt nicht überein"
 * Lösung: DATEV_BERATER_NR in datev_export.php anpassen
 * 
 * Problem: "Mandantennummer nicht gefunden"
 * Lösung: DATEV_MANDANT_NR korrekt eintragen
 * 
 * Problem: "Wirtschaftsjahr stimmt nicht"
 * Lösung: DATEV_WJ_BEGINN entsprechend anpassen
 * 
 * Problem: "Konten nicht gefunden"
 * Lösung: Kontenplan in DATEV prüfen und Nummern anpassen
 * 
 * Problem: "Encoding-Fehler bei Umlauten"
 * Lösung: Vollständiges DATEV-Format verwenden (Windows-1252)
 */

// CHECKLISTE VOR DEM ERSTEN EXPORT:
$checkliste = [
    '☐ Beraternummer in datev_export.php eingetragen',
    '☐ Mandantennummer in datev_export.php eingetragen', 
    '☐ Wirtschaftsjahr-Beginn geprüft',
    '☐ Kontenrahmen (SKR03/SKR04) bestätigt',
    '☐ Kassenkonto-Nummer geprüft',
    '☐ Gegenkonto-Nummern validiert',
    '☐ Test-Export mit kleinem Zeitraum durchgeführt',
    '☐ Import in DATEV-Testmandant erfolgreich'
];

echo "DATEV-Konfiguration für SV Freibad Dabringhausen e.V.\n";
echo "=====================================================\n\n";
echo "Bitte folgen Sie der Anleitung in den Kommentaren!\n";
echo "Wichtig: Passen Sie die Konstanten in datev_export.php an.\n\n";

foreach ($checkliste as $item) {
    echo $item . "\n";
}

echo "\n\nBei Fragen zur Konfiguration wenden Sie sich an Ihren DATEV-Berater.\n";
?>
