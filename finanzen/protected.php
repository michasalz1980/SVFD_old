<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$title = "Geschützte Seite";
include 'includes/header.php';
?>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">Einnahmen / Ausgaben</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">Karte 2</a>
    </li>
</ul>
<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
        <h3 class="mt-4">Einnahmen / Ausgaben (ohne Projekte)</h3>

        <!-- KPI Section -->
        <div class="kpi-container">
            <div class="kpi-box">
                <p><strong>Gesamtergebnis kumuliert (2023):</strong></p>
                <p>3.004 €</p>
            </div>
            <div class="kpi-box">
                <p><strong>Gesamtergebnis kumuliert (2024):</strong></p>
                <p>26.735 €</p>
            </div>
        </div>

        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th rowspan="2">Buchungstyp</th>
                    <th colspan="2" class="text-center">Januar</th>
                    <th colspan="2" class="text-center">Februar</th>
                    <th colspan="2" class="text-center">März</th>
                    <th colspan="2" class="text-center">April</th>
                    <th colspan="2" class="text-center">Mai</th>
                    <th colspan="2" class="text-center">Juni</th>
                </tr>
                <tr>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                    <th class="text-right">2023</th>
                    <th class="text-right">2024</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ausgabe</td>
                    <td class="text-right">n.v.</td>
                    <td class="text-right">-2.501 €</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-1.300 €</td>
                    <td class="text-right">-6.363 €</td>
                    <td class="text-right">-2.880 €</td>
                    <td class="text-right">-9.836 €</td>
                    <td class="text-right">-7.167 €</td>
                    <td class="text-right">-2.582 €</td>
                    <td class="text-right">-2.815 €</td>
                    <td class="text-right">-7.359 €</td>
                    <td class="text-right">-</td>
                </tr>
                <tr>
                    <td>Einnahme</td>
                    <td class="text-right">n.v.</td>
                    <td class="text-right">4.128 €</td>
                    <td class="text-right">709 €</td>
                    <td class="text-right">1.000 €</td>
                    <td class="text-right">875 €</td>
                    <td class="text-right">1.000 €</td>
                    <td class="text-right">17.970 €</td>
                    <td class="text-right">1.032 €</td>
                    <td class="text-right">3.146 €</td>
                    <td class="text-right">41.233 €</td>
                    <td class="text-right">35.448 €</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="highlight">
                    <td>Gesamtergebnis</td>
                    <td class="text-right">n.v.</td>
                    <td class="text-right">1.627 €</td>
                    <td class="text-right negative">-205 €</td>
                    <td class="text-right">-1.300 €</td>
                    <td class="text-right negative">-5.488 €</td>
                    <td class="text-right ">-1.880 €</td>
                    <td class="text-right">8.134 €</td>
                    <td class="text-right negative">-6.135 €</td>
                    <td class="text-right">563 €</td>
                    <td class="text-right">38.418 €</td>
                    <td class="text-right">28.089 €</td>
                    <td class="text-right">-</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
        <h3 class="mt-4">Sonstiges</h3>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
