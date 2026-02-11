<?php require 'data.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title>Freibad Dabringhausen - Frischwasser - Auswertung</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS-Block -->
    <?php include 'styles.css'; ?>

    <!-- Latest compiled and minified JavaScript -->
    <?php include 'scripts.js'; ?>

    <style>
        .tab-content {
            margin-top: 20px;
        }
        .chart-container {
            margin: 20px 0;
            height: 400px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <script>
        $(document).ready(function() {
            // DataTable für tägliche Daten
            $('#tbl_lastgaengeProTag').DataTable({
                dom: 'Bfrtip',
                "order": [[0, "desc"]],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "columnDefs": [
                    { "type": 'de_date', "targets": 0 }
                ],
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

        function getCurrentTabHash() {
            // Aktuellen Tab ermitteln
            var activeTab = document.querySelector('.nav-link.active');
            if (activeTab && activeTab.id === 'minutely-tab') {
                return '#minutely-tab';
            }
            return '';
        }

        function updateData() {
            var startDate = $('#filter_date').val();
            var endDate = $('#filter_date_end').val();
            var currentHash = getCurrentTabHash();
            
            if (startDate && endDate) {
                window.location.href = window.location.pathname + '?filter_date=' + startDate + '&filter_date_end=' + endDate + currentHash;
            }
        }

        function setDateAndUpdate(startDate, endDate) {
            $('#filter_date').val(startDate);
            $('#filter_date_end').val(endDate);
            updateData();
        }

        // Beim Laden der Seite den korrekten Tab aktivieren
        $(window).on('load', function() {
            if (window.location.hash === '#minutely-tab') {
                $('#minutely-tab').tab('show');
            }
        });

            // DataTable für minütliche Daten
            $('#tbl_lastgaengeProMinute').DataTable({
                dom: 'Bfrtip',
                "order": [[0, "desc"]],
                "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
                "columnDefs": [
                    { 
                        "type": 'datetime-de', 
                        "targets": 0
                    }
                ],
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Chart erstellen
            createChart();

            // Filter Event Handler
            $('#filter_date, #filter_date_end').on('change', function() {
                updateData();
            });

            // Heute Button
            $('#btn_today').on('click', function() {
                var today = new Date().toISOString().split('T')[0];
                setDateAndUpdate(today, today);
            });

            // Gestern Button
            $('#btn_yesterday').on('click', function() {
                var yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                var dateStr = yesterday.toISOString().split('T')[0];
                setDateAndUpdate(dateStr, dateStr);
            });

            // Diese Woche Button
            $('#btn_thisweek').on('click', function() {
                var today = new Date();
                var monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
                var sunday = new Date(today.setDate(today.getDate() - today.getDay() + 7));
                
                setDateAndUpdate(monday.toISOString().split('T')[0], sunday.toISOString().split('T')[0]);
            });
        });



        function createChart() {
            var chartData = [];
            
            <?php if (!empty($aLastgaengeProMinute)): ?>
                <?php foreach ($aLastgaengeProMinute as $obj): ?>
                    chartData.push({
                        x: new Date("<?php echo date('Y-m-d H:i:s', strtotime($obj['datetime_raw'])); ?>"),
                        y: <?php echo $obj['Zählerstand']; ?>,
                        verbrauch: <?php echo $obj['Verbrauch (in m³)']; ?>
                    });
                <?php endforeach; ?>
            <?php endif; ?>

            var chart = new CanvasJS.Chart("chartContainer", {
                animationEnabled: true,
                title: {
                    text: "Frischwasser Zählerstand Verlauf"
                },
                axisX: {
                    valueFormatString: "DD.MM HH:mm",
                    labelAngle: -45
                },
                axisY: {
                    title: "Zählerstand (m³)",
                    includeZero: false
                },
                toolTip: {
                    contentFormatter: function (e) {
                        return "Zeit: " + CanvasJS.formatDate(e.entries[0].dataPoint.x, "DD.MM.YYYY HH:mm") + 
                               "<br/>Zählerstand: " + e.entries[0].dataPoint.y.toFixed(1) + " m³" +
                               "<br/>Verbrauch: " + e.entries[0].dataPoint.verbrauch.toFixed(3) + " m³";
                    }
                },
                data: [{
                    type: "line",
                    markerSize: 3,
                    dataPoints: chartData
                }]
            });
            chart.render();

            // Verbrauchs-Chart
            var verbrauchsData = [];
            <?php if (!empty($aLastgaengeProMinute)): ?>
                <?php foreach ($aLastgaengeProMinute as $obj): ?>
                    verbrauchsData.push({
                        x: new Date("<?php echo date('Y-m-d H:i:s', strtotime($obj['datetime_raw'])); ?>"),
                        y: <?php echo $obj['Verbrauch (in m³)']; ?>
                    });
                <?php endforeach; ?>
            <?php endif; ?>

            var verbrauchChart = new CanvasJS.Chart("verbrauchChartContainer", {
                animationEnabled: true,
                title: {
                    text: "Frischwasser Verbrauch pro Minute"
                },
                axisX: {
                    valueFormatString: "DD.MM HH:mm",
                    labelAngle: -45
                },
                axisY: {
                    title: "Verbrauch (m³)",
                    includeZero: true
                },
                toolTip: {
                    contentFormatter: function (e) {
                        return "Zeit: " + CanvasJS.formatDate(e.entries[0].dataPoint.x, "DD.MM.YYYY HH:mm") + 
                               "<br/>Verbrauch: " + e.entries[0].dataPoint.y.toFixed(3) + " m³";
                    }
                },
                data: [{
                    type: "column",
                    dataPoints: verbrauchsData
                }]
            });
            verbrauchChart.render();
        }
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12" id="navBar">
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <a class="navbar-brand" href="#">Frischwasser Auswertung</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="../index.php">Menü</a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="row justify-content-center">
            <div class="col-12">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="true">
                            Tagesauswertung
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="minutely-tab" data-bs-toggle="tab" data-bs-target="#minutely" type="button" role="tab" aria-controls="minutely" aria-selected="false">
                            Detaillierte Auswertung
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Tägliche Auswertung -->
            <div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <h2>Frischwasser Verbrauch pro Tag</h2>
                        <table width="100%" id="tbl_lastgaengeProTag" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th style="text-align: right;">Zählerstand</th>
                                    <th style="text-align: right;">Verbrauch: Gesamt</th>
                                    <th style="text-align: right;">Verbrauch (09:00 - 20:00)</th>
                                    <th style="text-align: right;">Verbrauch (Rest)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aLastgaengeProTag as $obj): ?>
                                    <tr>
                                        <td><?php echo date("d.m.Y", strtotime($obj["Datum"])); ?></td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Zählerstand"], 1, ",", "."); ?> m³</td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Verbrauch (in m³)"], 1, ",", "."); ?> m³</td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Verbrauch (in m³) (09:00 - 20:00)"], 1, ",", "."); ?> m³</td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Verbrauch (in m³) (Rest)"], 1, ",", "."); ?> m³</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Minütliche Auswertung -->
            <div class="tab-pane fade" id="minutely" role="tabpanel" aria-labelledby="minutely-tab">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <h2>Detaillierte Frischwasser Auswertung</h2>
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <h5>Zeitraum Filter</h5>
                            <div class="filter-row">
                                <div>
                                    <label for="filter_date">Von:</label>
                                    <input type="date" id="filter_date" class="form-control" value="<?php echo $filterDate; ?>">
                                </div>
                                <div>
                                    <label for="filter_date_end">Bis:</label>
                                    <input type="date" id="filter_date_end" class="form-control" value="<?php echo $filterDateEnd; ?>">
                                </div>
                                <div>
                                    <button id="btn_today" class="btn btn-primary btn-sm">Heute</button>
                                    <button id="btn_yesterday" class="btn btn-secondary btn-sm">Gestern</button>
                                    <button id="btn_thisweek" class="btn btn-info btn-sm">Diese Woche</button>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="chart-container">
                                    <div id="chartContainer"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="chart-container">
                                    <div id="verbrauchChartContainer"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiken -->
                        <?php if (!empty($aLastgaengeProMinute)): ?>
                            <?php 
                                $totalVerbrauch = array_sum(array_column($aLastgaengeProMinute, 'Verbrauch (in m³)'));
                                $maxVerbrauch = max(array_column($aLastgaengeProMinute, 'Verbrauch (in m³)'));
                                $avgVerbrauch = $totalVerbrauch / count($aLastgaengeProMinute);
                            ?>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h5>Statistiken für den gewählten Zeitraum:</h5>
                                        <ul class="mb-0">
                                            <li><strong>Gesamtverbrauch:</strong> <?php echo number_format($totalVerbrauch, 3, ",", "."); ?> m³</li>
                                            <li><strong>Durchschnittlicher Verbrauch/Min:</strong> <?php echo number_format($avgVerbrauch, 4, ",", "."); ?> m³</li>
                                            <li><strong>Maximaler Verbrauch/Min:</strong> <?php echo number_format($maxVerbrauch, 4, ",", "."); ?> m³</li>
                                            <li><strong>Anzahl Messungen:</strong> <?php echo count($aLastgaengeProMinute); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tabelle -->
                        <table width="100%" id="tbl_lastgaengeProMinute" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Zeitpunkt</th>
                                    <th style="text-align: right;">Zählerstand (m³)</th>
                                    <th style="text-align: right;">Verbrauch (m³)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aLastgaengeProMinute as $obj): ?>
                                    <tr>
                                        <td><?php echo $obj["Zeitpunkt"]; ?></td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Zählerstand"], 1, ",", "."); ?></td>
                                        <td style="text-align: right;"><?php echo number_format($obj["Verbrauch (in m³)"], 4, ",", "."); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS für Tabs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>