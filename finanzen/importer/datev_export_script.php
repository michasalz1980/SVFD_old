<?php
/**
 * DATEV Export für SV Freibad Dabringhausen e.V.
 * Exportiert Kassendaten im DATEV-kompatiblen Format
 * 
 * WICHTIG: Dieses Script ist NUR für den DATEV-Export!
 * Das normale Dashboard nutzt dashboard_api.php
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 1.0
 * @date 2025-06-27
 */

// Error Reporting für Development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prüfen ob dies ein DATEV-Export-Request ist
if (!isset($_GET['start_date']) && !isset($_GET['end_date'])) {
    // Dies ist kein DATEV-Export-Request - weiterleiten zum Dashboard
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Dies ist das DATEV-Export-Script. Für Dashboard-Daten nutzen Sie dashboard_api.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// UTF-8 Headers setzen
header('Content-Type: text/csv; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Datenschutz-Einstellungen für DATEV - WICHTIG: ANPASSEN!
define('DATEV_BERATER_NR', '99999'); // IHRE Beraternummer - UNBEDINGT ANPASSEN!
define('DATEV_MANDANT_NR', '001'); // IHRE Mandantennummer - UNBEDINGT ANPASSEN!
define('DATEV_WJ_BEGINN', '0101'); // Beginn Wirtschaftsjahr (TTMM)
define('DATEV_SACHKONTEN_LAENGE', '4'); // Länge der Sachkonten
define('DATEV_KONTENRAHMEN', 'SKR03'); // SKR03 oder SKR04
define('DATEV_KASSE_KONTO', '1200'); // Kassenkonto (1200 = Kasse)

// Parameter validieren
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$export_type = $_GET['export_type'] ?? 'daily_summary';

// Validation
if (!$start_date || !$end_date) {
    sendError('Parameter start_date und end_date sind erforderlich.');
}

if (!isValidDate($start_date) || !isValidDate($end_date)) {
    sendError('Ungültiges Datumsformat. Erwartetes Format: YYYY-MM-DD');
}

if (strtotime($start_date) > strtotime($end_date)) {
    sendError('Startdatum muss vor dem Enddatum liegen.');
}

// Gültige Export-Typen
$valid_export_types = ['daily_summary', 'detailed', 'summary_only'];
if (!in_array($export_type, $valid_export_types)) {
    sendError('Ungültiger Export-Typ. Erlaubt: ' . implode(', ', $valid_export_types));
}

try {
    // PDO Verbindung
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // UTF-8 Einstellungen
    $pdo->exec("SET character_set_client = utf8mb4");
    $pdo->exec("SET character_set_results = utf8mb4");
    $pdo->exec("SET character_set_connection = utf8mb4");
    
    // Prüfen ob transaction_type Spalte existiert
    $hasTransactionType = checkTransactionTypeColumn($pdo);
    
    // Export-Format bestimmen
    $use_full_datev_format = $_GET['use_datev_format'] ?? 'simple';
    
    // Sicherheitscheck für Konfiguration - nur bei vollständigem DATEV-Format
    if ($use_full_datev_format === 'full' && (DATEV_BERATER_NR === '99999' || DATEV_MANDANT_NR === '001')) {
        sendError('DATEV-Konfiguration erforderlich: Bitte ändern Sie DATEV_BERATER_NR und DATEV_MANDANT_NR in datev_export.php für das vollständige DATEV-Format.');
    }
    
    // Export-Daten generieren
    $exportData = generateExportData($pdo, $start_date, $end_date, $export_type, $hasTransactionType);
    
    if (empty($exportData)) {
        sendError('Keine Daten im angegebenen Zeitraum gefunden.');
    }
    
    // CSV generieren und ausgeben
    if ($use_full_datev_format === 'full') {
        generateDATEVDownload($exportData, $start_date, $end_date, $export_type);
    } else {
        generateCSVDownload($exportData, $start_date, $end_date, $export_type);
    }
    
} catch (Exception $e) {
    sendError('Datenbankfehler: ' . $e->getMessage());
}

/**
 * Prüft ob transaction_type Spalte existiert
 */
function checkTransactionTypeColumn($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM pos_sales LIKE 'transaction_type'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generiert Export-Daten basierend auf Typ
 */
function generateExportData($pdo, $start_date, $end_date, $export_type, $hasTransactionType) {
    switch ($export_type) {
        case 'daily_summary':
            return getDailySummaryData($pdo, $start_date, $end_date, $hasTransactionType);
        case 'detailed':
            return getDetailedData($pdo, $start_date, $end_date, $hasTransactionType);
        case 'summary_only':
            return getSummaryOnlyData($pdo, $start_date, $end_date, $hasTransactionType);
        default:
            throw new Exception('Unbekannter Export-Typ');
    }
}

/**
 * Tageszusammenfassung - Ein Eintrag pro Tag
 */
function getDailySummaryData($pdo, $start_date, $end_date, $hasTransactionType) {
    $data = [];
    $receiptCounter = 1;
    $year = date('Y', strtotime($start_date));
    
    if ($hasTransactionType) {
        // Mit transaction_type Spalte
        $sql = "
            SELECT 
                DATE(transaction_date) as sale_date,
                transaction_type,
                payment_method,
                SUM(CASE 
                    WHEN transaction_type = 'entnahme' THEN ABS(price * quantity)
                    ELSE price * quantity 
                END) as total_amount,
                COUNT(*) as transaction_count
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND transaction_type IN ('einlage', 'einnahme', 'entnahme')
            GROUP BY DATE(transaction_date), transaction_type, payment_method
            ORDER BY sale_date ASC, transaction_type ASC
        ";
    } else {
        // Fallback ohne transaction_type
        $sql = "
            SELECT 
                DATE(transaction_date) as sale_date,
                payment_method,
                CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) THEN 'entnahme'
                    WHEN product_description LIKE '%Einlage%' THEN 'einlage'
                    ELSE 'einnahme'
                END as transaction_type,
                SUM(CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) THEN ABS(price * quantity)
                    ELSE price * quantity 
                END) as total_amount,
                COUNT(*) as transaction_count
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND (
                (price > 0 AND product_description NOT LIKE '%Anfangsbestand%')
                OR (product_description LIKE '%Entnahme%' OR price < 0)
                OR (product_description LIKE '%Einlage%')
            )
            GROUP BY DATE(transaction_date), transaction_type, payment_method
            ORDER BY sale_date ASC, transaction_type ASC
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    
    while ($row = $stmt->fetch()) {
        $amount = (float)$row['total_amount'];
        $isWithdrawal = $row['transaction_type'] === 'entnahme';
        
        // Gegenkonto bestimmen
        $contraAccount = getContraAccount($row['transaction_type'], $row['payment_method']);
        
        // Belegtext generieren
        $belegtext = generateBelegtext($row['transaction_type'], $row['payment_method'], 'daily');
        
        $data[] = [
            'currency' => 'EUR',
            'amount' => $isWithdrawal ? -$amount : $amount,
            'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
            'document_date' => date('dm', strtotime($row['sale_date'])),
            'document_text' => $belegtext,
            'vat_rate' => '',
            'bu_code' => '',
            'contra_account' => $contraAccount,
            'cost1' => '',
            'cost2' => '',
            'cost_quantity' => '',
            'discount' => '',
            'message' => 'Kasse Import Standardformat'
        ];
    }
    
    return $data;
}

/**
 * Detaillierte Daten - Jede Transaktion einzeln
 */
function getDetailedData($pdo, $start_date, $end_date, $hasTransactionType) {
    $data = [];
    $receiptCounter = 1;
    $year = date('Y', strtotime($start_date));
    
    if ($hasTransactionType) {
        $sql = "
            SELECT 
                transaction_date,
                transaction_type,
                payment_method,
                product_description,
                price * quantity as amount,
                receipt_number
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND transaction_type IN ('einlage', 'einnahme', 'entnahme')
            ORDER BY transaction_date ASC, receipt_number ASC
        ";
    } else {
        $sql = "
            SELECT 
                transaction_date,
                payment_method,
                product_description,
                price * quantity as amount,
                receipt_number,
                CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) THEN 'entnahme'
                    WHEN product_description LIKE '%Einlage%' THEN 'einlage'
                    ELSE 'einnahme'
                END as transaction_type
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND (
                (price > 0 AND product_description NOT LIKE '%Anfangsbestand%')
                OR (product_description LIKE '%Entnahme%' OR price < 0)
                OR (product_description LIKE '%Einlage%')
            )
            ORDER BY transaction_date ASC, receipt_number ASC
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    
    while ($row = $stmt->fetch()) {
        $amount = (float)$row['amount'];
        $isWithdrawal = $row['transaction_type'] === 'entnahme';
        
        // Bei Entnahmen/negativen Beträgen Absolutwert verwenden
        if ($amount < 0) {
            $amount = abs($amount);
            $isWithdrawal = true;
        }
        
        $contraAccount = getContraAccount($row['transaction_type'], $row['payment_method']);
        $belegtext = generateBelegtext($row['transaction_type'], $row['payment_method'], 'detailed', $row['product_description']);
        
        $data[] = [
            'currency' => 'EUR',
            'amount' => $isWithdrawal ? -$amount : $amount,
            'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
            'document_date' => date('dm', strtotime($row['transaction_date'])),
            'document_text' => $belegtext,
            'vat_rate' => '',
            'bu_code' => '',
            'contra_account' => $contraAccount,
            'cost1' => '',
            'cost2' => '',
            'cost_quantity' => '',
            'discount' => '',
            'message' => 'Kasse Import Standardformat'
        ];
    }
    
    return $data;
}

/**
 * Nur Einnahmen/Entnahmen Zusammenfassung
 */
function getSummaryOnlyData($pdo, $start_date, $end_date, $hasTransactionType) {
    $data = [];
    $receiptCounter = 1;
    $year = date('Y', strtotime($start_date));
    
    if ($hasTransactionType) {
        $sql = "
            SELECT 
                transaction_type,
                payment_method,
                SUM(CASE 
                    WHEN transaction_type = 'entnahme' THEN ABS(price * quantity)
                    ELSE price * quantity 
                END) as total_amount
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND transaction_type IN ('einlage', 'einnahme', 'entnahme')
            GROUP BY transaction_type, payment_method
            HAVING total_amount > 0
            ORDER BY transaction_type ASC
        ";
    } else {
        $sql = "
            SELECT 
                CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) THEN 'entnahme'
                    WHEN product_description LIKE '%Einlage%' THEN 'einlage'
                    ELSE 'einnahme'
                END as transaction_type,
                payment_method,
                SUM(CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) THEN ABS(price * quantity)
                    ELSE price * quantity 
                END) as total_amount
            FROM pos_sales 
            WHERE transaction_date BETWEEN ? AND ?
            AND (
                (price > 0 AND product_description NOT LIKE '%Anfangsbestand%')
                OR (product_description LIKE '%Entnahme%' OR price < 0)
                OR (product_description LIKE '%Einlage%')
            )
            GROUP BY transaction_type, payment_method
            HAVING total_amount > 0
            ORDER BY transaction_type ASC
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    
    while ($row = $stmt->fetch()) {
        $amount = (float)$row['total_amount'];
        $isWithdrawal = $row['transaction_type'] === 'entnahme';
        
        $contraAccount = getContraAccount($row['transaction_type'], $row['payment_method']);
        $belegtext = generateBelegtext($row['transaction_type'], $row['payment_method'], 'summary');
        
        $data[] = [
            'currency' => 'EUR',
            'amount' => $isWithdrawal ? -$amount : $amount,
            'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
            'document_date' => date('dm', strtotime($start_date)),
            'document_text' => $belegtext . ' (' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . ')',
            'vat_rate' => '',
            'bu_code' => '',
            'contra_account' => $contraAccount,
            'cost1' => '',
            'cost2' => '',
            'cost_quantity' => '',
            'discount' => '',
            'message' => 'Kasse Import Standardformat'
        ];
    }
    
    return $data;
}

/**
 * Bestimmt das Gegenkonto basierend auf Transaktionstyp und Zahlungsart
 */
function getContraAccount($transactionType, $paymentMethod) {
    if ($transactionType === 'einnahme' || $transactionType === 'einlage') {
        return '43000'; // Kassenumsätze/Tageseinnahmen/Einlagen
    } else if ($transactionType === 'entnahme') {
        // Unterscheidung nach Zahlungsart
        $paymentMethod = strtolower($paymentMethod);
        if (strpos($paymentMethod, 'ec') !== false || 
            strpos($paymentMethod, 'card') !== false || 
            strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'karte') !== false) {
            return '13721'; // Entnahme EC-Cash/Karte
        } else {
            return '13720'; // Entnahme Bar
        }
    }
    
    return '43000'; // Fallback
}

/**
 * Generiert Belegtext basierend auf Typ und Kontext
 */
function generateBelegtext($transactionType, $paymentMethod, $mode, $productDescription = '') {
    if ($transactionType === 'einnahme') {
        if ($mode === 'detailed' && $productDescription) {
            return 'Kassenumsatz: ' . substr($productDescription, 0, 30);
        } else {
            return 'Tageseinnahme Registrierkasse';
        }
    } else if ($transactionType === 'einlage') {
        if ($mode === 'detailed' && $productDescription) {
            return 'Einlage: ' . substr($productDescription, 0, 30);
        } else {
            return 'Einlage Registrierkasse';
        }
    } else if ($transactionType === 'entnahme') {
        $paymentMethod = strtolower($paymentMethod);
        if (strpos($paymentMethod, 'ec') !== false || 
            strpos($paymentMethod, 'card') !== false || 
            strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'karte') !== false) {
            return 'Entnahme EC-Cash (SUMUP)';
        } else {
            return 'Entnahme Registrierkasse';
        }
    }
    
    return 'Kassenbewegung';
}

/**
 * Generiert vollständiges DATEV-Format mit Kopfzeile
 */
function generateDATEVDownload($data, $start_date, $end_date, $export_type) {
    $buchungsmonat = date('m', strtotime($start_date));
    $year = date('Y', strtotime($start_date));
    
    // Filename im DATEV-Format
    $filename = 'EXTF_Buchungsstapel_' . $year . $buchungsmonat . '_' . date('YmdHis') . '.csv';
    
    // Headers für Download setzen
    header('Content-Type: text/csv; charset=windows-1252');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Output Buffer leeren
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $output = fopen('php://output', 'w');
    
    // DATEV-Kopfzeile generieren
    $header = [
        'EXTF',
        '510',
        '21',
        'Buchungsstapel',
        '7',
        date('Ymd', strtotime($start_date)),
        date('His'),
        '',
        'SV Freibad Dabringhausen',
        '',
        '',
        DATEV_BERATER_NR,
        DATEV_MANDANT_NR,
        DATEV_WJ_BEGINN,
        DATEV_SACHKONTEN_LAENGE,
        date('Ymd', strtotime($start_date)),
        date('Ymd', strtotime($end_date)),
        '',
        '',
        '1',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ];
    
    // Kopfzeile schreiben (Windows-1252 Encoding)
    fputcsv($output, array_map('utf8_decode', $header), ';', '"');
    
    // Spaltenbezeichnungen
    $columns = [
        'Umsatz (ohne Soll/Haben-Kz)',
        'Soll/Haben-Kennzeichen',
        'WKZ Umsatz',
        'Kurs',
        'Basis-Umsatz',
        'WKZ Basis-Umsatz',
        'Konto',
        'Gegenkonto (ohne BU-Schlüssel)',
        'BU-Schlüssel',
        'Belegdatum',
        'Belegfeld 1',
        'Belegfeld 2',
        'Skonto',
        'Buchungstext'
    ];
    
    fputcsv($output, array_map('utf8_decode', $columns), ';', '"');
    
    // Buchungsdaten schreiben
    foreach ($data as $row) {
        $amount = abs($row['amount']);
        $sollHaben = $row['amount'] < 0 ? 'H' : 'S';
        
        $datevRow = [
            number_format($amount, 2, ',', ''),
            $sollHaben,
            'EUR',
            '',
            '',
            '',
            DATEV_KASSE_KONTO, // Kasse/Bank
            $row['contra_account'],
            '',
            $row['document_date'],
            $row['receipt_number'],
            '',
            '',
            utf8_decode($row['document_text'])
        ];
        
        fputcsv($output, $datevRow, ';', '"');
    }
    
    fclose($output);
    exit;
}

/**
 * Generiert CSV-Download (vereinfachtes Format)
 */
function generateCSVDownload($data, $start_date, $end_date, $export_type) {
    // Filename generieren
    $filename = 'datev_export_' . $start_date . '_' . $end_date . '_' . $export_type . '.csv';
    
    // Headers für Download setzen
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Output Buffer leeren
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // CSV Header schreiben
    $output = fopen('php://output', 'w');
    
    // BOM für UTF-8 Excel-Kompatibilität
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header-Zeile
    $headers = [
        'Währung',
        'VorzBetrag', 
        'RechNr',
        'BelegDatum',
        'Belegtext',
        'UStSatz',
        'BU',
        'Gegenkonto',
        'Kost1',
        'Kost2',
        'Kostmenge',
        'Skonto',
        'Nachricht'
    ];
    
    fputcsv($output, $headers, ';', '"');
    
    // Daten schreiben
    foreach ($data as $row) {
        $csvRow = [
            $row['currency'],
            formatAmount($row['amount']),
            $row['receipt_number'],
            $row['document_date'],
            $row['document_text'],
            $row['vat_rate'],
            $row['bu_code'],
            $row['contra_account'],
            $row['cost1'],
            $row['cost2'],
            $row['cost_quantity'],
            $row['discount'],
            $row['message']
        ];
        
        fputcsv($output, $csvRow, ';', '"');
    }
    
    fclose($output);
    exit;
}

/**
 * Formatiert Betrag für DATEV (deutsches Format mit Vorzeichen)
 */
function formatAmount($amount) {
    $sign = $amount >= 0 ? '+' : '';
    return $sign . number_format($amount, 2, ',', '');
}

/**
 * Validiert Datum
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Sendet JSON-Fehler und beendet Script
 */
function sendError($message) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>