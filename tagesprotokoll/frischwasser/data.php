<?php
    require 'config.php';
    use \RedBeanPHP\R as R;
    // Connect to MySQL database
    $strMySql = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    R::setup($strMySql, DB_USER, DB_PASS);

    // Startzeit der Query
    $startzeit = microtime(true);

    // Optimierte SQL-Abfrage
    $sql = 'SELECT
            DATE_FORMAT(f.`datetime`, "%d.%m.%Y") AS Datum,
            MAX(f.`counter`)/1000 AS `Zählerstand`,
            SUM(f.`consumption`)/1000 AS `Verbrauch (in m³)`,
            SUM(IF(HOUR(f.`datetime`) BETWEEN 9 AND 20, f.`consumption`, 0))/1000 AS `Verbrauch (in m³) (09:00 - 20:00)`,
            SUM(IF(HOUR(f.`datetime`) NOT BETWEEN 9 AND 20, f.`consumption`, 0))/1000 AS `Verbrauch (in m³) (Rest)`,
            tp.`Tagesbesucherzahl` AS `Anzahl Besucher`,
            IFNULL(tp.`Tagesbesucherzahl`, 0) AS `Anzahl Besucher`,
            IF(tp.`Tagesbesucherzahl` IS NULL OR tp.`Tagesbesucherzahl` = 0, 0, (SUM(f.`consumption`)/1000) / tp.`Tagesbesucherzahl`) AS `Verbrauch pro Besucher`
        FROM
            `ffd_frischwasser` f
        LEFT JOIN
            `Tagesprotokoll` tp ON DATE(f.`datetime`) = tp.`Datum`
        WHERE
            f.`datetime` > "2024-07-18"
        GROUP BY
            DATE(f.`datetime`)
        ORDER BY
            f.`datetime` DESC';
    $aLastgaengeProTag = R::getAll($sql);

    // Gesamtverbrauch und Gesamtbesucherzahl berechnen
    $sql_total = 'SELECT
            SUM(f.`consumption`)/1000 AS `Gesamtverbrauch`,
            SUM(tp.`Tagesbesucherzahl`) AS `Gesamtbesucherzahl`
        FROM
            `ffd_frischwasser` f
        LEFT JOIN
            `Tagesprotokoll` tp ON DATE(f.`datetime`) = tp.`Datum`
        WHERE
            f.`datetime` > "2024-07-18"';
    $result_total = R::getRow($sql_total);

    $gesamtverbrauch = $result_total['Gesamtverbrauch'];
    $gesamtbesucherzahl = $result_total['Gesamtbesucherzahl'];
    $verbrauch_pro_besucher = $gesamtbesucherzahl > 0 ? $gesamtverbrauch / $gesamtbesucherzahl : 0;

    // Endzeit der Query
    $endzeit = microtime(true);

    // Berechnung der Ladezeit
    $ladezeit = $endzeit - $startzeit;

    R::close();
?>
