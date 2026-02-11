<?php
/**
 * Validierungs- und Duplikat-Check Helper
 * SV Freibad Dabringhausen e.V.
 */

class ValidationHelper {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Erweiterte Duplikat-Prüfung mit verschiedenen Methoden
     */
    public function checkDuplicates($row, $methods = ['receipt_date', 'transaction_hash']) {
        foreach ($methods as $method) {
            switch ($method) {
                case 'receipt_date':
                    if ($this->checkDuplicateByReceiptAndDate($row)) {
                        return ['is_duplicate' => true, 'method' => 'receipt_date'];
                    }
                    break;
                    
                case 'transaction_hash':
                    if ($this->checkDuplicateByTransactionHash($row)) {
                        return ['is_duplicate' => true, 'method' => 'transaction_hash'];
                    }
                    break;
                    
                case 'fuzzy_match':
                    $fuzzy_result = $this->checkFuzzyDuplicate($row);
                    if ($fuzzy_result['is_duplicate']) {
                        return $fuzzy_result;
                    }
                    break;
            }
        }
        
        return ['is_duplicate' => false, 'method' => null];
    }
    
    /**
     * Duplikat-Prüfung über Belegnummer und Datum (exakt)
     */
    private function checkDuplicateByReceiptAndDate($row) {
        $sql = "SELECT COUNT(*) FROM pos_sales 
                WHERE receipt_number = ? 
                AND DATE(transaction_date) = DATE(?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $row['receipt_number'], 
            $row['transaction_date']->format('Y-m-d H:i:s')
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Duplikat-Prüfung über Transaction-Hash
     */
    private function checkDuplicateByTransactionHash($row) {
        $hash = $this->generateTransactionHash($row);
        
        $sql = "SELECT COUNT(*) FROM pos_sales 
                WHERE MD5(CONCAT(receipt_number, transaction_date, price, product_description)) = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hash]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Fuzzy Duplicate Check für ähnliche Transaktionen
     */
    private function checkFuzzyDuplicate($row) {
        // Suche nach ähnlichen Transaktionen in einem Zeitfenster von ±2 Minuten
        $start_time = clone $row['transaction_date'];
        $start_time->modify('-2 minutes');
        $end_time = clone $row['transaction_date'];
        $end_time->modify('+2 minutes');
        
        $sql = "SELECT sale_id, receipt_number, price, product_description 
                FROM pos_sales 
                WHERE transaction_date BETWEEN ? AND ? 
                AND ABS(price - ?) < 0.01 
                AND product_description = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $start_time->format('Y-m-d H:i:s'),
            $end_time->format('Y-m-d H:i:s'),
            $row['price'],
            $row['product_description']
        ]);
        
        $similar_transactions = $stmt->fetchAll();
        
        if (count($similar_transactions) > 0) {
            return [
                'is_duplicate' => true, 
                'method' => 'fuzzy_match',
                'similar_count' => count($similar_transactions),
                'details' => $similar_transactions
            ];
        }
        
        return ['is_duplicate' => false, 'method' => 'fuzzy_match'];
    }
    
    /**
     * Transaction-Hash generieren
     */
    private function generateTransactionHash($row) {
        $data = $row['receipt_number'] . 
                $row['transaction_date']->format('Y-m-d H:i:s') . 
                $row['price'] . 
                $row['product_description'];
        
        return md5($data);
    }
    
    /**
     * Erweiterte Datumsvalidierung
     */
    public function validateDate($datetime_obj, $min_date = null, $max_date = null) {
        if (!$datetime_obj instanceof DateTime) {
            return ['valid' => false, 'error' => 'Ungültiges Datumsobjekt'];
        }
        
        // Minimales Datum prüfen (z.B. nicht vor 2020)
        if ($min_date && $datetime_obj < $min_date) {
            return ['valid' => false, 'error' => 'Datum zu alt'];
        }
        
        // Maximales Datum prüfen (z.B. nicht in der Zukunft)
        if ($max_date && $datetime_obj > $max_date) {
            return ['valid' => false, 'error' => 'Datum in der Zukunft'];
        }
        
        // Geschäftszeiten prüfen (z.B. Freibad nicht nachts geöffnet)
        $hour = intval($datetime_obj->format('H'));
        if ($hour < 6 || $hour > 22) {
            return ['valid' => false, 'error' => 'Ungewöhnliche Uhrzeit'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Erweiterte Preisvalidierung
     */
    public function validatePrice($price, $product_description = '') {
        // Grundvalidierung
        if (!is_numeric($price)) {
            return ['valid' => false, 'error' => 'Preis ist nicht numerisch'];
        }
        
        $price = floatval($price);
        
        // Negative Preise nur bei bestimmten Produkten erlauben
        if ($price < 0) {
            $allowed_negative = ['Entnahme', 'Storno', 'Rückgabe'];
            $is_allowed = false;
            
            foreach ($allowed_negative as $keyword) {
                if (stripos($product_description, $keyword) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if (!$is_allowed && !ALLOW_NEGATIVE_PRICES) {
                return ['valid' => false, 'error' => 'Negativer Preis nicht erlaubt'];
            }
        }
        
        // Unrealistisch hohe Preise prüfen
        if ($price > 1000) {
            return ['valid' => false, 'error' => 'Preis ungewöhnlich hoch', 'warning' => true];
        }
        
        // Cent-Beträge prüfen (sollten durch 0.05 teilbar sein für deutsche Rundungsregeln)
        $cents = round(($price - floor($price)) * 100);
        if ($cents % 5 !== 0) {
            return ['valid' => true, 'error' => null, 'warning' => 'Ungewöhnlicher Cent-Betrag'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Produktbeschreibung validieren
     */
    public function validateProductDescription($description) {
        $description = trim($description);
        
        if (empty($description)) {
            return ['valid' => false, 'error' => 'Produktbeschreibung leer'];
        }
        
        if (strlen($description) > 255) {
            return ['valid' => false, 'error' => 'Produktbeschreibung zu lang'];
        }
        
        // Verdächtige Zeichen prüfen
        if (preg_match('/[<>\"\'&]/', $description)) {
            return ['valid' => true, 'error' => null, 'warning' => 'Verdächtige Zeichen in Beschreibung'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Belegnummer validieren
     */
    public function validateReceiptNumber($receipt_number) {
        $receipt_number = trim($receipt_number);
        
        if (empty($receipt_number)) {
            return ['valid' => false, 'error' => 'Belegnummer leer'];
        }
        
        // Format prüfen (z.B. 5-stellige Nummer)
        if (!preg_match('/^\d{5}$/', $receipt_number)) {
            return ['valid' => false, 'error' => 'Ungültiges Belegnummer-Format'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Zahlungsart validieren
     */
    public function validatePaymentMethod($payment_method) {
        $allowed_methods = ['Bar', 'EC-Cash', 'Kreditkarte', 'PayPal'];
        
        if (!in_array($payment_method, $allowed_methods)) {
            return ['valid' => false, 'error' => 'Unbekannte Zahlungsart: ' . $payment_method];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Komplette Zeilen-Validierung
     */
    public function validateCompleteRow($row) {
        $errors = [];
        $warnings = [];
        
        // Datum validieren
        $date_validation = $this->validateDate($row['transaction_date']);
        if (!$date_validation['valid']) {
            $errors[] = 'Datum: ' . $date_validation['error'];
        }
        
        // Preis validieren
        $price_validation = $this->validatePrice($row['price'], $row['product_description']);
        if (!$price_validation['valid']) {
            $errors[] = 'Preis: ' . $price_validation['error'];
        } elseif (isset($price_validation['warning'])) {
            $warnings[] = 'Preis: ' . $price_validation['warning'];
        }
        
        // Produktbeschreibung validieren
        $product_validation = $this->validateProductDescription($row['product_description']);
        if (!$product_validation['valid']) {
            $errors[] = 'Produkt: ' . $product_validation['error'];
        } elseif (isset($product_validation['warning'])) {
            $warnings[] = 'Produkt: ' . $product_validation['warning'];
        }
        
        // Belegnummer validieren
        $receipt_validation = $this->validateReceiptNumber($row['receipt_number']);
        if (!$receipt_validation['valid']) {
            $errors[] = 'Belegnummer: ' . $receipt_validation['error'];
        }
        
        // Zahlungsart validieren
        $payment_validation = $this->validatePaymentMethod($row['payment_method']);
        if (!$payment_validation['valid']) {
            $errors[] = 'Zahlungsart: ' . $payment_validation['error'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
?>