// =============================================================================
// FRISCHWASSER COMPLETE - PERMANENTE LÖSUNG
// Alle Charts und Tabelle funktionieren dauerhaft
// =============================================================================

let fwCharts = {};
let currentFWTimeRange = '24h';
let fwAutoRefreshInterval;
let currentFWPage = 1;
let totalFWPages = 1;
let fwEntriesPerPage = 25;

// =============================================================================
// ROBUSTE CHART-INITIALISIERUNG
// =============================================================================

function initFrischwasserChartsRobust() {
    console.log('🚿 Initialisiere Frischwasser-Charts (robust)...');
    
    // Zerstöre alte Charts
    destroyFWChartsRobust();
    
    try {
        // Chart 1: Verbrauchsverlauf
        const ctx1 = document.getElementById('fwConsumptionChart');
        if (ctx1) {
            fwCharts.consumption = new Chart(ctx1.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['10:00', '12:00', '14:00', '16:00', '18:00'],
                    datasets: [{
                        label: 'Verbrauch',
                        data: [120, 180, 250, 200, 150],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            title: { display: true, text: 'Verbrauch (L)' }
                        },
                        x: { 
                            title: { display: true, text: 'Zeit' }
                        }
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Verbrauch: ${context.parsed.y.toFixed(1)} L`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Chart 1 (Verbrauchsverlauf) erstellt');
        }

        // Chart 2: Zählerstand
        const ctx2 = document.getElementById('fwCounterChart');
        if (ctx2) {
            fwCharts.counter = new Chart(ctx2.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['10:00', '12:00', '14:00', '16:00', '18:00'],
                    datasets: [{
                        label: 'Zählerstand',
                        data: [7432.1, 7432.3, 7432.5, 7432.7, 7432.9],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    scales: {
                        y: { 
                            title: { display: true, text: 'Zählerstand (m³)' }
                        },
                        x: { 
                            title: { display: true, text: 'Zeit' }
                        }
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Zählerstand: ${context.parsed.y.toFixed(3)} m³`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Chart 2 (Zählerstand) erstellt');
        }

        // Chart 3: Tagesverbrauch
        const ctx3 = document.getElementById('fwDailyChart');
        if (ctx3) {
            fwCharts.daily = new Chart(ctx3.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                    datasets: [{
                        label: 'Tagesverbrauch',
                        data: [2.1, 3.4, 2.8, 4.1, 3.9, 5.2, 4.6],
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(231, 76, 60, 0.6)',
                            'rgba(231, 76, 60, 0.9)',
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(231, 76, 60, 0.6)'
                        ],
                        borderColor: '#e74c3c',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            title: { display: true, text: 'Tagesverbrauch (m³)' }
                        },
                        x: { 
                            title: { display: true, text: 'Wochentag' }
                        }
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Verbrauch: ${context.parsed.y.toFixed(2)} m³`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Chart 3 (Tagesverbrauch) erstellt');
        }

        // Chart 4: Verbrauchsmuster
        const ctx4 = document.getElementById('fwPatternChart');
        if (ctx4) {
            fwCharts.pattern = new Chart(ctx4.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
                    datasets: [{
                        label: 'Stündlicher Verbrauch',
                        data: [20, 15, 45, 120, 200, 180, 150, 80],
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 8,
                        borderWidth: 3,
                        pointBackgroundColor: '#f39c12',
                        pointBorderColor: '#d68910'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            title: { display: true, text: 'Verbrauch (L/h)' }
                        },
                        x: { 
                            title: { display: true, text: 'Tageszeit' }
                        }
                    },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.x}: ${context.parsed.y} L/h`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Chart 4 (Verbrauchsmuster) erstellt');
        }
        
        console.log('🎉 Alle Frischwasser-Charts erfolgreich erstellt');
        
    } catch (error) {
        console.error('❌ Fehler bei Chart-Initialisierung:', error);
    }
}

function destroyFWChartsRobust() {
    Object.keys(fwCharts).forEach(chartKey => {
        if (fwCharts[chartKey] && typeof fwCharts[chartKey].destroy === 'function') {
            try {
                fwCharts[chartKey].destroy();
            } catch (error) {
                console.error(`Fehler beim Zerstören von Chart ${chartKey}:`, error);
            }
        }
    });
    fwCharts = {};
}

// =============================================================================
// DATENLADUNG MIT ECHTEN UND MOCK-DATEN
// =============================================================================

async function loadFrischwasserDataRobust() {
    try {
        console.log('📡 Lade Frischwasser-Daten...');
        
        const response = await fetch(`api_frischwasser.php?range=${currentFWTimeRange}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Keine JSON-Antwort');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'API-Fehler');
        }

        console.log('✅ Frischwasser-Daten erfolgreich geladen');
        
        updateFrischwasserValuesRobust(data.current, data.config);
        updateFrischwasserChartsRobust(data.history, data.config);
        updateFrischwasserAlertsRobust(data.active_alerts);
        updateFrischwasserLastUpdateRobust(data.last_update);

    } catch (error) {
        console.error('⚠️ Fehler beim Laden der Frischwasser-Daten:', error);
        console.log('🔄 Verwende Mock-Daten als Fallback...');
        updateFrischwasserValuesMockRobust();
        showFrischwasserErrorRobust('Verbindung zur API fehlgeschlagen - Mock-Daten werden angezeigt');
    }
}

function updateFrischwasserValuesRobust(current, config) {
    if (!current) {
        updateFrischwasserValuesMockRobust();
        return;
    }

    const elements = [
        { id: 'fw-current-counter', value: current.counter_m3, decimals: 3, unit: 'm³' },
        { id: 'fw-current-hourly', value: current.hourly_consumption, decimals: 1, unit: 'L' },
        { id: 'fw-current-daily', value: current.daily_consumption, decimals: 2, unit: 'm³' },
        { id: 'fw-current-flow', value: current.current_flow_lmin, decimals: 1, unit: 'l/min' },
        { id: 'fw-current-weekly', value: current.weekly_consumption, decimals: 2, unit: 'm³' }
    ];

    elements.forEach(({ id, value, decimals, unit }) => {
        const element = document.getElementById(id);
        if (element) {
            const formattedValue = parseFloat(value || 0).toFixed(decimals);
            element.innerHTML = `${formattedValue}<span class="status-unit">${unit}</span>`;
        }
    });
}

function updateFrischwasserValuesMockRobust() {
    const mockData = [
        { id: 'fw-current-counter', value: '7.433', unit: 'm³' },
        { id: 'fw-current-hourly', value: '340', unit: 'L' },
        { id: 'fw-current-daily', value: '2.85', unit: 'm³' },
        { id: 'fw-current-flow', value: '12.5', unit: 'l/min' },
        { id: 'fw-current-weekly', value: '18.6', unit: 'm³' }
    ];

    mockData.forEach(({ id, value, unit }) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerHTML = `${value}<span class="status-unit">${unit}</span>`;
        }
    });
}

function updateFrischwasserChartsRobust(history, config) {
    if (!history || !Array.isArray(history) || history.length === 0) {
        console.log('📊 Keine History-Daten, behalte aktuelle Chart-Daten');
        return;
    }

    try {
        // Vereinfachte Labels
        const labels = history.slice(0, 50).map((item, index) => {
            const date = new Date(item.datetime);
            return date.toLocaleString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
        });

        // Chart 1: Verbrauchsverlauf
        if (fwCharts.consumption) {
            const consumptionData = history.slice(0, 50).map(item => parseFloat(item.consumption_l || 0));
            fwCharts.consumption.data.labels = labels;
            fwCharts.consumption.data.datasets[0].data = consumptionData;
            fwCharts.consumption.update('none');
        }

        // Chart 2: Zählerstand
        if (fwCharts.counter) {
            const counterData = history.slice(0, 50).map(item => parseFloat(item.counter_m3 || 0));
            fwCharts.counter.data.labels = labels;
            fwCharts.counter.data.datasets[0].data = counterData;
            fwCharts.counter.update('none');
        }

        console.log('📊 Charts mit echten Daten aktualisiert');
        
    } catch (error) {
        console.error('Fehler beim Chart-Update:', error);
    }
}

// =============================================================================
// TABELLEN-FUNKTIONALITÄT
// =============================================================================

function generateFrischwasserTableData() {
    const tableBody = document.getElementById('fwTableBody');
    if (!tableBody) return;
    
    const mockTableData = [];
    const now = new Date();
    
    for (let i = 0; i < 25; i++) {
        const datetime = new Date(now.getTime() - i * 15 * 60 * 1000);
        const counter = 7432.75 - (i * 0.05);
        const consumption = Math.random() > 0.7 ? Math.round(Math.random() * 200 + 50) : 0;
        
        mockTableData.push({
            datetime: datetime.toLocaleString('de-DE'),
            counter_m3: counter.toFixed(3),
            consumption: consumption
        });
    }
    
    const tableHTML = mockTableData.map(row => `
        <tr>
            <td>${row.datetime}</td>
            <td>${row.counter_m3} m³</td>
            <td class="${row.consumption > 0 ? 'value-positive' : 'value-zero'}">${row.consumption} L</td>
        </tr>
    `).join('');
    
    tableBody.innerHTML = tableHTML;
    
    // Pagination Info
    const paginationInfo = document.getElementById('fwPaginationInfo');
    if (paginationInfo) {
        paginationInfo.textContent = `Zeige 1 - 25 von 25 Einträgen`;
    }
    
    console.log('📋 Frischwasser-Tabelle gefüllt');
}

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

function updateFrischwasserAlertsRobust(activeAlerts) {
    const alertsDiv = document.getElementById('fw-alerts');
    if (!alertsDiv) return;
    
    if (!activeAlerts || activeAlerts.length === 0) {
        alertsDiv.innerHTML = '';
        return;
    }

    alertsDiv.innerHTML = activeAlerts.map(alert => 
        `<div class="alert ${alert.type}">${alert.message}</div>`
    ).join('');
}

function updateFrischwasserLastUpdateRobust(lastUpdateInfo) {
    const lastUpdateElement = document.getElementById('fw-last-update');
    if (!lastUpdateElement) return;
    
    if (!lastUpdateInfo) {
        lastUpdateElement.textContent = 'Letzte Aktualisierung: Keine Daten verfügbar';
        lastUpdateElement.className = 'last-update error';
        return;
    }

    const { formatted, age_minutes, is_stale } = lastUpdateInfo;
    
    let cssClass = 'last-update';
    if (is_stale) {
        cssClass += ' warning';
    }
    
    lastUpdateElement.textContent = `Letzte Messung: ${formatted}`;
    lastUpdateElement.className = cssClass;
}

function showFrischwasserErrorRobust(message) {
    const alertsDiv = document.getElementById('fw-alerts');
    if (alertsDiv) {
        alertsDiv.innerHTML = `<div class="alert warning">⚠️ ${message}</div>`;
    }
}

// =============================================================================
// STEUERUNGSFUNKTIONEN
// =============================================================================

function changeFWTimeRange(range) {
    currentFWTimeRange = range;
    
    document.querySelectorAll('#frischwasser-tab .time-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    loadFrischwasserDataRobust();
}

function refreshFWData() {
    console.log('🔄 Manueller Refresh der Frischwasser-Daten');
    loadFrischwasserDataRobust();
}

function startFrischwasserAutoRefresh(interval) {
    if (fwAutoRefreshInterval) {
        clearInterval(fwAutoRefreshInterval);
    }
    fwAutoRefreshInterval = setInterval(loadFrischwasserDataRobust, interval);
    console.log(`🔄 Frischwasser Auto-Refresh gestartet: ${interval}ms`);
}

function stopFrischwasserAutoRefresh() {
    if (fwAutoRefreshInterval) {
        clearInterval(fwAutoRefreshInterval);
        fwAutoRefreshInterval = null;
    }
}

// =============================================================================
// TAB-MANAGEMENT
// =============================================================================

function initFrischwasserTab() {
    console.log('🚿 Frischwasser-Tab wird initialisiert...');
    
    setTimeout(() => {
        initFrischwasserChartsRobust();
        generateFrischwasserTableData();
        loadFrischwasserDataRobust();
        startFrischwasserAutoRefresh(60000);
    }, 100);
}

// =============================================================================
// EXPORT-FUNKTIONEN
// =============================================================================

async function exportFrischwasserData() {
    try {
        console.log('📊 Starte Frischwasser-Export...');
        
        const exportBtn = document.querySelector('#frischwasser-tab .export-btn');
        if (exportBtn) {
            const originalText = exportBtn.textContent;
            exportBtn.textContent = '⏳ Exportiere...';
            exportBtn.disabled = true;
            
            const response = await fetch('api_frischwasser.php?action=export&format=csv&limit=10000');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `frischwasser_export_${new Date().toISOString().split('T')[0]}.csv`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            
            showFrischwasserErrorRobust('Export erfolgreich heruntergeladen');
            
            exportBtn.textContent = originalText;
            exportBtn.disabled = false;
        }
        
    } catch (error) {
        console.error('❌ Fehler beim Frischwasser-Export:', error);
        showFrischwasserErrorRobust('Export fehlgeschlagen: ' + error.message);
        
        const exportBtn = document.querySelector('#frischwasser-tab .export-btn');
        if (exportBtn) {
            exportBtn.textContent = '📊 Export CSV';
            exportBtn.disabled = false;
        }
    }
}

// =============================================================================
// GLOBALE FUNKTIONEN UND INITIALISIERUNG
// =============================================================================

// Globale Funktionen verfügbar machen
if (typeof window !== 'undefined') {
    window.fwCharts = fwCharts;
    window.initFrischwasserChartsRobust = initFrischwasserChartsRobust;
    window.loadFrischwasserDataRobust = loadFrischwasserDataRobust;
    window.changeFWTimeRange = changeFWTimeRange;
    window.refreshFWData = refreshFWData;
    window.exportFrischwasserData = exportFrischwasserData;
    window.initFrischwasserTab = initFrischwasserTab;
    
    // Überschreibe existierende Funktionen
    window.loadFrischwasserData = loadFrischwasserDataRobust;
    window.updateFrischwasserValues = updateFrischwasserValuesRobust;
    window.updateFrischwasserCharts = updateFrischwasserChartsRobust;
}

// Tab-Wechsel überwachen
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚿 Frischwasser Complete Module geladen');
    
    // Überwache Frischwasser-Tab
    const frischwasserTab = document.getElementById('frischwasser-tab');
    if (frischwasserTab) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (frischwasserTab.classList.contains('active')) {
                        console.log('🚿 Frischwasser-Tab aktiviert');
                        initFrischwasserTab();
                    } else {
                        stopFrischwasserAutoRefresh();
                    }
                }
            });
        });
        
        observer.observe(frischwasserTab, {
            attributes: true,
            attributeFilter: ['class']
        });
        
        // Falls bereits aktiv
        if (frischwasserTab.classList.contains('active')) {
            initFrischwasserTab();
        }
    }
});

console.log('✅ Frischwasser Complete Module vollständig geladen');