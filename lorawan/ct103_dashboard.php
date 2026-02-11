<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔌 CT103 Stromverbrauch Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header .timestamp {
            font-size: 13px;
            color: #999;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        
        .stat-card.current {
            border-left-color: #ff6b6b;
        }
        
        .stat-card.power {
            border-left-color: #ffd93d;
        }
        
        .stat-card.energy {
            border-left-color: #6bcf7f;
        }
        
        .stat-card.signal {
            border-left-color: #4d96ff;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-unit {
            font-size: 14px;
            color: #999;
            margin-left: 5px;
        }
        
        .stat-extra {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #667eea;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #667eea;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-valid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: white;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
            transition: background 0.3s;
        }
        
        .refresh-btn:hover {
            background: #764ba2;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔌 CT103 Stromverbrauch Monitor</h1>
            <div class="timestamp">Zuletzt aktualisiert: <span id="lastUpdate">-</span></div>
        </div>
        
        <button class="refresh-btn" onclick="loadData()">🔄 Aktualisieren</button>
        
        <div id="errorContainer"></div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card current">
                <div class="stat-label">⚡ Stromstärke</div>
                <div class="stat-value"><span id="currentValue">-</span><span class="stat-unit">A</span></div>
                <div class="stat-extra">
                    <strong>Min:</strong> <span id="currentMin">-</span> A | 
                    <strong>Max:</strong> <span id="currentMax">-</span> A
                </div>
            </div>
            
            <div class="stat-card power">
                <div class="stat-label">🔋 Leistung (aktuell)</div>
                <div class="stat-value"><span id="powerValue">-</span><span class="stat-unit">W</span></div>
                <div class="stat-extra">
                    <strong>Min:</strong> <span id="powerMin">-</span> W | 
                    <strong>Max:</strong> <span id="powerMax">-</span> W
                </div>
            </div>
            
            <div class="stat-card energy">
                <div class="stat-label">⚙️ Energie (kumuliert)</div>
                <div class="stat-value"><span id="energyValue">-</span><span class="stat-unit">kWh</span></div>
                <div class="stat-extra">
                    <strong>Datenstart:</strong> <span id="energyStart">-</span>
                </div>
            </div>
            
            <div class="stat-card signal">
                <div class="stat-label">📡 Signal</div>
                <div class="stat-value"><span id="rssiValue">-</span><span class="stat-unit">dBm</span></div>
                <div class="stat-extra">
                    <strong>SNR:</strong> <span id="snrValue">-</span> dB
                </div>
            </div>
        </div>
        
        <!-- Charts Grid -->
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-title">Stromverbrauch (Leistung) Trend</div>
                <canvas id="powerChart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">Stromstärke Trend</div>
                <canvas id="currentChart"></canvas>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <div style="margin-bottom: 15px; font-weight: 600;">
                Letzte Messwerte (<span id="rowCount">0</span> Einträge)
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Zeit</th>
                        <th>Gerät</th>
                        <th>Stromstärke</th>
                        <th>Leistung</th>
                        <th>Energie</th>
                        <th>Signal (RSSI)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Laden...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        let chartPower = null;
        let chartCurrent = null;
        let allData = [];
        
        // Auto-refresh alle 30 Sekunden
        setInterval(() => {
            loadData();
        }, 30000);
        
        // Lade Daten beim Start
        document.addEventListener('DOMContentLoaded', () => {
            loadData();
        });
        
        function loadData() {
            fetch('ct103_api.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        showError(data.error);
                        return;
                    }
                    
                    allData = data;
                    updateStats();
                    updateTable();
                    updateCharts();
                    updateTimestamp();
                    clearError();
                })
                .catch(error => {
                    showError('Fehler beim Laden der Daten: ' + error.message);
                    console.error('Error:', error);
                });
        }
        
        function updateStats() {
            if (allData.length === 0) return;
            
            const latest = allData[0];
            const powerValues = allData.map(d => d.power_w).filter(v => v !== null);
            const currentValues = allData.map(d => d.current_a).filter(v => v !== null);
            
            // Aktuelle Werte
            document.getElementById('currentValue').textContent = 
                (latest.current_a !== null) ? latest.current_a.toFixed(2) : '-';
            document.getElementById('powerValue').textContent = 
                (latest.power_w !== null) ? latest.power_w : '-';
            document.getElementById('energyValue').textContent = 
                (latest.energy_kwh !== null) ? latest.energy_kwh.toFixed(3) : '-';
            document.getElementById('rssiValue').textContent = 
                (latest.rssi !== null) ? latest.rssi : '-';
            document.getElementById('snrValue').textContent = 
                (latest.snr !== null) ? latest.snr.toFixed(1) : '-';
            
            // Min/Max
            if (powerValues.length > 0) {
                document.getElementById('powerMin').textContent = 
                    Math.min(...powerValues).toFixed(0);
                document.getElementById('powerMax').textContent = 
                    Math.max(...powerValues).toFixed(0);
            }
            
            if (currentValues.length > 0) {
                document.getElementById('currentMin').textContent = 
                    Math.min(...currentValues).toFixed(2);
                document.getElementById('currentMax').textContent = 
                    Math.max(...currentValues).toFixed(2);
            }
            
            // Energy start
            if (allData.length > 0) {
                const oldest = allData[allData.length - 1];
                document.getElementById('energyStart').textContent = 
                    oldest.timestamp.substring(0, 10);
            }
        }
        
        function updateTable() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            if (allData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">Keine Daten</td></tr>';
                return;
            }
            
            allData.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.timestamp}</td>
                    <td>${row.device_name}</td>
                    <td>${row.current_a !== null ? row.current_a.toFixed(2) : '-'} A</td>
                    <td>${row.power_w !== null ? row.power_w : '-'} W</td>
                    <td>${row.energy_kwh !== null ? row.energy_kwh.toFixed(3) : '-'} kWh</td>
                    <td>${row.rssi !== null ? row.rssi : '-'} dBm</td>
                    <td>
                        <span class="status-badge status-${row.status === 'valid' ? 'valid' : 'warning'}">
                            ${row.status === 'valid' ? '✓ Gültig' : '⚠ ' + row.status}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            document.getElementById('rowCount').textContent = allData.length;
        }
        
        function updateCharts() {
            // Reversiere für Zeitachse (älteste zuerst)
            const reversed = [...allData].reverse();
            
            const times = reversed.map(d => d.timestamp.substring(11, 16));
            const powerData = reversed.map(d => d.power_w);
            const currentData = reversed.map(d => d.current_a);
            
            // Power Chart
            const ctxPower = document.getElementById('powerChart').getContext('2d');
            if (chartPower) chartPower.destroy();
            
            chartPower = new Chart(ctxPower, {
                type: 'line',
                data: {
                    labels: times,
                    datasets: [{
                        label: 'Leistung (W)',
                        data: powerData,
                        borderColor: '#ffd93d',
                        backgroundColor: 'rgba(255, 217, 61, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: '#ffd93d',
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => value + ' W' }
                        }
                    }
                }
            });
            
            // Current Chart
            const ctxCurrent = document.getElementById('currentChart').getContext('2d');
            if (chartCurrent) chartCurrent.destroy();
            
            chartCurrent = new Chart(ctxCurrent, {
                type: 'line',
                data: {
                    labels: times,
                    datasets: [{
                        label: 'Stromstärke (A)',
                        data: currentData,
                        borderColor: '#ff6b6b',
                        backgroundColor: 'rgba(255, 107, 107, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3,
                        pointBackgroundColor: '#ff6b6b',
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true, position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => value.toFixed(2) + ' A' }
                        }
                    }
                }
            });
        }
        
        function updateTimestamp() {
            const now = new Date();
            const timeStr = now.toLocaleString('de-DE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('lastUpdate').textContent = timeStr;
        }
        
        function showError(message) {
            const container = document.getElementById('errorContainer');
            container.innerHTML = `<div class="error">⚠️ ${message}</div>`;
        }
        
        function clearError() {
            document.getElementById('errorContainer').innerHTML = '';
        }
    </script>
</body>
</html>
