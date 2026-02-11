<?php require 'data.php'; ?>
<html lang="de">
<head>
    <title>Freibad Dabringhausen - Energie - Auswertung</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <!-- CSS-Block -->
    <?php include 'styles.css'; ?>

    <!-- Latest compiled and minified JavaScript -->
    <?php include 'scripts.js'; ?>

    <!-- JavaScript-Block -->
    <script>
		function loadContent(content) {
			// Verstecke alle DIVs
			$('#Projekte').hide();
			$('#Rechnungen').hide();
			$('#Lieferanten').hide();

			// Überprüfe, ob der angeforderte Inhalt verfügbar ist
			if ($('#' + content).length) {
				// Zeige das ausgewählte DIV an
				$('#' + content).show();
			}
		}
        $(document).ready(function () {
			loadContent('Projekte');
            // DataTables initialisieren
			$('#tbl_projekte').DataTable({
					"language": {
						"decimal": ",",
						"thousands": "."
					},
					"order": [[0, "asc"]],
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"columnDefs": [
						{ "className": 'dt-body-right', "targets": [1,2,3,4,5] },
						{ "type": 'de_date', "targets": [1,2] }
					]
			});
            // DataTables initialisieren
			$('#tbl_rechnungen').DataTable({
					"language": {
						"decimal": ",",
						"thousands": "."
					},
					"order": [[0, "asc"], [2, "desc"]],
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"columnDefs": [
						{ "className": 'dt-body-right', "targets": [2,4,5] },
						{ "className": 'dt-body-center', "targets": [6] },
						{ "type": 'de_date', "targets": 2 }
					]
			});
            // DataTables initialisieren
			$('#tbl_lieferanten').DataTable({
					"language": {
						"decimal": ",",
						"thousands": "."
					},
					"order": [[3, "desc"]],
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"columnDefs": [
						{ "className": 'dt-body-right', "targets": [3] }
					]
			});
			
			var chart = new CanvasJS.Chart("chartContainer", {
				animationEnabled: true,
				culture: "de", // Setzt die Kultur auf Deutsch
				title: {
					text: "Cash Flow pro Monat"
				},
				axisX: {
					title: "Monat",
				},
				axisY: {
					title: "Cash Flow (€)",
					valueFormatString: "€#,##0.00", // Deutsche Formatierung mit zwei Dezimalstellen
					labelFormatter: function(e) {
						// Ersetze '.' durch ',' für das Dezimaltrennzeichen und ',' durch '.' für Tausendertrennzeichen
						return e.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".").replace(",", ",");
					}
				},
				toolTip: {
					shared: true,
				},
				data: chartData // Verwenden Sie die Daten aus data.php
			});
			chart.render();

        });

    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12" id="navBar">
				<nav class="navbar navbar-expand-lg navbar-light bg-light">
				  <a class="navbar-brand" href="#">Menü</a>
				  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				  </button>
				  <div class="collapse navbar-collapse" id="navbarNav">
					<ul class="navbar-nav">
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('Projekte')">Projekte</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('Rechnungen')">Rechnungen</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('Lieferanten')">Lieferanten</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="../index.php">Menü</a>
					  </li>
					</ul>
				  </div>
				</nav>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12" id="Projekte">
                <h2>Finanzübersicht der Projekte</h2>
                <table width="100%" id="tbl_projekte" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Projektname</th>
							<th>Startdatum</th>
							<th>Enddatum</th>
							<th>Gesamtbudget</th>
							<th>Ist-Kosten</th>
							<th>Verbleibendes Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aRechnungenSummary as $obj): ?>
                            <tr>
                                <td><?php echo $obj["projectNumber"]; ?></td>
								<td><?php echo $obj["StartDate"]; ?></td>
								<td><?php echo $obj["EndDate"]; ?></td>
								<td><?php echo number_format($obj["budget"], 2, ",", "."); ?> €</td>
								<td><?php echo number_format($obj["Brutto"], 2, ",", "."); ?> €</td>
								<td><?php echo number_format($obj["RestBudget"], 2, ",", "."); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
				<h2>Cash Flow (Ist-Kosten)</h2>
				<div id="chartContainer" style="height: 370px; width: 100%;"></div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12" id="Rechnungen">
                <h2>Rechnungsbezogene Ausgabenaufstellung</h2>
                <table width="100%" id="tbl_rechnungen" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Projektname</th>
							<th>Lieferant</th>
                            <th>Rechnungsdatum</th>
							<th>Rechnungsnummer</th>
							<th>Rechnungsbetrag</th>
							<th>Bezahlt</th>
							<th>Details</th>
                        </tr>
                    </thead>
					<tbody>
						<?php 
						$heute = new DateTime(); // Heutiges Datum
						foreach ($aRechnungenDetail as $obj):
							// Datum aus dem deutschen Format (d.m.Y) parsen
							$rechnungsDatum = DateTime::createFromFormat('d.m.Y', $obj["Datum"]);
							// Setze die Farbe und den Text je nach Status
							if ($obj["posted"] == 1) {
								$farbe = $rechnungsDatum <= $heute ? "green" : "red";
								$postedText = "Ja";
							} elseif ($obj["posted"] == 2) {
								$farbe = "green"; // Oder eine andere Farbe für Spenden
								$postedText = "Spende";
							} else {
								$farbe = "red";
								$postedText = "Nein";
							}
						?>
							<tr>
								<td><?php echo $obj["projectNumber"]; ?></td>
								<td><?php echo $obj["Lieferant"]; ?></td>
								<td><?php echo $obj["Datum"]; ?></td>
								<td><?php echo $obj["Rechnungsnummer"]; ?></td>
								<td><?php echo number_format($obj["Brutto"], 2, ",", "."); ?> €</td>
								<td style="color: <?php echo $farbe; ?>;"><?php echo $postedText; ?></td>
								<td><a href="rechnungen/<?php echo $obj["id"]; ?>.pdf" target="_blank"><i class="fas fa-file-pdf"></i></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
                </table>
            </div>
        </div> 
		<div class="row justify-content-center">
            <div class="col-12" id="Lieferanten">
                <h2>Lieferantebezogene Ausgabenaufstellung</h2>
                <table width="100%" id="tbl_lieferanten" class="table table-striped table-bordered">
                    <thead>
                        <tr>
							<th>Lieferant</th>
							<th>Straße</th>
							<th>Ort</th>
							<th>Rechnungsbetrag konsolidiert</th>
                        </tr>
                    </thead>
					<tbody>
                        <?php foreach ($aSupplierProject as $obj): ?>
                            <tr>
								<td><?php echo $obj["supplier"]; ?></td>
								<td><?php echo $obj["street"]; ?></td>
								<td><?php echo $obj["location"]; ?></td>
								<td><?php echo number_format($obj["Brutto"], 2, ",", "."); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
					</tbody>
                </table>
            </div>
        </div> 
    </div>
</body>
</html>
