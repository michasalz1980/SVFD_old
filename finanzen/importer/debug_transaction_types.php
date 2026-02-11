        if (!$hasTransactionType) {
            $stmt = $pdo->query("
                SELECT 
                    $primaryKey as id,
                    transaction_date,
                    price,
                    quantity,
                    product_description,
                    receipt_number,
                    (price * quantity) as total_amount
                FROM pos_sales 
                WHERE YEAR(transaction_date) = $year
                AND price<?php
/**
 * Debug Script für Transaktionstypen-Analyse
 * Freibad Dabringhausen e.V.
 * 
 * Zeigt alle Transaktionen und ihre automatische Kategorisierung an
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    echo "<h1>🔍 Debug: Transaktionstypen-Analyse</h1>";
    echo "<p>Überprüft die automatische Kategorisierung aller Transaktionen</p>";
    
    // Prüfen ob transaction_type Spalte existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM pos_sales LIKE 'transaction_type'");
    $hasTransactionType = $stmt->rowCount() > 0;
    
    echo "<h2>📊 System-Status</h2>";
    echo "<p><strong>transaction_type Spalte:</strong> " . ($hasTransactionType ? "✅ Vorhanden" : "❌ Nicht vorhanden") . "</p>";
    
    // Tabellenstruktur anzeigen
    echo "<h3>🔍 Tabellenstruktur pos_sales:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM pos_sales");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $columns = [];
    while ($column = $stmt->fetch()) {
        $columns[] = $column['Field'];
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Primary Key ist sale_id (aus der Struktur ersichtlich)
    $primaryKey = 'sale_id';
    echo "<p><strong>Verwendet als Primary Key:</strong> $primaryKey</p>";
    
    // Prüfen der transaction_type Verteilung
    echo "<h3>📊 Aktuelle transaction_type Verteilung:</h3>";
    $typeStmt = $pdo->query("
        SELECT transaction_type, COUNT(*) as count, SUM(price * quantity) as total_amount 
        FROM pos_sales 
        WHERE YEAR(transaction_date) = 2025
        GROUP BY transaction_type
    ");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Typ</th><th>Anzahl</th><th>Gesamtbetrag</th></tr>";
    while ($typeRow = $typeStmt->fetch()) {
        echo "<tr>";
        echo "<td><strong>{$typeRow['transaction_type']}</strong></td>";
        echo "<td>{$typeRow['count']}</td>";
        echo "<td>" . number_format($typeRow['total_amount'], 2, ',', '.') . " €</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Alle Transaktionen für 2025 abrufen
    $year = 2025;
    
    if ($hasTransactionType) {
        // Mit transaction_type Spalte
        $stmt = $pdo->query("
            SELECT 
                $primaryKey as id,
                transaction_date,
                price,
                quantity,
                product_description,
                receipt_number,
                transaction_type,
                (price * quantity) as total_amount
            FROM pos_sales 
            WHERE YEAR(transaction_date) = $year
            ORDER BY transaction_date DESC
        ");
        
        echo "<h2>📋 Alle Transaktionen $year (mit transaction_type)</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>ID</th>
                <th>Datum</th>
                <th>Beschreibung</th>
                <th>Preis</th>
                <th>Menge</th>
                <th>Gesamt</th>
                <th>Typ (DB)</th>
                <th>Sollte sein</th>
                <th>Beleg</th>
              </tr>";
        
        $totals = ['einlage' => 0, 'einnahme' => 0, 'entnahme' => 0];
        $incorrectCount = 0;
        $totalRows = 0;
        
        while ($row = $stmt->fetch()) {
            $amount = $row['total_amount'];
            $dbType = $row['transaction_type'];
            
            // Bestimme was der korrekte Typ sein sollte
            $description = strtolower($row['product_description']);
            $correctType = 'einnahme'; // Default
            
            if ((strpos($description, 'einlage:') !== false || 
                 strpos($description, 'anfangsbestand') !== false) && $row['price'] > 0) {
                $correctType = 'einlage';
            } elseif (strpos($description, 'entnahme:') !== false || 
                      strpos($description, 'entnahme ') !== false || 
                      $row['price'] < 0) {
                $correctType = 'entnahme';
            }
            
            // Prüfe ob korrekt kategorisiert
            $isCorrect = ($dbType === $correctType);
            if (!$isCorrect) $incorrectCount++;
            $totalRows++;
            
            // Für Entnahmen: Absoluter Wert anzeigen
            $displayAmount = ($correctType === 'entnahme') ? abs($amount) : $amount;
            $totals[$correctType] += $displayAmount;
            
            $bgColor = '';
            switch ($correctType) {
                case 'einlage': $bgColor = 'background: #d4edda;'; break;
                case 'einnahme': $bgColor = 'background: #d1ecf1;'; break;
                case 'entnahme': $bgColor = 'background: #f8d7da;'; break;
            }
            
            if (!$isCorrect) {
                $bgColor = 'background: #ffe6e6; border: 2px solid red;'; // Fehlerhafte Kategorisierung
            }
            
            echo "<tr style='$bgColor'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($row['transaction_date'])) . "</td>";
            echo "<td style='max-width: 300px; word-wrap: break-word;'>" . htmlspecialchars($row['product_description']) . "</td>";
            echo "<td>" . number_format($row['price'], 2, ',', '.') . " €</td>";
            echo "<td>{$row['quantity']}</td>";
            echo "<td>" . number_format($amount, 2, ',', '.') . " €</td>";
            echo "<td><strong>$dbType</strong></td>";
            echo "<td style='font-weight: bold; color: " . ($isCorrect ? 'green' : 'red') . ";'>$correctType</td>";
            echo "<td>{$row['receipt_number']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>⚠️ Kategorisierungs-Probleme:</h3>";
        echo "<p><strong>Falsch kategorisierte Transaktionen:</strong> $incorrectCount von $totalRows</p>";
        
    } else {
        echo "<p>❌ transaction_type Spalte nicht gefunden (sollte nicht passieren)</p>";
    }
    
    // Zusammenfassung
    echo "<h2>💰 Zusammenfassung $year</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Typ</th><th>Betrag</th></tr>";
    
    foreach ($totals as $type => $amount) {
        $bgColor = '';
        switch ($type) {
            case 'einlage': $bgColor = 'background: #d4edda;'; break;
            case 'einnahme': $bgColor = 'background: #d1ecf1;'; break;
            case 'entnahme': $bgColor = 'background: #f8d7da;'; break;
            case 'unbekannt': $bgColor = 'background: #fff3cd;'; break;
        }
        
        echo "<tr style='$bgColor'>";
        echo "<td><strong>" . ucfirst($type) . "n</strong></td>";
        echo "<td><strong>" . number_format($amount, 2, ',', '.') . " €</strong></td>";
        echo "</tr>";
    }
    
    // Nettoergebnis
    $netResult = $totals['einlage'] + $totals['einnahme'] - $totals['entnahme'];
    echo "<tr style='background: #e2e6ff; border-top: 2px solid #333;'>";
    echo "<td><strong>Nettoergebnis</strong></td>";
    echo "<td><strong>" . number_format($netResult, 2, ',', '.') . " €</strong></td>";
    echo "</tr>";
    
    echo "</table>";
    
    // Vergleich mit Dashboard-Werten
    echo "<h2>⚠️ Problem-Analyse</h2>";
    echo "<p><strong>Dashboard zeigt Einnahmen:</strong> 3.810,00 €</p>";
    echo "<p><strong>Tatsächliche Einnahmen:</strong> " . number_format($totals['einnahme'], 2, ',', '.') . " €</p>";
    echo "<p><strong>Erwartete Einnahmen (CSV):</strong> 2.860,00 €</p>";
    
    $difference = $totals['einnahme'] - 2860;
    echo "<p><strong>Differenz:</strong> " . number_format($difference, 2, ',', '.') . " €</p>";
    
    if ($difference > 0) {
        echo "<p style='color: red;'>⚠️ Es werden " . number_format($difference, 2, ',', '.') . " € zu viel als Einnahmen kategorisiert!</p>";
        
        // Verdächtige Transaktionen finden
        echo "<h3>🔍 Verdächtige Transaktionen (möglicherweise falsch kategorisiert):</h3>";
        
        if (!$hasTransactionType) {
            $stmt = $pdo->query("
                SELECT 
                    id,
                    transaction_date,
                    price,
                    quantity,
                    product_description,
                    receipt_number,
                    (price * quantity) as total_amount
                FROM pos_sales 
                WHERE YEAR(transaction_date) = $year
                AND price > 0 
                AND product_description NOT LIKE '%Einlage%' 
                AND product_description NOT LIKE '%Anfangsbestand%'
                AND product_description NOT LIKE '%Entnahme%'
                AND (
                    product_description LIKE '%Storno%' 
                    OR product_description LIKE '%Korrektur%'
                    OR product_description LIKE '%Rückgabe%'
                    OR product_description LIKE '%Fehler%'
                    OR price > 1000
                )
                ORDER BY total_amount DESC
            ");
            
            while ($row = $stmt->fetch()) {
                echo "<p>🚨 ID {$row['id']}: {$row['product_description']} - " . 
                     number_format($row['total_amount'], 2, ',', '.') . " €</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<h1>❌ Fehler</h1>";
    echo "<p>Fehler beim Laden der Daten: " . $e->getMessage() . "</p>";
}
?>