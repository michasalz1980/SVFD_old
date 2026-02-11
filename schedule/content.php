<?php
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
    <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="js/md5.js"></script>
    <script src="js/main.js"></script>
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
if (!isset($_SESSION['id'])) {
    ?>
    <div class="container" id="login">
        <form role="form" class="form-signin">
            <h2 class="form-signin-heading">Anmeldung/Registrierung</h2>
            <span class="input-group-addon">
                <input type="radio" name="login-process" value="login" checked="checked"><p>Anmelden</p>
            </span>
            <span class="input-group-addon">
                <input type="radio" name="login-process" value="register"><p>Registrieren</p>
            </span>
            <input id="firstname" type="text" required="" class="form-control" placeholder="Vorname">
            <input id="surname" type="text" required="" class="form-control" placeholder="Nachname">
            <input id="email" type="email" autofocus="" required="" placeholder="Email Adresse" class="form-control">
            <input id="passwd1" type="password" required="" placeholder="Passwort" class="form-control">
            <input id="passwd2" type="password" required="" placeholder="Passwort wiederholen" class="form-control">
            <span class="input-group-addon">
                <input type="radio" name="type" value="aushilfe"><p>Aushilfe</p>
            </span>
            <span class="input-group-addon">
                <input type="radio" name="type" value="kassenkraft" checked="checked"><p>Kassenkraft</p>
            </span>
            <!-- 31.08.2025 Funktionalität wird nicht mehr benötigt -->
            <!-- 
            <span class="input-group-addon">
                <input type="radio" name="type" value="kassenabschluss"><p>Kassenabschluss</p>
            </span>
            -->
            <button type="button" class="btn btn-lg btn-primary btn-block">Anmelden</button>
            <span class="label label-default"><a href="/schedule/resetPassword.php">Passwort anfordern</a></span>
        </form>
    </div>
<?php } else { ?>
    <div class="container" style="max-width: 1013px;" id="schedule">
        <input type="button" value="Daten speichern" onclick="schedule.sendJSON()" class="btn btn-primary pull-right"/>
        <table class="table table-striped">
            <thead>
            <tr>
                <th style="text-align: center;">Datum</th>
                <th style="text-align: center;">10-12 Uhr</th>
                <th style="text-align: center;">12-14 Uhr</th>
                <th style="text-align: center;">14-16 Uhr</th>
                <th style="text-align: center;">16-18 Uhr</th>
                <th style="text-align: center;">18-20 Uhr</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $days = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
            $date = new DateTime($config['start_date']);

            for ($i = 1; $i <= $daysCount; $i++) {
                echo '<tr>';
                $sDate = $date->format('Y-m-d');
                $t = $date->format('w');
                $wd = $days[$t];
                echo '<td style="text-align: center;">'. $wd .', ' . $date->format('d.m.Y') . '</td>';
                echo '<td style="text-align: center;"><input type="checkbox" name="uhrzeit" value="' . $sDate . ' 10:00:00;' . $sDate . ' 12:00:00"></td>';
                echo '<td style="text-align: center;"><input type="checkbox" name="uhrzeit" value="' . $sDate . ' 12:00:00;' . $sDate . ' 14:00:00"></td>';
                echo '<td style="text-align: center;"><input type="checkbox" name="uhrzeit" value="' . $sDate . ' 14:00:00;' . $sDate . ' 16:00:00"></td>';
                echo '<td style="text-align: center;"><input type="checkbox" name="uhrzeit" value="' . $sDate . ' 16:00:00;' . $sDate . ' 18:00:00"></td>';
                echo '<td style="text-align: center;"><input type="checkbox" name="uhrzeit" value="' . $sDate . ' 18:00:00;' . $sDate . ' 20:00:00"></td>';
                echo '</tr>';
                $date->add(new DateInterval('P01D'));
            }
            ?>
            </tbody>
        </table>
        <input type="button" value="Daten speichern" onclick="schedule.sendJSON()" class="btn btn-primary pull-right"/>
    </div>
<?php } ?>
<div id="content" class="container" style="max-width: 1013px">
</div>
</BODY>
</HTML>
