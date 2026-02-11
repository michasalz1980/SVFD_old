<?php
/**
 * Duplikat-Analyse für SV Freibad Import-System
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "🔍 SV Freibad - Duplikat-Analyse\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // 1. Grundlegende Statistiken
    echo "📊 DATENBANK-ÜBERSICHT:\n";
    echo str_repeat("-", 30) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM pos_sales");
    $total_records = $stmt->fetchColumn();
    echo "Gesamte Verkaufsdatensätze: " . number_format($total_records) . "\n";
    
    $stmt = $pdo->query("SELECT MIN(transaction_date) as oldest, MAX(transaction_date) as newest FROM pos_sales");
    $timespan = $stmt->fetch();
    echo "Zeitraum: {$timespan['oldest']} bis {$timespan['newest']}\n";
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT receipt_number) FROM pos_sales");
    $unique_receipts = $stmt->fetchColumn();
    echo "Eindeutige Belegnummern: " . number_format($unique_receipts) . "\n";
    
    $stmt = $pdo->query("SELECT SUM(price) FROM pos_sales");
    $total_revenue = $stmt->fetchColumn();
    echo "Gesamtumsatz: " . number_format($total_revenue, 2) . " €\n";
    
    // 2. Import-Historie
    echo "\n📋 IMPORT-HISTORIE (letzte 10):\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->query("
        SELECT filename, import_date, total_rows, imported_rows, error_rows, status 
        FROM pos_import_log 
        ORDER BY import_date DESC 
        LIMIT 10
    ");
    $imports = $stmt->fetchAll();
    
    foreach ($imports as $import) {
        $duplicate_rate = 0;
        if ($import['total_rows'] > 0) {
            $duplicate_rate = (1 - ($import['imported_rows'] / $import['total_rows'])) * 100;
        }
        
        echo sprintf(
            "%s: %s (%d/%d Zeilen, %.1f%% Duplikate) [%s]\n",
            $import['import_date'],
            $import['filename'],
            $import['imported_rows'],
            $import['total_rows'],
            $duplicate_rate,
            $import['status']
        );
    }
    
    // 3. Mögliche echte Duplikate finden
    echo "\n🔍 ECHTE DUPLIKATE SUCHEN:\n";
    echo str_repeat("-", 35) . "\n";
    
    $stmt = $pdo->query("
        SELECT receipt_number, transaction_date, COUNT(*) as count,
               GROUP_CONCAT(sale_id) as sale_ids,
               MAX(price) as price, MAX(product_description) as product
        FROM pos_sales 
        GROUP BY receipt_number, transaction_date 
        HAVING COUNT(*) > 1
        LIMIT 10
    ");
    $real_duplicates = $stmt->fetchAll();
    
    if (empty($real_duplicates)) {
        echo "✅ Keine echten Duplikate gefunden - Index funktioniert korrekt!\n";
    } else {
        echo "⚠️ Gefundene echte Duplikate:\n";
        foreach ($real_duplicates as $dup) {
            echo "   Beleg {$dup['receipt_number']} am {$dup['transaction_date']}: {$dup['count']}x (IDs: {$dup['sale_ids']})\n";
        }
    }
    
    // 4. Tägliche Verkäufe
    echo "\n📅 VERKÄUFE PRO TAG (letzte 7 Tage):\n";
    echo str_repeat("-", 45) . "\n";
    
    $stmt = $pdo->query("
        SELECT DATE(transaction_date) as sale_date,
               COUNT(*) as transactions,
               SUM(price) as daily_revenue,
               COUNT(DISTINCT receipt_number) as unique_receipts
        FROM pos_sales 
        WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(transaction_date)
        ORDER BY sale_date DESC
    ");
    $daily_sales = $stmt->fetchAll();
    
    foreach ($daily_sales as $day) {
        echo sprintf(
            "%s: %d Transaktionen, %.2f €, %d Belege\n",
            $day['sale_date'],
            $day['transactions'],
            $day['daily_revenue'],
            $day['unique_receipts']
        );
    }
    
    // 5. Empfehlungen
    echo "\n💡 EMPFEHLUNGEN:\n";
    echo str_repeat("-", 20) . "\n";
    
    if (!empty($imports)) {
        $last_import = $imports[0];
        $last_duplicate_rate = 0;
        if ($last_import['total_rows'] > 0) {
            $last_duplicate_rate = (1 - ($last_import['imported_rows'] / $last_import['total_rows'])) * 100;
        }
        
        if ($last_duplicate_rate > 95) {
            echo "✅ Sehr hohe Duplikatsrate - das ist normal bei wiederholten Importen\n";
            echo "   Die Datei wurde wahrscheinlich schon einmal importiert\n";
        } elseif ($last_duplicate_rate > 50) {
            echo "⚠️ Mittlere Duplikatsrate - teilweise bereits importierte Daten\n";
            echo "   Möglicherweise überlappende Zeiträume in CSV-Dateien\n";
        } else {
            echo "✅ Niedrige Duplikatsrate - größtenteils neue Daten\n";
        }
    }
    
    echo "\n🔧 NÄCHSTE SCHRITTE:\n";
    echo "1. Für kompletten Neuimport: Datenbank leeren mit TRUNCATE pos_sales\n";
    echo "2. Für teilweisen Import: Zeitraum in CSV eingrenzen\n";
    echo "3. Aktueller Zustand beibehalten (empfohlen)\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
?>