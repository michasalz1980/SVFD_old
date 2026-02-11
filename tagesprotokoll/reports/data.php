<?php
    require 'config.php';
    
    // Use-Statement muss ganz oben stehen
    if (file_exists('../../schedule/vendor/autoload.php')) {
        require '../../schedule/vendor/autoload.php';
    }
    
    // Globale Variablen für Datenbankverbindung
    $useRedBean = false;
    $pdo = null;
    
    // Prüfe ob RedBeanPHP verfügbar ist
    try {
        if (class_exists('\RedBeanPHP\R')) {
            $strMySql = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
            \RedBeanPHP\R::setup($strMySql, DB_USER, DB_PASS);
            
            $useRedBean = true;
            echo "<!-- DEBUG: RedBeanPHP loaded successfully -->\n";
        } else {
            throw new Exception("RedBeanPHP class not found");
        }
        
    } catch (Exception $e) {
        $useRedBean = false;
        echo "<!-- INFO: RedBeanPHP not available: " . $e->getMessage() . " -->\n";
        echo "<!-- INFO: Using PDO fallback -->\n";
        
        // PDO Fallback
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            echo "<!-- DEBUG: PDO connection established -->\n";
        } catch (PDOException $e) {
            echo "<!-- FATAL: Database connection failed: " . $e->getMessage() . " -->\n";
            // Fallback zu Dummy-Daten
            $aLastgaengeProJahr = array();
            $aLastgaengeProMonat = array();
            $aLastgaengeProTag = array();
            $apvFeedInPerMonth = array();
            $aLastgaengeProSaison = array();
            return;
        }
    }

    // Funktion für Datenbankabfragen
    function executeQuery($sql) {
        global $useRedBean, $pdo;
        
        if ($useRedBean && class_exists('\RedBeanPHP\R')) {
            return \RedBeanPHP\R::getAll($sql);
        } else if ($pdo) {
            try {
                $stmt = $pdo->query($sql);
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                echo "<!-- ERROR in query: " . $e->getMessage() . " -->\n";
                return array();
            }
        } else {
            echo "<!-- ERROR: No database connection available -->\n";
            return array();
        }
    }

    // SQL Abfrage Lastgänge pro Tag
    $sql = 'SELECT DATE_FORMAT(`date`, "%d.%m.%Y") AS Datum, DATE_FORMAT(`date`, "%u") AS Kalenderwoche, ROUND(SUM(`value` * 0.25), 0) AS Verbrauch FROM ffd_lastgang WHERE `date` != "0000-00-00" GROUP BY `date` ORDER BY `date` DESC';
    $aLastgaengeProTag = executeQuery($sql);
    
    // SQL Abfrage Lastgänge pro Monat
    $sql = 'SELECT DATE_FORMAT(`date`, "%Y-%m") AS Monat, ROUND(SUM(`value` * 0.25), 0) AS Verbrauch FROM ffd_lastgang WHERE `date` != "0000-00-00" GROUP BY DATE_FORMAT(`date`, "%Y-%m") ORDER BY `date` DESC';
    $aLastgaengeProMonat = executeQuery($sql);

    // SQL Abfrage Lastgänge pro Jahr
    $sql = 'SELECT YEAR(`date`) AS Jahr, ROUND(SUM(`value` * 0.25), 0) AS Verbrauch FROM ffd_lastgang WHERE `date` != "0000-00-00" AND YEAR(`date`) > 0 GROUP BY YEAR(`date`) ORDER BY `date` DESC';
    $aLastgaengeProJahr = executeQuery($sql);

    // SQL Abfrage PV-Einspeisung pro Monat
    $sql = 'SELECT DATE_FORMAT(`Datetime`, "%Y-%m") AS Monat, ROUND(`value`, 0) AS Verbrauch FROM ffd_pvFeedIn WHERE `Datetime` IS NOT NULL ORDER BY `Datetime` DESC';
    $apvFeedInPerMonth = executeQuery($sql);
    
    // SQL Abfrage Lastgänge pro Saison (mit Fehlerbehandlung)
    try {
        $sql = 'SELECT
                    YEAR(`ffd_seasonweeks`.`date`) AS Saison,
                    DATE_FORMAT(MIN(`ffd_seasonweeks`.`date`), "%d.%m.%Y") AS Start,
                    DATE_FORMAT(MAX(`ffd_seasonweeks`.`date`), "%d.%m.%Y") AS Ende,
                    (SELECT COUNT(*)
                     FROM `ffd_seasonweeks` AS sw
                     WHERE sw.`closed` = "Offen"
                       AND YEAR(sw.`date`) = YEAR(`ffd_seasonweeks`.`date`)) AS Anzahl,
                    CONCAT(ROUND(SUM(`ffd_lastgang`.`value` * 0.25), 0), " kWh") AS Verbrauch,
                    CONCAT(ROUND(SUM(`ffd_lastgang`.`value` * 0.25) / COUNT(DISTINCT `ffd_lastgang`.`date`), 0), " kWh") AS `Verbrauch pro Tag`
                FROM
                    `ffd_seasonweeks`
                INNER JOIN `ffd_lastgang` ON `ffd_seasonweeks`.`date` = `ffd_lastgang`.`date`
                GROUP BY YEAR(`ffd_seasonweeks`.`date`)
                ORDER BY MIN(`ffd_seasonweeks`.`date`) DESC';
        $aLastgaengeProSaison = executeQuery($sql);
    } catch (Exception $e) {
        echo "<!-- INFO: Season data not available: " . $e->getMessage() . " -->\n";
        $aLastgaengeProSaison = array();
    }
    
    // Schließe Verbindung
    if ($useRedBean && class_exists('\RedBeanPHP\R')) {
        \RedBeanPHP\R::close();
    }
    
    echo "<!-- DEBUG SUMMARY: -->\n";
    echo "<!-- Lastgänge Jahr: " . count($aLastgaengeProJahr) . " entries -->\n";
    echo "<!-- Lastgänge Monat: " . count($aLastgaengeProMonat) . " entries -->\n";
    echo "<!-- PV Feed-In: " . count($apvFeedInPerMonth) . " entries -->\n";
?>