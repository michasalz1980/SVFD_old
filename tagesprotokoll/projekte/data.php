<?php
    require 'config.php';
	use \RedBeanPHP\R as R;
    // Connect to MySQL database
    $strMySql = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    R::setup($strMySql, DB_USER, DB_PASS);

	// SQL Abfrage : Ausgaben pro Projekt
	$sql = 'SELECT 
			CONCAT(pr.projectNumber, " (", pr.shortDescription, ")") AS projectNumber,
			DATE_FORMAT(pr.start, "%d.%m.%y") AS StartDate,
			DATE_FORMAT(pr.end, "%d.%m.%y") AS EndDate,
			pr.budget AS budget,
			SUM(re.brutto) AS Brutto,
			(pr.budget - SUM(re.brutto)) AS RestBudget
		FROM 
			svfd_schedule.ffd_projects pr
		LEFT JOIN 
			svfd_schedule.ffd_rechnungen re ON re.project_id = pr.id AND re.posted IN (0,1)
		GROUP BY 
			pr.projectNumber;';
    $aRechnungenSummary = R::getAll($sql);

	// SQL Abfrage : Alle Ausgaben
	$sql = 'SELECT re.id AS id, su.name AS Lieferant, DATE_FORMAT(datetime,"%d.%m.%Y") AS Datum, number AS Rechnungsnummer, brutto AS Brutto, CONCAT(pr.projectNumber," (", pr.shortDescription, ")") AS projectNumber, posted
			FROM svfd_schedule.ffd_rechnungen re, svfd_schedule.ffd_supplier su, svfd_schedule.ffd_projects pr
			WHERE re.supplier_id = su.id AND re.project_id = pr.id';
    $aRechnungenDetail = R::getAll($sql);
	
	// SQL Abfrage : Ausgaben pro Lieferant
	$sql = 'SELECT su.name AS supplier, street, CONCAT(zip, " ", city) AS location, SUM(brutto) AS Brutto
			FROM svfd_schedule.ffd_rechnungen re, svfd_schedule.ffd_supplier su
			WHERE re.supplier_id = su.id
			GROUP BY su.name;';
    $aSupplierProject = R::getAll($sql);
	
	// SQL Abfrage : Cash Flow pro Monat und Projekt für das aktuelle Jahr
	$currentYear = date('Y');
	$sql = "SELECT pr.projectNumber, pr.shortDescription, DATE_FORMAT(datetime, '%Y/%m') AS month, IFNULL(SUM(brutto), 0) AS Brutto
			FROM svfd_schedule.ffd_rechnungen re
			RIGHT JOIN svfd_schedule.ffd_projects pr ON re.project_id = pr.id AND YEAR(datetime) = {$currentYear}
			WHERE re.posted IN (0,1)
			GROUP BY pr.projectNumber, month
			ORDER BY pr.projectNumber, month";

	$aCashFlow = R::getAll($sql);

	// Erstellen der Datenstruktur für das Diagramm
	$months = array_map(function($m) use ($currentYear) { return "{$currentYear}/" . str_pad($m, 2, '0', STR_PAD_LEFT); }, range(1, 12));
	$projectCashFlowData = [];

	foreach($aCashFlow as $row) {
		$projectNumber = $row['projectNumber'] . ' (' . $row['shortDescription'] . ')';
		if (!array_key_exists($projectNumber, $projectCashFlowData)) {
			$projectCashFlowData[$projectNumber] = array_fill_keys($months, ['y' => 0.0]);
		}
		$projectCashFlowData[$projectNumber][$row['month']] = ['y' => floatval($row['Brutto'])];
	}

	// Umwandeln in das erwartete Format
	$chartData = [];
	foreach($projectCashFlowData as $projectName => $dataPoints) {
		$chartData[] = [
			'type' => 'stackedColumn',
			'name' => $projectName,
			'showInLegend' => 'true',
			'dataPoints' => array_values(array_map(function($month) use ($dataPoints) {
				return ['label' => $month, 'y' => $dataPoints[$month]['y']];
			}, array_keys($dataPoints)))
		];
	}

	// Hinzufügen der Datenstruktur für die Ausgabe auf index.php
	echo '<script type="text/javascript">';
	echo 'var chartData = ' . json_encode($chartData) . ';';
	echo '</script>';

    R::close();
?>
