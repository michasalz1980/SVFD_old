<?php
/**
 * Vollständige Dashboard API für SV Freibad Web-Interface
 * INKLUSIVE DATEV-Export - Funktioniert mit bestehender Datenbank
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 2.0 - Mit integriertem DATEV-Export
 * @date 2025-06-27
 */

// UTF-8 Headers setzen
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // PDO mit expliziter UTF-8 Konfiguration
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Zusätzliche MySQL UTF-8 Einstellungen
    $pdo->exec("SET character_set_client = utf8mb4");
    $pdo->exec("SET character_set_results = utf8mb4");
    $pdo->exec("SET character_set_connection = utf8mb4");
    
    $action = $_GET['action'] ?? 'all';
    $year = $_GET['year'] ?? null;
    $response = ['status' => 'success', 'data' => []];
    
    // Prüfen ob transaction_type Spalte existiert
    $hasTransactionType = checkTransactionTypeColumn($pdo);
    
    // DATEV Export handling
    if ($action === 'datev_export') {
        handleDATEVExport($pdo, $hasTransactionType);
        return; // Script beendet hier für Export
    }
    
    switch ($action) {
        case 'stats':
            $response['data'] = getBasicStats($pdo, $year, $hasTransactionType);
            break;
            
        case 'daily_revenue':
            $response['data'] = getDailyRevenue($pdo, $year, $hasTransactionType);
            break;
            
        case 'payment_methods':
            $response['data'] = getPaymentMethods($pdo, $year, $hasTransactionType);
            break;
            
        case 'top_products':
            $response['data'] = getTopProducts($pdo, $year, $hasTransactionType);
            break;
            
        case 'bon_analysis':
            $response['data'] = getBonAnalysis($pdo, $year);
            break;
            
        case 'cash_flow':
            $response['data'] = getCashFlowAnalysis($pdo, $year, $hasTransactionType);
            break;
            
        case 'transaction_types':
            $response['data'] = getTransactionTypeAnalysis($pdo, $year, $hasTransactionType);
            break;
            
        case 'daily_cash_flow':
            $response['data'] = getDailyCashFlow($pdo, $year, $hasTransactionType);
            break;
            
        case 'import_status':
            $response['data'] = getImportStatus($pdo);
            break;
            
        case 'available_years':
            $response['data'] = getAvailableYears($pdo, $hasTransactionType);
            break;
            
        case 'all':
        default:
            $response['data'] = [
                'stats' => getBasicStats($pdo, $year, $hasTransactionType),
                'daily_revenue' => getDailyRevenue($pdo, $year, $hasTransactionType),
                'payment_methods' => getPaymentMethods($pdo, $year, $hasTransactionType),
                'top_products' => getTopProducts($pdo, $year, $hasTransactionType),
                'bon_analysis' => getBonAnalysis($pdo, $year),
                'cash_flow' => getCashFlowAnalysis($pdo, $year, $hasTransactionType),
                'transaction_types' => getTransactionTypeAnalysis($pdo, $year, $hasTransactionType),
                'daily_cash_flow' => getDailyCashFlow($pdo, $year, $hasTransactionType),
                'import_status' => getImportStatus($pdo),
                'available_years' => getAvailableYears($pdo, $hasTransactionType),
                'system_info' => [
                    'has_transaction_type' => $hasTransactionType,
                    'database_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
                ]
            ];
            break;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => [
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * DATEV Export Handler
 */
function handleDATEVExport($pdo, $hasTransactionType) {
    // Debug-Informationen loggen
    error_log("DATEV Export gestartet - GET Parameter: " . print_r($_GET, true));
    
    // Parameter validieren
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $export_type = $_GET['export_type'] ?? 'daily_summary';
    $use_datev_format = $_GET['use_datev_format'] ?? 'simple';

    // Validation
    if (!$start_date || !$end_date) {
        error_log("DATEV Export Fehler: Fehlende Parameter");
        sendExportError('Parameter start_date und end_date sind erforderlich.');
    }

    if (!isValidDate($start_date) || !isValidDate($end_date)) {
        error_log("DATEV Export Fehler: Ungültiges Datum - Start: $start_date, Ende: $end_date");
        sendExportError('Ungültiges Datumsformat. Erwartetes Format: YYYY-MM-DD');
    }

    if (strtotime($start_date) > strtotime($end_date)) {
        error_log("DATEV Export Fehler: Start nach Ende");
        sendExportError('Startdatum muss vor dem Enddatum liegen.');
    }

    // Gültige Export-Typen
    $valid_export_types = ['daily_summary', 'detailed', 'summary_only'];
    if (!in_array($export_type, $valid_export_types)) {
        error_log("DATEV Export Fehler: Ungültiger Export-Typ: $export_type");
        sendExportError('Ungültiger Export-Typ. Erlaubt: ' . implode(', ', $valid_export_types));
    }

    try {
        error_log("DATEV Export: Generiere Daten für Zeitraum $start_date bis $end_date, Typ: $export_type");
        
        // Export-Daten generieren
        $exportData = generateExportData($pdo, $start_date, $end_date, $export_type, $hasTransactionType);
        
        if (empty($exportData)) {
            error_log("DATEV Export: Keine Daten gefunden");
            sendExportError('Keine Daten im angegebenen Zeitraum gefunden.');
        }
        
        error_log("DATEV Export: " . count($exportData) . " Datensätze gefunden, starte CSV-Download");
        
        // CSV generieren und ausgeben
        generateCSVDownload($exportData, $start_date, $end_date, $export_type);
        
    } catch (Exception $e) {
        error_log("DATEV Export Datenbankfehler: " . $e->getMessage());
        sendExportError('Datenbankfehler: ' . $e->getMessage());
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
 * Tageszusammenfassung - Ein Eintrag pro Tag mit kombinierter Logik
 * Bar + EC-Cash = eine Tageseinnahme-Zeile
 * EC-Cash zusätzlich als separate Entnahme-Zeile
 */
function getDailySummaryData($pdo, $start_date, $end_date, $hasTransactionType) {
    $data = [];
    $receiptCounter = 1;
    $year = date('Y', strtotime($start_date));
    
    if ($hasTransactionType) {
        // Holen wir die Daten pro Tag und Type gruppiert
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
            AND (price * quantity) != 0
            GROUP BY DATE(transaction_date), transaction_type, payment_method
            HAVING total_amount > 0
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
            AND (price * quantity) != 0
            GROUP BY DATE(transaction_date), transaction_type, payment_method
            HAVING total_amount > 0
            ORDER BY sale_date ASC, transaction_type ASC
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    
    // Daten nach Datum gruppieren
    $dailyData = [];
    while ($row = $stmt->fetch()) {
        $date = $row['sale_date'];
        $type = $row['transaction_type'];
        $method = strtolower(trim($row['payment_method']));
        $amount = (float)$row['total_amount'];
        
        if (!isset($dailyData[$date])) {
            $dailyData[$date] = [
                'einnahme_bar' => 0,
                'einnahme_ec' => 0,
                'entnahme_bar' => 0,
                'entnahme_ec' => 0,
                'einlage' => 0
            ];
        }
        
        // Kategorisierung
        if ($type === 'einnahme') {
            if (strpos($method, 'ec') !== false || strpos($method, 'card') !== false || 
                strpos($method, 'karte') !== false || $method === 'ec-cash') {
                $dailyData[$date]['einnahme_ec'] += $amount;
            } else {
                $dailyData[$date]['einnahme_bar'] += $amount;
            }
        } elseif ($type === 'entnahme') {
            if (strpos($method, 'ec') !== false || strpos($method, 'card') !== false || 
                strpos($method, 'karte') !== false || $method === 'ec-cash') {
                $dailyData[$date]['entnahme_ec'] += $amount;
            } else {
                $dailyData[$date]['entnahme_bar'] += $amount;
            }
        } elseif ($type === 'einlage') {
            $dailyData[$date]['einlage'] += $amount;
        }
    }
    
    // CSV-Einträge generieren
    foreach ($dailyData as $date => $amounts) {
        // 1. Kombinierte Tageseinnahme (Bar + EC-Cash)
        $totalEinnahme = $amounts['einnahme_bar'] + $amounts['einnahme_ec'];
        if ($totalEinnahme > 0) {
            $data[] = [
                'currency' => 'EUR',
                'amount' => $totalEinnahme,
                'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
                'document_date' => date('dm', strtotime($date)),
                'document_text' => 'Tageseinnahme Registrierkasse',
                'vat_rate' => '',
                'bu_code' => '',
                'contra_account' => '43000',
                'cost1' => '',
                'cost2' => '',
                'cost_quantity' => '',
                'discount' => '',
                'message' => 'Kasse Import Standardformat'
            ];
            
            error_log("Kombinierte Tageseinnahme $date: Bar {$amounts['einnahme_bar']} + EC {$amounts['einnahme_ec']} = {$totalEinnahme}");
        }
        
        // 2. Separate EC-Cash Entnahme (wenn EC-Cash Einnahmen vorhanden)
        if ($amounts['einnahme_ec'] > 0) {
            $data[] = [
                'currency' => 'EUR',
                'amount' => -$amounts['einnahme_ec'],
                'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
                'document_date' => date('dm', strtotime($date)),
                'document_text' => 'Entnahme EC-Cash (Kartenumsatz)',
                'vat_rate' => '',
                'bu_code' => '',
                'contra_account' => '13721',
                'cost1' => '',
                'cost2' => '',
                'cost_quantity' => '',
                'discount' => '',
                'message' => 'Kasse Import Standardformat'
            ];
            
            error_log("EC-Cash Entnahme $date: -{$amounts['einnahme_ec']} (von Einnahme abgebucht)");
        }
        
        // 3. Normale Bar-Entnahmen
        if ($amounts['entnahme_bar'] > 0) {
            $data[] = [
                'currency' => 'EUR',
                'amount' => -$amounts['entnahme_bar'],
                'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
                'document_date' => date('dm', strtotime($date)),
                'document_text' => 'Entnahme Registrierkasse',
                'vat_rate' => '',
                'bu_code' => '',
                'contra_account' => '13720',
                'cost1' => '',
                'cost2' => '',
                'cost_quantity' => '',
                'discount' => '',
                'message' => 'Kasse Import Standardformat'
            ];
            
            error_log("Bar-Entnahme $date: -{$amounts['entnahme_bar']}");
        }
        
        // 4. Normale EC-Cash Entnahmen (zusätzlich zu den automatischen)
        if ($amounts['entnahme_ec'] > 0) {
            $data[] = [
                'currency' => 'EUR',
                'amount' => -$amounts['entnahme_ec'],
                'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
                'document_date' => date('dm', strtotime($date)),
                'document_text' => 'Entnahme EC-Cash (manuell)',
                'vat_rate' => '',
                'bu_code' => '',
                'contra_account' => '13721',
                'cost1' => '',
                'cost2' => '',
                'cost_quantity' => '',
                'discount' => '',
                'message' => 'Kasse Import Standardformat'
            ];
        }
        
        // 5. Einlagen
        if ($amounts['einlage'] > 0) {
            $data[] = [
                'currency' => 'EUR',
                'amount' => $amounts['einlage'],
                'receipt_number' => $year . '-' . str_pad($receiptCounter++, 4, '0', STR_PAD_LEFT),
                'document_date' => date('dm', strtotime($date)),
                'document_text' => 'Einlage Registrierkasse',
                'vat_rate' => '',
                'bu_code' => '',
                'contra_account' => '43000',
                'cost1' => '',
                'cost2' => '',
                'cost_quantity' => '',
                'discount' => '',
                'message' => 'Kasse Import Standardformat'
            ];
            
            error_log("Einlage $date: +{$amounts['einlage']}");
        }
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
        
        // Bei negativen Beträgen Absolutwert verwenden und als Entnahme markieren
        if ($amount < 0) {
            $amount = abs($amount);
            $isWithdrawal = true;
        } else {
            // Bestimme ob es sich um eine "Entnahme" handelt (nur bei SUMUP Einnahmen)
            $isWithdrawal = false;
            $paymentMethodLower = strtolower(trim($row['payment_method']));
            
            if ($row['transaction_type'] === 'entnahme') {
                $isWithdrawal = true;
            } else if ($row['transaction_type'] === 'einnahme') {
                // Nur SUMUP-Einnahmen werden als Entnahmen behandelt (nicht normale EC-Cash!)
                if (strpos($paymentMethodLower, 'sumup') !== false ||
                    strpos($paymentMethodLower, 'sum up') !== false) {
                    $isWithdrawal = true;
                    error_log("Detail: SUMUP-Einnahme wird als Entnahme behandelt");
                }
            }
        }
        
        // Null-Beträge überspringen
        if ($amount == 0) {
            error_log("Überspringe 0-Betrag Detail-Transaktion: " . $row['product_description']);
            continue;
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
        
        // Bestimme ob es sich um eine "Entnahme" handelt (nur bei SUMUP Einnahmen)
        $isWithdrawal = false;
        $paymentMethodLower = strtolower(trim($row['payment_method']));
        
        if ($row['transaction_type'] === 'entnahme') {
            $isWithdrawal = true;
        } else if ($row['transaction_type'] === 'einnahme') {
            // Nur SUMUP-Einnahmen werden als Entnahmen behandelt (nicht normale EC-Cash!)
            if (strpos($paymentMethodLower, 'sumup') !== false ||
                strpos($paymentMethodLower, 'sum up') !== false) {
                $isWithdrawal = true;
                error_log("Summary: SUMUP-Einnahme wird als Entnahme behandelt");
            }
        }
        
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
 * Korrigierte Logik: Nur SUMUP-Einnahmen werden als Entnahmen behandelt
 */
function getContraAccount($transactionType, $paymentMethod) {
    $paymentMethod = strtolower(trim($paymentMethod));
    
    // Debug-Log für Analyse
    error_log("Konto-Analyse: Type='$transactionType', Method='$paymentMethod'");
    
    // Spezialfall: Nur SUMUP-Einnahmen = Entnahme EC-Cash (nicht normale EC-Cash!)
    if ($transactionType === 'einnahme') {
        if (strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'sum up') !== false) {
            
            error_log("SUMUP-Einnahme erkannt -> Entnahme EC-Cash: 13721");
            return '13721'; // Entnahme EC-Cash (SUMUP)
        } else {
            // Alle anderen Einnahmen (auch normale EC-Cash) sind normale Kassenumsätze
            error_log("Normale Einnahme (inkl. EC-Cash): 43000");
            return '43000'; // Normale Kassenumsätze
        }
    }
    
    // Normale Entnahmen
    if ($transactionType === 'entnahme') {
        if (strpos($paymentMethod, 'ec') !== false || 
            strpos($paymentMethod, 'card') !== false || 
            strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'karte') !== false ||
            strpos($paymentMethod, 'sum up') !== false ||
            strpos($paymentMethod, 'kartenzahlung') !== false ||
            strpos($paymentMethod, 'terminal') !== false ||
            $paymentMethod === 'ec-cash' ||
            $paymentMethod === 'ec cash') {
            
            error_log("Entnahme EC-Cash: 13721");
            return '13721'; // Entnahme EC-Cash/Karte
        } else {
            error_log("Entnahme Bar: 13720");
            return '13720'; // Entnahme Bar
        }
    }
    
    // Einlagen
    if ($transactionType === 'einlage') {
        error_log("Einlage: 43000");
        return '43000'; // Einlagen
    }
    
    error_log("Fallback: 43000");
    return '43000'; // Fallback
}

/**
 * Generiert Belegtext basierend auf Typ und Kontext
 * Korrigierte Logik: Nur SUMUP-Einnahmen werden als Entnahmen dargestellt
 */
function generateBelegtext($transactionType, $paymentMethod, $mode, $productDescription = '') {
    $paymentMethod = strtolower(trim($paymentMethod));
    
    // Spezialfall: Nur SUMUP-Einnahmen = Entnahme EC-Cash (nicht normale EC-Cash!)
    if ($transactionType === 'einnahme') {
        if (strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'sum up') !== false) {
            
            // Nur SUMUP-Einnahmen werden als Entnahme EC-Cash behandelt
            return 'Entnahme EC-Cash (SUMUP)';
        } else {
            // Alle anderen Einnahmen (auch normale EC-Cash) sind normale Kassenumsätze
            if ($mode === 'detailed' && $productDescription) {
                return 'Kassenumsatz: ' . substr($productDescription, 0, 30);
            } else {
                return 'Tageseinnahme Registrierkasse';
            }
        }
    } 
    
    // Normale Entnahmen
    if ($transactionType === 'entnahme') {
        if (strpos($paymentMethod, 'ec') !== false || 
            strpos($paymentMethod, 'card') !== false || 
            strpos($paymentMethod, 'sumup') !== false ||
            strpos($paymentMethod, 'karte') !== false ||
            strpos($paymentMethod, 'sum up') !== false ||
            strpos($paymentMethod, 'kartenzahlung') !== false ||
            strpos($paymentMethod, 'terminal') !== false ||
            $paymentMethod === 'ec-cash' ||
            $paymentMethod === 'ec cash') {
            return 'Entnahme EC-Cash (SUMUP)';
        } else {
            return 'Entnahme Registrierkasse';
        }
    }
    
    // Einlagen
    if ($transactionType === 'einlage') {
        if ($mode === 'detailed' && $productDescription) {
            return 'Einlage: ' . substr($productDescription, 0, 30);
        } else {
            return 'Einlage Registrierkasse';
        }
    }
    
    return 'Kassenbewegung';
}

/**
 * Generiert CSV-Download (vereinfachtes Format)
 */
function generateCSVDownload($data, $start_date, $end_date, $export_type) {
    // Filename generieren
    $filename = 'datev_export_' . $start_date . '_' . $end_date . '_' . $export_type . '.csv';
    
    // Alle Output-Buffer leeren
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers für Download setzen
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Transfer-Encoding: binary');
    
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
function sendExportError($message) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function checkTransactionTypeColumn($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM pos_sales LIKE 'transaction_type'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function getBasicStats($pdo, $year = null, $hasTransactionType = false) {
    // WHERE-Klausel für Jahr-Filter
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    // Gesamtanzahl Transaktionen
    $stmt = $pdo->query("SELECT COUNT(*) FROM pos_sales $whereClause");
    $stats['total_transactions'] = (int)$stmt->fetchColumn();
    
    // Gesamtumsatz - mit oder ohne transaction_type
    if ($hasTransactionType) {
        $revenueWhere = $whereClause ? $whereClause . " AND transaction_type = 'einnahme'" : "WHERE transaction_type = 'einnahme'";
    } else {
        // Fallback: Nur positive Preise, ohne Einlagen/Entnahmen
        $excludeWhere = $whereClause ? $whereClause . " AND" : "WHERE";
        $revenueWhere = $excludeWhere . " price > 0 AND product_description NOT LIKE '%Einlage%' AND product_description NOT LIKE '%Entnahme%'";
    }
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(price * quantity), 0) FROM pos_sales $revenueWhere");
    $stats['total_revenue'] = (float)$stmt->fetchColumn();
    
    // Anzahl Bons
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT CONCAT(receipt_number, '-', transaction_date)) 
        FROM pos_sales $whereClause
    ");
    $stats['total_bons'] = (int)$stmt->fetchColumn();
    
    // Durchschnittlicher Bon-Wert
    if ($hasTransactionType) {
        $bonWhere = $whereClause ? $whereClause . " AND transaction_type = 'einnahme'" : "WHERE transaction_type = 'einnahme'";
    } else {
        $bonWhere = $revenueWhere;
    }
    
    $revenue_bons_stmt = $pdo->query("
        SELECT COUNT(DISTINCT CONCAT(receipt_number, '-', transaction_date)) 
        FROM pos_sales $bonWhere
    ");
    $revenue_bons = $revenue_bons_stmt->fetchColumn();
    $stats['avg_bon_value'] = $revenue_bons > 0 ? $stats['total_revenue'] / $revenue_bons : 0;
    
    // Zeitraum
    $stmt = $pdo->query("
        SELECT MIN(transaction_date) as oldest, MAX(transaction_date) as newest 
        FROM pos_sales $whereClause
    ");
    $timespan = $stmt->fetch();
    $stats['date_range'] = [
        'oldest' => $timespan['oldest'],
        'newest' => $timespan['newest']
    ];
    
    // Durchschnittliche Artikel pro Bon
    $stmt = $pdo->query("
        SELECT COALESCE(AVG(item_count), 0) as avg_items
        FROM (
            SELECT COUNT(*) as item_count
            FROM pos_sales 
            $whereClause
            GROUP BY receipt_number, transaction_date
        ) as bon_counts
    ");
    $stats['avg_items_per_bon'] = (float)$stmt->fetchColumn();
    
    $stats['filtered_year'] = $year;
    $stats['has_transaction_type'] = $hasTransactionType;
    
    return $stats;
}

function getCashFlowAnalysis($pdo, $year = null, $hasTransactionType = false) {
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    if ($hasTransactionType) {
        // Mit transaction_type Spalte
        $stmt = $pdo->query("
            SELECT 
                transaction_type,
                COUNT(*) as transaction_count,
                SUM(CASE 
                    WHEN transaction_type = 'entnahme' THEN ABS(price * quantity)
                    ELSE price * quantity 
                END) as total_amount
            FROM pos_sales 
            $whereClause
            GROUP BY transaction_type
        ");
        
        $cash_flow = [
            'einlagen' => ['amount' => 0, 'count' => 0],
            'einnahmen' => ['amount' => 0, 'count' => 0],
            'entnahmen' => ['amount' => 0, 'count' => 0]
        ];
        
        while ($row = $stmt->fetch()) {
            $type = $row['transaction_type'];
            $key = $type . 'n'; // einlage -> einlagen
            if ($type === 'einlage') $key = 'einlagen';
            if ($type === 'einnahme') $key = 'einnahmen';
            if ($type === 'entnahme') $key = 'entnahmen';
            
            $cash_flow[$key] = [
                'amount' => (float)$row['total_amount'],
                'count' => (int)$row['transaction_count']
            ];
        }
    } else {
        // Fallback ohne transaction_type Spalte
        $stmt = $pdo->query("
            SELECT 
                SUM(CASE 
                    WHEN (product_description LIKE '%Einlage%' OR product_description LIKE '%Anfangsbestand%') 
                    AND price > 0 
                    THEN price * quantity 
                    ELSE 0 
                END) as einlagen_amount,
                COUNT(CASE 
                    WHEN (product_description LIKE '%Einlage%' OR product_description LIKE '%Anfangsbestand%') 
                    AND price > 0 
                    THEN 1 
                END) as einlagen_count,
                
                SUM(CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) 
                    THEN ABS(price * quantity) 
                    ELSE 0 
                END) as entnahmen_amount,
                COUNT(CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) 
                    THEN 1 
                END) as entnahmen_count,
                
                SUM(CASE 
                    WHEN price > 0 
                    AND product_description NOT LIKE '%Einlage%' 
                    AND product_description NOT LIKE '%Anfangsbestand%'
                    AND product_description NOT LIKE '%Entnahme%'
                    THEN price * quantity 
                    ELSE 0 
                END) as einnahmen_amount,
                COUNT(CASE 
                    WHEN price > 0 
                    AND product_description NOT LIKE '%Einlage%' 
                    AND product_description NOT LIKE '%Anfangsbestand%'
                    AND product_description NOT LIKE '%Entnahme%'
                    THEN 1 
                END) as einnahmen_count
            FROM pos_sales 
            $whereClause
        ");
        
        $result = $stmt->fetch();
        
        $cash_flow = [
            'einlagen' => [
                'amount' => (float)$result['einlagen_amount'],
                'count' => (int)$result['einlagen_count']
            ],
            'einnahmen' => [
                'amount' => (float)$result['einnahmen_amount'],
                'count' => (int)$result['einnahmen_count']
            ],
            'entnahmen' => [
                'amount' => (float)$result['entnahmen_amount'],
                'count' => (int)$result['entnahmen_count']
            ]
        ];
    }
    
    // Nettoergebnis berechnen
    $net_result = $cash_flow['einlagen']['amount'] + 
                  $cash_flow['einnahmen']['amount'] - 
                  $cash_flow['entnahmen']['amount'];
    
    return [
        'einlagen' => $cash_flow['einlagen'],
        'einnahmen' => $cash_flow['einnahmen'],
        'entnahmen' => $cash_flow['entnahmen'],
        'net_result' => $net_result,
        'filtered_year' => $year,
        'has_transaction_type' => $hasTransactionType
    ];
}

function getTransactionTypeAnalysis($pdo, $year = null, $hasTransactionType = false) {
    if (!$hasTransactionType) {
        // Fallback ohne transaction_type
        $cash_flow = getCashFlowAnalysis($pdo, $year, false);
        
        return [
            [
                'type' => 'einlage',
                'count' => $cash_flow['einlagen']['count'],
                'amount' => $cash_flow['einlagen']['amount'],
                'percentage_count' => 0,
                'percentage_amount' => 0
            ],
            [
                'type' => 'einnahme',
                'count' => $cash_flow['einnahmen']['count'],
                'amount' => $cash_flow['einnahmen']['amount'],
                'percentage_count' => 0,
                'percentage_amount' => 0
            ],
            [
                'type' => 'entnahme',
                'count' => $cash_flow['entnahmen']['count'],
                'amount' => $cash_flow['entnahmen']['amount'],
                'percentage_count' => 0,
                'percentage_amount' => 0
            ]
        ];
    }
    
    // Mit transaction_type Spalte
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    $stmt = $pdo->query("
        SELECT 
            transaction_type,
            COUNT(*) as count,
            SUM(CASE 
                WHEN transaction_type = 'entnahme' THEN ABS(price * quantity)
                ELSE price * quantity 
            END) as amount
        FROM pos_sales 
        $whereClause
        GROUP BY transaction_type
        ORDER BY amount DESC
    ");
    
    $types = [];
    while ($row = $stmt->fetch()) {
        $types[] = [
            'type' => $row['transaction_type'],
            'count' => (int)$row['count'],
            'amount' => (float)$row['amount'],
            'percentage_count' => 0,
            'percentage_amount' => 0
        ];
    }
    
    return $types;
}

function getDailyCashFlow($pdo, $year = null, $hasTransactionType = false) {
    if ($year) {
        $whereClause = "WHERE YEAR(transaction_date) = " . intval($year);
        $orderClause = "ORDER BY sale_date ASC";
    } else {
        $whereClause = "WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $orderClause = "ORDER BY sale_date DESC LIMIT 30";
    }
    
    if ($hasTransactionType) {
        // Mit transaction_type Spalte
        $stmt = $pdo->query("
            SELECT 
                DATE(transaction_date) as sale_date,
                SUM(CASE WHEN transaction_type = 'einlage' THEN price * quantity ELSE 0 END) as einlagen,
                SUM(CASE WHEN transaction_type = 'einnahme' THEN price * quantity ELSE 0 END) as einnahmen,
                SUM(CASE WHEN transaction_type = 'entnahme' THEN ABS(price * quantity) ELSE 0 END) as entnahmen
            FROM pos_sales 
            $whereClause
            GROUP BY DATE(transaction_date)
            $orderClause
        ");
    } else {
        // Fallback ohne transaction_type
        $stmt = $pdo->query("
            SELECT 
                DATE(transaction_date) as sale_date,
                SUM(CASE 
                    WHEN (product_description LIKE '%Einlage%' OR product_description LIKE '%Anfangsbestand%') 
                    AND price > 0 
                    THEN price * quantity 
                    ELSE 0 
                END) as einlagen,
                SUM(CASE 
                    WHEN price > 0 
                    AND product_description NOT LIKE '%Einlage%' 
                    AND product_description NOT LIKE '%Anfangsbestand%'
                    AND product_description NOT LIKE '%Entnahme%'
                    THEN price * quantity 
                    ELSE 0 
                END) as einnahmen,
                SUM(CASE 
                    WHEN (product_description LIKE '%Entnahme%' OR price < 0) 
                    THEN ABS(price * quantity) 
                    ELSE 0 
                END) as entnahmen
            FROM pos_sales 
            $whereClause
            GROUP BY DATE(transaction_date)
            $orderClause
        ");
    }
    
    $daily_data = [];
    while ($row = $stmt->fetch()) {
        $net_result = $row['einlagen'] + $row['einnahmen'] - $row['entnahmen'];
        
        $daily_data[] = [
            'date' => $row['sale_date'],
            'einlagen' => (float)$row['einlagen'],
            'einnahmen' => (float)$row['einnahmen'],
            'entnahmen' => (float)$row['entnahmen'],
            'net_result' => $net_result
        ];
    }
    
    return $year ? $daily_data : array_reverse($daily_data);
}

function getDailyRevenue($pdo, $year = null, $hasTransactionType = false) {
    if ($year) {
        $whereClause = "WHERE YEAR(transaction_date) = " . intval($year);
        $orderClause = "ORDER BY sale_date ASC";
    } else {
        $whereClause = "WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
        $orderClause = "ORDER BY sale_date DESC LIMIT 14";
    }
    
    // Nur Einnahmen für korrekten Umsatz
    if ($hasTransactionType) {
        $whereClause .= " AND transaction_type = 'einnahme'";
    } else {
        $whereClause .= " AND price > 0 AND product_description NOT LIKE '%Einlage%' AND product_description NOT LIKE '%Entnahme%'";
    }
    
    $stmt = $pdo->query("
        SELECT DATE(transaction_date) as sale_date,
               COUNT(DISTINCT CONCAT(receipt_number, '-', transaction_date)) as bon_count,
               COUNT(*) as item_count,
               SUM(price * quantity) as daily_revenue
        FROM pos_sales 
        $whereClause
        GROUP BY DATE(transaction_date)
        $orderClause
    ");
    
    $daily_data = [];
    while ($row = $stmt->fetch()) {
        $daily_data[] = [
            'date' => $row['sale_date'],
            'revenue' => (float)$row['daily_revenue'],
            'bons' => (int)$row['bon_count'],
            'items' => (int)$row['item_count']
        ];
    }
    
    return $year ? $daily_data : array_reverse($daily_data);
}

function getPaymentMethods($pdo, $year = null, $hasTransactionType = false) {
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    // Nur Einnahmen für korrekte Zahlungsarten-Analyse
    if ($hasTransactionType) {
        $whereClause .= $whereClause ? " AND transaction_type = 'einnahme'" : "WHERE transaction_type = 'einnahme'";
    } else {
        $andWhere = $whereClause ? " AND" : "WHERE";
        $whereClause .= $andWhere . " price > 0 AND product_description NOT LIKE '%Einlage%' AND product_description NOT LIKE '%Entnahme%'";
    }
    
    $stmt = $pdo->query("
        SELECT payment_method, 
               COUNT(*) as transaction_count,
               SUM(price * quantity) as total_revenue
        FROM pos_sales 
        $whereClause
        GROUP BY payment_method 
        ORDER BY total_revenue DESC
    ");
    
    $payment_data = [];
    $total_revenue = 0;
    
    while ($row = $stmt->fetch()) {
        $total_revenue += $row['total_revenue'];
        $payment_data[] = $row;
    }
    
    foreach ($payment_data as &$payment) {
        $payment['revenue'] = (float)$payment['total_revenue'];
        $payment['percentage'] = $total_revenue > 0 ? ($payment['total_revenue'] / $total_revenue) * 100 : 0;
        $payment['transaction_count'] = (int)$payment['transaction_count'];
        unset($payment['total_revenue']);
    }
    
    return $payment_data;
}

function getTopProducts($pdo, $year = null, $hasTransactionType = false) {
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    // Nur Einnahmen für Top-Produkte
    if ($hasTransactionType) {
        $whereClause .= $whereClause ? " AND transaction_type = 'einnahme'" : "WHERE transaction_type = 'einnahme'";
    } else {
        $andWhere = $whereClause ? " AND" : "WHERE";
        $whereClause .= $andWhere . " price > 0 AND product_description NOT LIKE '%Einlage%' AND product_description NOT LIKE '%Entnahme%'";
    }
    
    $stmt = $pdo->query("
        SELECT product_description, 
               SUM(quantity) as total_quantity,
               SUM(price * quantity) as total_revenue,
               COUNT(*) as transaction_count
        FROM pos_sales 
        $whereClause
        GROUP BY product_description 
        ORDER BY total_revenue DESC 
        LIMIT 15
    ");
    
    $products = [];
    
    // Gesamtumsatz berechnen
    $total_stmt = $pdo->query("
        SELECT COALESCE(SUM(price * quantity), 0) 
        FROM pos_sales 
        $whereClause
    ");
    $total_revenue = $total_stmt->fetchColumn();
    
    $rank = 1;
    while ($row = $stmt->fetch()) {
        $products[] = [
            'rank' => $rank++,
            'name' => $row['product_description'],
            'quantity' => (int)$row['total_quantity'],
            'revenue' => (float)$row['total_revenue'],
            'transaction_count' => (int)$row['transaction_count'],
            'percentage' => $total_revenue > 0 ? ($row['total_revenue'] / $total_revenue) * 100 : 0
        ];
    }
    
    return $products;
}

function getBonAnalysis($pdo, $year = null) {
    $whereClause = $year ? "WHERE YEAR(transaction_date) = " . intval($year) : "";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN item_count = 1 THEN 1 END) as single_item_bons,
            COUNT(CASE WHEN item_count > 1 THEN 1 END) as multi_item_bons,
            COALESCE(AVG(item_count), 0) as avg_items_per_bon,
            COALESCE(MAX(item_count), 0) as max_items_per_bon,
            COUNT(*) as total_bons
        FROM (
            SELECT COUNT(*) as item_count
            FROM pos_sales 
            $whereClause
            GROUP BY receipt_number, transaction_date
        ) as bon_counts
    ");
    
    $analysis = $stmt->fetch();
    
    return [
        'single_item_bons' => (int)$analysis['single_item_bons'],
        'multi_item_bons' => (int)$analysis['multi_item_bons'],
        'avg_items_per_bon' => (float)$analysis['avg_items_per_bon'],
        'max_items_per_bon' => (int)$analysis['max_items_per_bon'],
        'total_bons' => (int)$analysis['total_bons'],
        'multi_item_percentage' => $analysis['total_bons'] > 0 ? 
            ($analysis['multi_item_bons'] / $analysis['total_bons']) * 100 : 0
    ];
}

function getAvailableYears($pdo, $hasTransactionType = false) {
    if ($hasTransactionType) {
        $stmt = $pdo->query("
            SELECT DISTINCT YEAR(transaction_date) as year,
                   COUNT(*) as transaction_count,
                   SUM(CASE WHEN transaction_type = 'einnahme' THEN price * quantity ELSE 0 END) as year_revenue
            FROM pos_sales 
            GROUP BY YEAR(transaction_date)
            ORDER BY year DESC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT DISTINCT YEAR(transaction_date) as year,
                   COUNT(*) as transaction_count,
                   SUM(CASE 
                       WHEN price > 0 
                       AND product_description NOT LIKE '%Einlage%' 
                       AND product_description NOT LIKE '%Entnahme%'
                       THEN price * quantity 
                       ELSE 0 
                   END) as year_revenue
            FROM pos_sales 
            GROUP BY YEAR(transaction_date)
            ORDER BY year DESC
        ");
    }
    
    $years = [];
    while ($row = $stmt->fetch()) {
        $years[] = [
            'year' => (int)$row['year'],
            'transaction_count' => (int)$row['transaction_count'],
            'revenue' => (float)$row['year_revenue']
        ];
    }
    
    return $years;
}

function getImportStatus($pdo) {
    $stmt = $pdo->query("
        SELECT filename, import_date, total_rows, imported_rows, status, error_rows
        FROM pos_import_log 
        ORDER BY import_date DESC 
        LIMIT 5
    ");
    
    $imports = [];
    while ($row = $stmt->fetch()) {
        $success_rate = $row['total_rows'] > 0 ? 
            ($row['imported_rows'] / $row['total_rows']) * 100 : 0;
            
        $imports[] = [
            'filename' => $row['filename'],
            'import_date' => $row['import_date'],
            'total_rows' => (int)$row['total_rows'],
            'imported_rows' => (int)$row['imported_rows'],
            'error_rows' => (int)($row['error_rows'] ?? 0),
            'status' => $row['status'],
            'success_rate' => $success_rate
        ];
    }
    
    return $imports;
}
?>