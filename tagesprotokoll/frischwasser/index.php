<?php require 'data.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title>Freibad Dabringhausen - Frischwasser - Auswertung</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <!-- CSS-Block -->
    <?php include 'styles.css'; ?>

    <!-- Latest compiled and minified JavaScript -->
    <?php include 'scripts.js'; ?>

    <script>
        $(document).ready(function() {
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
                                <a class="nav-link" href="../index.php" onclick="">Menü</a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <!--
        <div class="row">
            <div class="col-12">
                <h2>Übersicht</h2>
                <ul>
                    <li><strong>Gesamtverbrauch:</strong> <?php echo number_format($gesamtverbrauch, 1, ",", "."); ?> m³</li>
                    <li><strong>Gesamtbesucherzahl:</strong> <?php echo number_format($gesamtbesucherzahl, 0, ",", "."); ?></li>
                    <li><strong>Ø Verbrauch pro Besucher:</strong> <?php echo number_format($verbrauch_pro_besucher, 3, ",", "."); ?> m³</li>
                </ul>
            </div>
        </div>
    -->
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
                            <th style="text-align: right;">Anzahl Besucher</th>
                            <th style="text-align: right;">Verbrauch pro Besucher</th>
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
                                <td style="text-align: right;"><?php echo isset($obj["Anzahl Besucher"]) ? number_format($obj["Anzahl Besucher"], 0, ",", ".") : '0'; ?></td>
                                <td style="text-align: right;"><?php echo isset($obj["Verbrauch pro Besucher"]) ? number_format($obj["Verbrauch pro Besucher"], 3, ",", ".") : '0,000'; ?> m³</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
