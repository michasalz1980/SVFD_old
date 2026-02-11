<!DOCTYPE html>
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
			$('#strom').hide();
			$('#lastgangJahr').hide();
			$('#lastgangSaison').hide();
			$('#lastgangMonat').hide();
            $('#pvFeedIn').hide();
            $('#energieUebersicht').hide();

			// Überprüfe, ob der angeforderte Inhalt verfügbar ist
			if ($('#' + content).length) {
				// Zeige das ausgewählte DIV an
				$('#' + content).show();
			}
		}
        $(document).ready(function () {
			loadContent('energieUebersicht');
            // DataTables initialisieren
			$('#tbl_lastgaengeProTag').DataTable({
					"order": [[0, "desc"]],
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"columnDefs": [{ "type": 'de_date', "targets": 0 }]
			});
			$('#tbl_lastgaengeProMonat').DataTable({
                "order": [[0, "desc"]],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            $('#tbl_lastgaengeProJahr').DataTable({
                "order": [[0, "desc"]],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            // Energie-Übersicht Tabelle
            $('#tbl_energieUebersicht').DataTable({
                "order": [[0, "desc"]],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
			$('#tbl_lastgaengeProSaison').DataTable({
					"order": [[0, "desc"]],
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"columnDefs": [
						{ "type": 'de_date', "targets": 1 },
						{ "type": 'de_date', "targets": 2 }
					]
			});
            $('#tbl_pvFeedIn').DataTable({
                "order": [[0, "desc"]],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        });

    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12" id="navBar">
				<nav class="navbar navbar-expand-lg navbar-light bg-light">
				  <a class="navbar-brand" href="#">Energie-Dashboard</a>
				  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				  </button>
				  <div class="collapse navbar-collapse" id="navbarNav">
					<ul class="navbar-nav">
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('energieUebersicht')">🏠 Energie-Übersicht</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('lastgangJahr')">📊 Verbrauch pro Jahr</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('lastgangSaison')">🏊 Verbrauch pro Saison</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('lastgangMonat')">📅 Verbrauch pro Monat</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="#" onclick="loadContent('pvFeedIn')">☀️ PV-Einspeisung</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link" href="../index.php">🏠 Hauptmenü</a>
					  </li>
					</ul>
				  </div>
				</nav>
            </div>
        </div>

        <!-- ENERGIE-ÜBERSICHT -->
        <div class="row justify-content-center">
            <div class="col-12" id="energieUebersicht">
                <h2>🔋 Energie-Management Übersicht</h2>
                <p class="text-muted">Umfassende Analyse von Stromerzeugung, -verbrauch und Energieflüssen</p>
                
                <!-- Quick Stats Karten -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Netzbezug 2025</h5>
                                <h3><?php 
                                    $verbrauch2025 = 0;
                                    foreach($aLastgaengeProJahr as $jahr) {
                                        if($jahr['Jahr'] == '2025') {
                                            $verbrauch2025 = $jahr['Verbrauch'];
                                            break;
                                        }
                                    }
                                    echo number_format($verbrauch2025, 0, ",", "."); 
                                ?> kWh</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">PV-Einspeisung 2025</h5>
                                <h3><?php 
                                    $pv2025 = 0;
                                    foreach($apvFeedInPerMonth as $pv) {
                                        if(strpos($pv['Monat'], '2025') !== false) {
                                            $pv2025 += $pv['Verbrauch'];
                                        }
                                    }
                                    echo number_format($pv2025, 0, ",", "."); 
                                ?> kWh</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Trend 2025 vs 2024</h5>
                                <h3><?php 
                                    $verbrauch2024 = 0;
                                    foreach($aLastgaengeProJahr as $jahr) {
                                        if($jahr['Jahr'] == '2024') {
                                            $verbrauch2024 = $jahr['Verbrauch'];
                                            break;
                                        }
                                    }
                                    if($verbrauch2024 > 0 && $verbrauch2025 > 0) {
                                        $trend = (($verbrauch2025 - $verbrauch2024) / $verbrauch2024) * 100;
                                        echo ($trend > 0 ? '+' : '') . number_format($trend, 1) . '%';
                                    } else {
                                        echo 'N/A';
                                    }
                                ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Einsparpotential</h5>
                                <h3>~15%</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Haupttabelle -->
                <table width="100%" id="tbl_energieUebersicht" class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Jahr</th>
                            <th>Netzbezug</th>
                            <th>PV-Einspeisung</th>
                            <th>PV-Erzeugung</th>
                            <th>PV-Eigenverbrauch</th>
                            <th>Gesamtverbrauch</th>
                            <th>Autarkiegrad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $jahresDaten = array();
                        
                        // Netzbezugsdaten sammeln (aus ffd_lastgang)
                        foreach ($aLastgaengeProJahr as $jahr) {
                            if($jahr["Jahr"] != "0" && $jahr["Jahr"] != "") {
                                $jahresDaten[$jahr["Jahr"]]['netzbezug'] = $jahr["Verbrauch"];
                            }
                        }
                        
                        // PV-Einspeisung sammeln
                        foreach ($apvFeedInPerMonth as $pv) {
                            $jahr = substr($pv["Monat"], 0, 4);
                            if($jahr != "" && $jahr != "0000") {
                                if (!isset($jahresDaten[$jahr]['einspeisung'])) {
                                    $jahresDaten[$jahr]['einspeisung'] = 0;
                                }
                                $jahresDaten[$jahr]['einspeisung'] += $pv["Verbrauch"];
                            }
                        }
                        
                        // PV-Erzeugung aus ffd_power_monitoring sammeln
                        try {
                            $sql = 'SELECT 
                                        DATE_FORMAT(datetime, "%Y-%m") as monat,
                                        ROUND((MAX(total_feed_wh) - MIN(total_feed_wh)) / 1000, 0) as erzeugung_kwh
                                    FROM ffd_power_monitoring 
                                    WHERE total_feed_wh > 0 AND datetime IS NOT NULL
                                    GROUP BY DATE_FORMAT(datetime, "%Y-%m")
                                    ORDER BY datetime';
                            $pvMonatsdaten = executeQuery($sql);
                            
                            // Monatsdaten zu Jahresdaten aggregieren
                            foreach ($pvMonatsdaten as $monat) {
                                $jahr = substr($monat['monat'], 0, 4);
                                if (!isset($jahresDaten[$jahr]['pv_erzeugung_echt'])) {
                                    $jahresDaten[$jahr]['pv_erzeugung_echt'] = 0;
                                }
                                $jahresDaten[$jahr]['pv_erzeugung_echt'] += $monat['erzeugung_kwh'];
                            }
                            
                            $pvMonitoringVerfuegbar = true;
                            
                        } catch (Exception $e) {
                            $pvMonitoringVerfuegbar = false;
                            echo "<!-- INFO: PV-Monitoring data not available: " . $e->getMessage() . " -->\n";
                        }
                        
                        // Daten ausgeben
                        krsort($jahresDaten); // Absteigende Sortierung
                        
                        foreach ($jahresDaten as $jahr => $daten): 
                            $netzbezug = isset($daten['netzbezug']) ? $daten['netzbezug'] : 0;
                            $pvEinspeisung = isset($daten['einspeisung']) ? $daten['einspeisung'] : 0;
                            
                            // PV-Erzeugung: Echte Daten falls verfügbar, sonst Schätzung
                            if (isset($daten['pv_erzeugung_echt']) && $daten['pv_erzeugung_echt'] > 0) {
                                $pvErzeugung = $daten['pv_erzeugung_echt'];
                                $schaetzung = false;
                            } else {
                                $pvErzeugung = $pvEinspeisung > 0 ? round($pvEinspeisung / 0.7) : 0; // Fallback
                                $schaetzung = true;
                            }
                            
                            // PV-Eigenverbrauch
                            $pvEigenverbrauch = $pvErzeugung - $pvEinspeisung;
                            
                            // Gesamtverbrauch = Netzbezug + PV-Eigenverbrauch  
                            $gesamtverbrauch = $netzbezug + $pvEigenverbrauch;
                            
                            // Autarkiegrad (Anteil des Gesamtverbrauchs durch PV gedeckt)
                            $autarkiegrad = $gesamtverbrauch > 0 ? ($pvEigenverbrauch / $gesamtverbrauch) * 100 : 0;
                            if ($autarkiegrad > 100) $autarkiegrad = 100; // Maximum 100%
                        ?>
                            <tr>
                                <td><strong><?php echo $jahr; ?></strong></td>
                                <td class="text-end redText"><?php echo number_format($netzbezug, 0, ",", "."); ?> kWh</td>
                                <td class="text-end text-info"><?php echo number_format($pvEinspeisung, 0, ",", "."); ?> kWh</td>
                                <td class="text-end greenText">
                                    <strong><?php echo number_format($pvErzeugung, 0, ",", "."); ?> kWh</strong>
                                    <?php if($schaetzung): ?>
                                        <small class="text-muted d-block">*geschätzt</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-success"><?php echo number_format($pvEigenverbrauch, 0, ",", "."); ?> kWh</td>
                                <td class="text-end"><strong><?php echo number_format($gesamtverbrauch, 0, ",", "."); ?> kWh</strong></td>
                                <td class="text-center">
                                    <strong class="<?php echo $autarkiegrad > 20 ? 'greenText' : ($autarkiegrad > 10 ? 'text-warning' : 'redText'); ?>">
                                        <?php echo number_format($autarkiegrad, 1); ?>%
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- LEGENDE -->
                <div class="mt-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <button class="btn btn-link text-decoration-none" type="button" data-toggle="collapse" data-target="#legende" aria-expanded="false" aria-controls="legende">
                                    📖 Legende & Berechnungsgrundlagen
                                </button>
                            </h5>
                        </div>
                        <div id="legende" class="collapse">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">📊 Datenspalten</h6>
                                        <dl>
                                            <dt>Netzbezug</dt>
                                            <dd>Strom aus dem öffentlichen Stromnetz bezogen (gemessen via Lastgang)</dd>
                                            
                                            <dt>PV-Einspeisung</dt>
                                            <dd>Solarstrom, der ins öffentliche Stromnetz eingespeist wurde</dd>
                                            
                                            <dt>PV-Erzeugung</dt>
                                            <dd>Gesamterzeugung der Photovoltaik-Anlage (echte Messdaten oder geschätzt)</dd>
                                            
                                            <dt>PV-Eigenverbrauch</dt>
                                            <dd>Solarstrom, der direkt vor Ort verbraucht wurde (nicht eingespeist)</dd>
                                            
                                            <dt>Gesamtverbrauch</dt>
                                            <dd>Gesamter Stromverbrauch des Freibads (Netzbezug + PV-Eigenverbrauch)</dd>
                                            
                                            <dt>Autarkiegrad</dt>
                                            <dd>Anteil des Gesamtverbrauchs, der durch eigene PV-Erzeugung gedeckt wird</dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success">🔢 Berechnungsformeln</h6>
                                        <dl>
                                            <dt>PV-Erzeugung</dt>
                                            <dd><code>(MAX(total_feed_wh) - MIN(total_feed_wh)) ÷ 1000 pro Monat</code><br>
                                            <small class="text-muted">PRIMÄR: Echte Messdaten aus Power-Monitoring (ffd_power_monitoring)</small><br>
                                            <small class="text-muted">FALLBACK: Schätzung PV-Einspeisung ÷ 0,7 (falls keine Messdaten verfügbar)</small></dd>
                                            
                                            <dt>PV-Eigenverbrauch</dt>
                                            <dd><code>PV-Erzeugung - PV-Einspeisung</code></dd>
                                            
                                            <dt>Gesamtverbrauch</dt>
                                            <dd><code>Netzbezug + PV-Eigenverbrauch</code></dd>
                                            
                                            <dt>Autarkiegrad</dt>
                                            <dd><code>(PV-Eigenverbrauch ÷ Gesamtverbrauch) × 100%</code></dd>
                                        </dl>
                                        
                                        <h6 class="text-warning mt-3">🎯 Autarkiegrad-Bewertung</h6>
                                        <ul class="list-unstyled">
                                            <li><span class="badge bg-success">Sehr gut</span> > 20%</li>
                                            <li><span class="badge bg-warning">Gut</span> 10-20%</li>
                                            <li><span class="badge bg-danger">Ausbaufähig</span> < 10%</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-success">
                                            <h6>✅ Dashboard-Status</h6>
                                            <ul class="mb-0">
                                                <li><strong>Robuste Datenbankanbindung:</strong> Automatischer Fallback von RedBeanPHP auf PDO</li>
                                                <li><strong>Echte PV-Daten:</strong> Verwendet Power-Monitoring (total_feed_wh) für präzise Erzeugungsmessung</li>
                                                <li><strong>Berechnung:</strong> Monatliche Differenz (letzter - erster Eintrag) der kumulativen Wh-Werte</li>
                                                <li><strong>Transparenz:</strong> Geschätzte Werte werden deutlich markiert (*geschätzt)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BESTEHENDE TABELLEN -->
        <div class="row justify-content-center">
            <div class="col-12" id="lastgangJahr">
                <h2>Stromverbrauch pro Jahr</h2>
                <table width="100%" id="tbl_lastgaengeProJahr" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Jahr</th>
                            <th>Verbrauch (in kWh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aLastgaengeProJahr as $obj): ?>
                            <tr>
                                <td><?php echo $obj["Jahr"]; ?></td>
                                <td><?php echo number_format((float)$obj["Verbrauch"], 0, ",", "."); ?> kWh</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-12" id="lastgangSaison">
                <h2>Stromverbrauch pro Saison</h2>
                <table width="100%" id="tbl_lastgaengeProSaison" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Saison</th>
                            <th>Start</th>
                            <th>Ende</th>
                            <th>Anzahl Öffnungstage</th>
                            <th>Gesamtverbrauch</th>
							<th>Ø Verbrauch pro Tag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aLastgaengeProSaison as $obj): ?>
                            <tr>
                                <td><?php echo $obj["Saison"]; ?></td>
                                <td><?php echo $obj["Start"]; ?></td>
                                <td><?php echo $obj["Ende"]; ?></td>
                                <td><?php echo $obj["Anzahl"]; ?></td>
                                <td><?php echo number_format((float)$obj["Verbrauch"], 0, ",", "."); ?> kWh</td>
								<td><?php echo number_format((float)$obj["Verbrauch pro Tag"], 0, ",", "."); ?> kWh</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-12" id="lastgangMonat">
                <h2>Stromverbrauch pro Monat</h2>
                <table width="100%" id="tbl_lastgaengeProMonat" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Monat</th>
                            <th>Verbrauch (in kWh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aLastgaengeProMonat as $obj): ?>
                            <tr>
                                <td><?php echo $obj["Monat"]; ?></td>
                                <td><?php echo number_format((float)$obj["Verbrauch"], 0, ",", "."); ?> kWh</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-12" id="pvFeedIn">
                <h2>PV eingespeist</h2>
                <table width="100%" id="tbl_pvFeedIn" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Monat</th>
                            <th>Einspeisung (in kWh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apvFeedInPerMonth as $obj): ?>
                            <tr>
                                <td><?php echo $obj["Monat"]; ?></td>
                                <td><?php echo number_format((float)$obj["Verbrauch"], 0, ",", "."); ?> kWh</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>