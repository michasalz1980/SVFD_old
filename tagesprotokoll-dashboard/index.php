<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freibad Dabringhausen - Monitoring Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
}

.header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header h1 {
    color: white;
    font-size: 1.8rem;
    font-weight: 300;
}

.header .subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.version-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.tabs {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s;
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.tab-btn.active {
    background: #667eea;
    color: white;
}

.tab-btn:hover {
    transform: translateY(-2px);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.status-bar {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.status-item {
    text-align: center;
    flex: 1;
    min-width: 120px;
}

.status-label {
    font-size: 0.8rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.status-value {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
}

.status-unit {
    font-size: 0.9rem;
    color: #7f8c8d;
    margin-left: 0.25rem;
}

.controls {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.time-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.time-btn {
    padding: 0.5rem 1rem;
    border: 2px solid #ddd;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.3s;
    white-space: nowrap;
    min-width: 80px;
    text-align: center;
}

.time-btn.active,
.time-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.refresh-btn {
    background: linear-gradient(45deg, #4CAF50, #45a049);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: transform 0.2s;
}

.refresh-btn:hover {
    transform: translateY(-2px);
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.chart-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.chart-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1rem;
    text-align: center;
}

.chart-wrapper {
    position: relative;
    height: 300px;
}

.last-update {
    background: #e8f5e8;
    color: #27ae60;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
    white-space: nowrap;
    transition: all 0.3s ease;
    cursor: help;
    text-align: center;
    margin-top: 1rem;
}

.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
    color: #7f8c8d;
}

.error {
    background: #ffebee;
    color: #c62828;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    text-align: center;
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 1rem;
    }
    
    .controls {
        flex-direction: column;
        text-align: center;
    }
    
    .tabs {
        flex-direction: column;
    }
    
    .status-bar {
        flex-direction: column;
    }
}
    </style>
</head>
<body>
    <div class="header">
        <h1>🏊‍♂️ Freibad Dabringhausen</h1>
        <div class="subtitle">Monitoring Dashboard <span class="version-badge">v2.0.0</span></div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('tagesprotokoll')">📋 Tagesprotokoll</button>
            <button class="tab-btn" onclick="switchTab('wasserqualitaet')">🧪 Wasserqualität</button>
        </div>

        <!-- Tagesprotokoll Tab -->
        <div id="tagesprotokoll" class="tab-content active">
            <div class="status-bar">
                <div class="status-item">
                    <div class="status-label">Tagesbesucher</div>
                    <div class="status-value" id="current-besucher">--<span class="status-unit">Pers.</span></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Lufttemperatur</div>
                    <div class="status-value" id="current-lufttemp">--<span class="status-unit">°C</span></div>
                </div>
                <div class="status-item">
                    <div class="status-label">MZB Temperatur</div>
                    <div class="status-value" id="current-mzb-temp">--<span class="status-unit">°C</span></div>
                </div>
                <div class="status-item">
                    <div class="status-label">NSB Temperatur</div>
                    <div class="status-value" id="current-nsb-temp">--<span class="status-unit">°C</span></div>
                </div>
            </div>

            <div class="controls">
                <div class="time-selector">
                    <span style="margin-right: 1rem; color: #666;">Zeitraum:</span>
                    <button class="time-btn active" onclick="changeTimeRange('7d')">7 Tage</button>
                    <button class="time-btn" onclick="changeTimeRange('30d')">30 Tage</button>
                    <button class="time-btn" onclick="changeTimeRange('90d')">3 Monate</button>
                    <button class="time-btn" onclick="changeTimeRange('1y')">1 Jahr</button>
                    <button class="time-btn" onclick="changeTimeRange('all')">Alle</button>
                </div>
                <button class="refresh-btn" onclick="refreshData()">🔄 Aktualisieren</button>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">👥 Besucherzahlen</h3>
                    <div class="chart-wrapper">
                        <canvas id="besucherChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">🌡️ Temperaturverläufe</h3>
                    <div class="chart-wrapper">
                        <canvas id="temperaturChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">💧 Zählerstände</h3>
                    <div class="chart-wrapper">
                        <canvas id="zaehlerChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">🌦️ Wetterbedingungen</h3>
                    <div class="chart-wrapper">
                        <canvas id="wetterChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wasserqualität Tab -->
        <div id="wasserqualitaet" class="tab-content">
            <div class="status-bar">
                <div class="status-item">
                    <div class="status-label">pH-Wert</div>
                    <div class="status-value" id="current-ph">--</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Chlor frei</div>
                    <div class="status-value" id="current-cl-frei">--<span class="status-unit">mg/l</span></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Chlor gesamt</div>
                    <div class="status-value" id="current-cl-gesamt">--<span class="status-unit">mg/l</span></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Redox-Wert</div>
                    <div class="status-value" id="current-redox">--<span class="status-unit">mV</span></div>
                </div>
            </div>

            <div class="controls">
                <div class="time-selector">
                    <span style="margin-right: 1rem; color: #666;">Zeitraum:</span>
                    <button class="time-btn active" onclick="changeTimeRange('7d')">7 Tage</button>
                    <button class="time-btn" onclick="changeTimeRange('30d')">30 Tage</button>
                    <button class="time-btn" onclick="changeTimeRange('90d')">3 Monate</button>
                    <button class="time-btn" onclick="changeTimeRange('1y')">1 Jahr</button>
                    <button class="time-btn" onclick="changeTimeRange('all')">Alle</button>
                </div>
                <button class="refresh-btn" onclick="refreshData()">🔄 Aktualisieren</button>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">⚗️ pH-Wert Verlauf</h3>
                    <div class="chart-wrapper">
                        <canvas id="phChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">🧪 Chlorwerte</h3>
                    <div class="chart-wrapper">
                        <canvas id="chlorChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">⚡ Redox-Wert</h3>
                    <div class="chart-wrapper">
                        <canvas id="redoxChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">🏊‍♂️ Beckenwerte (MZB vs NSB vs KKB)</h3>
                    <div class="chart-wrapper">
                        <canvas id="beckenChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="last-update" id="last-update">
            Letzte Aktualisierung: --
        </div>
    </div>

    <script>
let charts = {};
let currentTimeRange = '7d';
let currentTab = 'tagesprotokoll';

// Chart-Konfigurationen
const chartConfigs = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index'
    },
    plugins: {
        legend: { 
            display: true,
            position: 'top'
        }
    },
    scales: {
        x: {
            title: { display: true, text: 'Datum' },
            grid: { color: 'rgba(0, 0, 0, 0.1)' }
        },
        y: {
            grid: { color: 'rgba(0, 0, 0, 0.1)' }
        }
    },
    elements: {
        line: { tension: 0.4 },
        point: { radius: 3, hoverRadius: 6 }
    }
};

// Charts initialisieren
function initCharts() {
    // Tagesprotokoll Charts
    const besucherCtx = document.getElementById('besucherChart').getContext('2d');
    charts.besucher = new Chart(besucherCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Tagesbesucher',
                data: [],
                backgroundColor: 'rgba(52, 152, 219, 0.6)',
                borderColor: '#3498db',
                borderWidth: 2
            }]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'Anzahl Besucher' },
                    beginAtZero: true
                }
            }
        }
    });

    const temperaturCtx = document.getElementById('temperaturChart').getContext('2d');
    charts.temperatur = new Chart(temperaturCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Lufttemperatur',
                    data: [],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    fill: false
                },
                {
                    label: 'MZB Temperatur',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: false
                },
                {
                    label: 'NSB Temperatur',
                    data: [],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'Temperatur (°C)' }
                }
            }
        }
    });

    const zaehlerCtx = document.getElementById('zaehlerChart').getContext('2d');
    charts.zaehler = new Chart(zaehlerCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Wasserleitungsnetz',
                    data: [],
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    fill: false
                },
                {
                    label: 'Abwasser',
                    data: [],
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'Zählerstand' }
                }
            }
        }
    });

    const wetterCtx = document.getElementById('wetterChart').getContext('2d');
    charts.wetter = new Chart(wetterCtx, {
        type: 'doughnut',
        data: {
            labels: ['Sonnig', 'Heiter', 'Bewölkt', 'Regen', 'Gewitter'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: [
                    '#f1c40f',
                    '#f39c12',
                    '#95a5a6',
                    '#3498db',
                    '#9b59b6'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });

    // Wasserqualität Charts
    const phCtx = document.getElementById('phChart').getContext('2d');
    charts.ph = new Chart(phCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'MZB pH-Wert',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: false
                },
                {
                    label: 'NSB pH-Wert',
                    data: [],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: false
                },
                {
                    label: 'KKB pH-Wert',
                    data: [],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'pH-Wert' },
                    min: 6,
                    max: 9
                }
            }
        }
    });

    const chlorCtx = document.getElementById('chlorChart').getContext('2d');
    charts.chlor = new Chart(chlorCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Chlor frei (MZB)',
                    data: [],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: false
                },
                {
                    label: 'Chlor gesamt (MZB)',
                    data: [],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'Chlor (mg/l)' },
                    beginAtZero: true
                }
            }
        }
    });

    const redoxCtx = document.getElementById('redoxChart').getContext('2d');
    charts.redox = new Chart(redoxCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Redox-Wert (MZB)',
                data: [],
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                fill: true
            }]
        },
        options: {
            ...chartConfigs,
            scales: {
                ...chartConfigs.scales,
                y: {
                    ...chartConfigs.scales.y,
                    title: { display: true, text: 'Redox-Wert (mV)' }
                }
            }
        }
    });

    const beckenCtx = document.getElementById('beckenChart').getContext('2d');
    charts.becken = new Chart(beckenCtx, {
        type: 'scatter',
        data: {
            datasets: [
                {
                    label: 'MZB (pH vs Chlor)',
                    data: [],
                    backgroundColor: '#3498db',
                    borderColor: '#3498db'
                },
                {
                    label: 'NSB (pH vs Chlor)',
                    data: [],
                    backgroundColor: '#2ecc71',
                    borderColor: '#2ecc71'
                },
                {
                    label: 'KKB (pH vs Chlor)',
                    data: [],
                    backgroundColor: '#e74c3c',
                    borderColor: '#e74c3c'
                }
            ]
        },
        options: {
            ...chartConfigs,
            scales: {
                x: {
                    title: { display: true, text: 'pH-Wert' },
                    min: 6,
                    max: 9
                },
                y: {
                    title: { display: true, text: 'Chlor frei (mg/l)' },
                    beginAtZero: true
                }
            }
        }
    });
}

// Tab wechseln
function switchTab(tabName) {
    // Tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    
    currentTab = tabName;
    loadData();
}

// Zeitraum ändern
function changeTimeRange(range) {
    currentTimeRange = range;
    
    // Buttons aktualisieren
    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    loadData();
}

// Daten laden von der API
async function loadData() {
    try {
        const response = await fetch(`freibad_api.php?type=${currentTab}&range=${currentTimeRange}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error);
        }
        
        if (currentTab === 'tagesprotokoll') {
            loadTagesprotokollData(data);
        } else {
            loadWasserqualitaetData(data);
        }
        
        updateLastUpdate();
    } catch (error) {
        console.error('Fehler beim Laden der Daten:', error);
        showError('Fehler beim Laden der Daten: ' + error.message);
    }
}

function loadTagesprotokollData(apiData) {
    if (!apiData.data || apiData.data.length === 0) {
        showError('Keine Tagesprotokoll-Daten verfügbar');
        return;
    }
    
    const labels = [];
    const besucherData = [];
    const lufttempData = [];
    const mzbTempData = [];
    const nsbTempData = [];
    const wasserData = [];
    const abwasserData = [];
    
    apiData.data.forEach(row => {
        const date = new Date(row.Datum);
        labels.push(date.toLocaleDateString('de-DE'));
        
        besucherData.push(parseInt(row.Tagesbesucherzahl) || 0);
        lufttempData.push(parseFloat(row.Lufttemperatur) || null);
        mzbTempData.push(parseFloat(row.Temperatur_MZB) || null);
        nsbTempData.push(parseFloat(row.Temperatur_NSB) || null);
        wasserData.push(parseInt(row.Zaehlerstand_Wasserleitungsnetz) || null);
        abwasserData.push(parseInt(row.Zaehlerstand_Abwasser) || null);
    });
    
    // Charts aktualisieren
    charts.besucher.data.labels = labels;
    charts.besucher.data.datasets[0].data = besucherData;
    charts.besucher.update();
    
    charts.temperatur.data.labels = labels;
    charts.temperatur.data.datasets[0].data = lufttempData;
    charts.temperatur.data.datasets[1].data = mzbTempData;
    charts.temperatur.data.datasets[2].data = nsbTempData;
    charts.temperatur.update();
    
    charts.zaehler.data.labels = labels;
    charts.zaehler.data.datasets[0].data = wasserData;
    charts.zaehler.data.datasets[1].data = abwasserData;
    charts.zaehler.update();
    
    // Wetter-Doughnut Chart
    if (apiData.wetter_stats) {
        charts.wetter.data.datasets[0].data = [
            apiData.wetter_stats.sonnig,
            apiData.wetter_stats.heiter,
            apiData.wetter_stats.bewoelkt,
            apiData.wetter_stats.regen,
            apiData.wetter_stats.gewitter
        ];
        charts.wetter.update();
    }
    
    // Status-Bar aktualisieren
    if (apiData.current) {
        const current = apiData.current;
        document.getElementById('current-besucher').innerHTML = 
            `${parseInt(current.Tagesbesucherzahl) || 0}<span class="status-unit">Pers.</span>`;
        document.getElementById('current-lufttemp').innerHTML = 
            `${parseFloat(current.Lufttemperatur).toFixed(1) || '--'}<span class="status-unit">°C</span>`;
        document.getElementById('current-mzb-temp').innerHTML = 
            `${parseFloat(current.Temperatur_MZB).toFixed(1) || '--'}<span class="status-unit">°C</span>`;
        document.getElementById('current-nsb-temp').innerHTML = 
            `${parseFloat(current.Temperatur_NSB).toFixed(1) || '--'}<span class="status-unit">°C</span>`;
    }
}

function loadWasserqualitaetData(apiData) {
    if (!apiData.data || apiData.data.length === 0) {
        showError('Keine Wasserqualitäts-Daten verfügbar');
        return;
    }
    
    // Daten nach Datum gruppieren für Charts
    const dateGroups = {};
    const scatterData = {MZB: [], NSB: [], KKB: []};
    
    apiData.data.forEach(row => {
        const date = new Date(row.Datum).toLocaleDateString('de-DE');
        if (!dateGroups[date]) {
            dateGroups[date] = {MZB: null, NSB: null, KKB: null};
        }
        dateGroups[date][row.Becken] = row;
        
        // Scatter-Plot Daten
        if (row.pH_Wert > 0 && row.Cl_frei >= 0) {
            scatterData[row.Becken].push({
                x: parseFloat(row.pH_Wert),
                y: parseFloat(row.Cl_frei)
            });
        }
    });
    
    const dates = Object.keys(dateGroups).slice(-30); // Letzte 30 Datenpunkte
    const mzbPhData = [];
    const nsbPhData = [];
    const kkbPhData = [];
    const mzbClFreiData = [];
    const mzbClGesamtData = [];
    const mzbRedoxData = [];
    
    dates.forEach(date => {
        const dayData = dateGroups[date];
        mzbPhData.push(dayData.MZB ? parseFloat(dayData.MZB.pH_Wert) || null : null);
        nsbPhData.push(dayData.NSB ? parseFloat(dayData.NSB.pH_Wert) || null : null);
        kkbPhData.push(dayData.KKB ? parseFloat(dayData.KKB.pH_Wert) || null : null);
        mzbClFreiData.push(dayData.MZB ? parseFloat(dayData.MZB.Cl_frei) || null : null);
        mzbClGesamtData.push(dayData.MZB ? parseFloat(dayData.MZB.Cl_gesamt) || null : null);
        mzbRedoxData.push(dayData.MZB ? parseFloat(dayData.MZB.Redox_Wert) || null : null);
    });
    
    // Charts aktualisieren
    charts.ph.data.labels = dates;
    charts.ph.data.datasets[0].data = mzbPhData;
    charts.ph.data.datasets[1].data = nsbPhData;
    charts.ph.data.datasets[2].data = kkbPhData;
    charts.ph.update();
    
    charts.chlor.data.labels = dates;
    charts.chlor.data.datasets[0].data = mzbClFreiData;
    charts.chlor.data.datasets[1].data = mzbClGesamtData;
    charts.chlor.update();
    
    charts.redox.data.labels = dates;
    charts.redox.data.datasets[0].data = mzbRedoxData;
    charts.redox.update();
    
    charts.becken.data.datasets[0].data = scatterData.MZB.slice(-50);
    charts.becken.data.datasets[1].data = scatterData.NSB.slice(-50);
    charts.becken.data.datasets[2].data = scatterData.KKB.slice(-50);
    charts.becken.update();
    
    // Status-Bar aktualisieren
    if (apiData.current && apiData.current.MZB) {
        const current = apiData.current.MZB;
        document.getElementById('current-ph').textContent = 
            parseFloat(current.pH_Wert).toFixed(1) || '--';
        document.getElementById('current-cl-frei').innerHTML = 
            `${parseFloat(current.Cl_frei).toFixed(2) || '--'}<span class="status-unit">mg/l</span>`;
        document.getElementById('current-cl-gesamt').innerHTML = 
            `${parseFloat(current.Cl_gesamt).toFixed(2) || '--'}<span class="status-unit">mg/l</span>`;
        document.getElementById('current-redox').innerHTML = 
            `${parseInt(current.Redox_Wert) || '--'}<span class="status-unit">mV</span>`;
    }
}

function showError(message) {
    console.error(message);
    // Hier könnten Sie eine Fehlermeldung im UI anzeigen
}

function getCurrentRangeDays() {
    switch (currentTimeRange) {
        case '7d': return 7;
        case '30d': return 30;
        case '90d': return 90;
        case '1y': return 365;
        case 'all': return 30; // Für Demo begrenzt
        default: return 7;
    }
}

function updateLastUpdate() {
    document.getElementById('last-update').textContent = 
        `Letzte Aktualisierung: ${new Date().toLocaleString('de-DE')}`;
}

function refreshData() {
    loadData();
}

// Initialisierung
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadData();
    
    // Auto-Refresh alle 5 Minuten
    setInterval(loadData, 5 * 60 * 1000);
});
    </script>
</body>
</html>