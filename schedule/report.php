<?php
    use \RedBeanPHP\R as R;
    require 'vendor/autoload.php';
    $config = include 'config.php';

    // Datenbankkonfiguration
    $dbConfig = $config['database'];
    R::setup(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
        $dbConfig['username'],
        $dbConfig['password']
    );

    // Berechnung der Anzahl der Tage zwischen start_date und end_date
    $startDate = new DateTime($config['start_date']);
    $endDate = new DateTime($config['end_date']);
    $interval = $startDate->diff($endDate);
    $daysCount = $interval->days + 1; // Inklusive des Enddatums

    $days = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
    $date = new DateTime($config['start_date']);
    $TYPE = $_GET['type'];

    $workScheduleActive = R::getAll('SELECT u.id, u.firstname, u.surname, u.type, s.start_date, s.end_date, s.approved 
                                    FROM schedule s, user u 
                                    WHERE s.user_id = u.id AND u.type LIKE ? AND s.approved="true" 
                                    ORDER BY s.start_date, u.surname', array($TYPE));

    $workScheduleStandby = R::getAll('SELECT u.id, u.firstname, u.surname, u.type, s.start_date, s.end_date, s.approved 
                                    FROM schedule s, user u 
                                    WHERE s.user_id = u.id AND u.type LIKE ? AND s.standby="true" 
                                    ORDER BY s.start_date, u.surname', array($TYPE));

    R::close();

    /* PREPARE DATA STRUCTURE ACTIVE */
    $aDataStructureActive = [];
    foreach ($workScheduleActive as $element) {
        $aDataStructureActive[$element["start_date"]] = substr($element["firstname"], 0, 2) . ". " . $element["surname"];
    }

    /* PREPARE DATA STRUCTURE STANDBY */
    $aDataStructureStandby = [];
    foreach ($workScheduleStandby as $element) {
        $aDataStructureStandby[$element["start_date"]][] = substr($element["firstname"], 0, 2) . ". " . $element["surname"];
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML>
    <HEAD>
        <TITLE>Freibad Dabringhausen - Dienstplan</TITLE>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="css/general.css">
    </HEAD>
    <BODY>
        <input type="hidden" id="hiddenType" value="<?php echo $TYPE ?>" />

        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="container" id="header" width="100%">
                    <a href="/assets/images/logo.gif" id="logo"></a>
                </div>
            </div>
        </div>
        <div class="container" style="max-width: 1013px;" id="schedule">
            <table border="1" width="100%">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>10-12 Uhr</th>
                        <th>12-14 Uhr</th>
                        <th>14-16 Uhr</th>
                        <th>16-18 Uhr</th>
                        <th>18-20 Uhr</th>
                        <th>Bereitschaft</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        for ($i = 1; $i <= $daysCount; $i++) {
                            echo '<tr>';
                            $sDate = $date->format('Y-m-d');
                            $t = $date->format('w');
                            $wd = $days[$t];
                            echo '<td><div>' . $wd . ', ' . $date->format('d.m.Y') . '</div></td>';
                            
                            $timeSlots = ['10:00:00', '12:00:00', '14:00:00', '16:00:00', '18:00:00'];
                            foreach ($timeSlots as $time) {
                                $startDate = $sDate . ' ' . $time;
                                if (isset($aDataStructureActive[$startDate])) {
                                    echo '<td id="' . $startDate . '">' . $aDataStructureActive[$startDate] . '</td>';
                                } else {
                                    echo '<td id="' . $startDate . '" style="background-color: orange">Offen</td>';
                                }
                            }
                            
                            $startDate = $sDate . ' 00:00:00';
                            if (isset($aDataStructureStandby[$startDate]) && count($aDataStructureStandby[$startDate]) > 0) {
                                echo '<td name="bereitschaft">';
                                foreach ($aDataStructureStandby[$startDate] as $standby) {
                                    echo '<div>' . $standby . '</div>';
                                }
                                echo '</td>';
                            } else {
                                echo '<td id="' . $startDate . '" style="background-color: orange">Offen</td>';
                            }
                            echo '</tr>';
                            $date->add(new DateInterval('P1D'));
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="content" class="container" style="max-width: 1013px"></div>
    </BODY>
</HTML>
