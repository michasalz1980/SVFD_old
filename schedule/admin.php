<?php
    error_reporting(E_ALL); ini_set('display_errors', 1);
    session_start();
    $config = include 'config.php';

    // Berechnung der Anzahl der Tage zwischen start_date und end_date
    $startDate = new DateTime($config['start_date']);
    $endDate = new DateTime($config['end_date']);
    $interval = $startDate->diff($endDate);
    $daysCount = $interval->days + 1; // Inklusive des Enddatums
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
<HTML>
    <HEAD>
        <TITLE>Freibad Dabringhausen - Dienstplan</TITLE>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
        <script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
        <script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="js/md5.js"></script>
        <script type="text/javascript" src="js/main.js"></script>
        <link rel="stylesheet" href="css/signin.css">
        <link rel="stylesheet" href="css/general.css">
    </HEAD>
    <BODY>
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="container" id="header" style="max-width: 1013px;">
                <a href="/assets/images/logo.gif" id="logo"></a>

                <div id="navi">
                    <?php if (isset($_SESSION['id'])) { ?>
                        <input type="button" value="Ausloggen"  onClick="location.href='/schedule/api/logout'" class="btn btn-primary pull-right"/>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    if (1 == 0) {
        header("Location: https://personal.freibad-dabringhausen.de/schedule/content.php");
    } else { ?>
        <div class="container" style="max-width: 1013px;" id="schedule">
            <input type="button" class="btn btn-primary pull-right" onclick="admin.sendAdminJSON()" value="Daten speichern">
            <input type="button" class="btn btn-primary pull-right" onclick="report.sendReport('all')" value="PDF - An alle">
            <input type="button" class="btn btn-primary pull-right" onclick="report.sendReport('admin')" value="PDF - An admin">

            <div class="btn-group">
                <button type="button" class="btn btn-default">Aushilfe</button>
                <button type="button" class="btn btn-default">Kassenkraft</button>
                <!-- 31.08.2025 Funktionalität wird nicht mehr benötigt -->
                <!-- <button type="button" class="btn btn-default">Kassenabschluss</button> -->
            </div>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Datum</th>
                    <th>10-12 Uhr</th>
                    <th>12-14 Uhr</th>
                    <th>14-16 Uhr</th>
                    <th>16-18 Uhr</th>
                    <th>18-20 Uhr</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $days = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
                $date = new DateTime($config['start_date']);

                for ($i = 1; $i <= $daysCount; $i++) {
                    echo '<tr>';
                    $sDate = $date->format('Y-m-d');
                    $t = $date->format('w');
                    $wd = $days[$t];
                    echo ' <td><div>'. $wd .', ' . $date->format('d.m.Y') . '</div></td>';
                    echo '<td id="' . $sDate . ' 10:00:00;' . $sDate . ' 12:00:00">Offen</td>';
                    echo '<td id="' . $sDate . ' 12:00:00;' . $sDate . ' 14:00:00">Offen</td>';
                    echo '<td id="' . $sDate . ' 14:00:00;' . $sDate . ' 16:00:00">Offen</td>';
                    echo '<td id="' . $sDate . ' 16:00:00;' . $sDate . ' 18:00:00">Offen</td>';
                    echo '<td id="' . $sDate . ' 18:00:00;' . $sDate . ' 20:00:00">Offen</td>';
                    echo '</tr>';
                    $date->add(new DateInterval('P01D'));
                }
                ?>
                </tbody>
            </table>

            <input type="button" class="btn btn-primary pull-right" onclick="admin.sendAdminJSON()" value="Daten speichern">
            <input type="button" class="btn btn-primary pull-right" onclick="report.sendReport('all')" value="PDF - An alle">
            <input type="button" class="btn btn-primary pull-right" onclick="report.sendReport('admin')" value="PDF - An admin">
        </div>
    <?php } ?>
    <div id="content" class="container" style="max-width: 1013px">
    </div>
    </BODY>
</HTML>
