// =============================================================================
// VEREINFACHTE FRISCHWASSER DASHBOARD FUNKTIONEN
// Robuste Version mit verbesserter Fehlerbehandlung
// =============================================================================

let fwCharts = {};
let currentFWTimeRange = '24h';
let fwAutoRefreshInterval;

// =============================================================================
// FRISCHWASSER CHART-MANAGEMENT
// =============================================================================

// Frischwasser Charts sicher zerstören
function destroyFWCharts() {
    Object.keys(fwCharts).forEach(chartKey => {
        if (fwCharts[chartKey] && typeof fwCharts[chartKey].destroy === 'function') {
            try {
                fwCharts[chartKey].destroy();
                console.log(`Frischwasser Chart ${chartKey} zerstört`);
            } catch (error) {
                console.error(`Fehler beim Zerstören von FW-Chart ${chartKey}:`, error);
            }
        }
    });
    fwCharts = {};
}

// Frischwasser Charts initialisieren
function initFrischwasserCharts() {
    console.log('Initialisiere Frischwasser-Charts...');
    
    // Alte Charts zerstören
    destroyFWCharts();
    
    try {
        // Verbrauchsverlauf Chart
        const ctx1 = document.getElementById('fwConsumptionChart');
        if (ctx1) {
            fwCharts.consumption = new Chart(ctx1.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Verbrauch',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            title: { display: true, text: 'Verbrauch (L)' },
                            beginAtZero: true
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
            console.log('Frischwasser Verbrauch-Chart erstellt');
        }

        // Zählerstand Chart
        const ctx2 = document.getElementById('fwCounterChart');
        if (ctx2) {
            fwCharts.counter = new Chart(ctx2.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Zählerstand',
                        data: [],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
            console.log('Frischwasser Zählerstand-Chart erstellt');
        }

        // Tagesverbrauch Chart (vereinfacht)
        const ctx3 = document.getElementById('fwDailyChart');
        if (ctx3) {
            fwCharts.daily = new Chart(ctx3.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Tagesverbrauch',
                        data: [],
                        backgroundColor: 'rgba(231, 76, 60, 0.7)',
                        borderColor: '#e74c3c',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            title: { display: true, text: 'Tagesverbrauch (m³)' },
                            beginAtZero: true
                        },
                        x: {
                            title: { display: true, text: 'Tag' }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Tagesverbrauch: ${context.parsed.y.toFixed(2)} m³`;
                                }
                            }
                        }
                    }
                }
            });
            console.log('Frischwasser Tagesverbrauch-Chart erstellt');
        }

        // Muster Chart (vereinfacht)
        const ctx4 = document.getElementById('fwPatternChart');
        if (ctx4) {
            fwCharts.pattern = new Chart(ctx4.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Stündlicher Verbrauch',
                        data: [],
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 7,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            title: { display: true, text: 'Verbrauch (L)' },
                            beginAtZero: true
                        },
                        x: {
                            title: { display: true, text: 'Uhrzeit' }
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
            console.log('Frischwasser Pattern-Chart erstellt');
        }
        
        console.log('Alle Frischwasser-Charts erfolgreich initialisiert');
        
    } catch (error) {
        console.error('Fehler beim Initialisieren der Frischwasser-Charts:', error);
        showFrischwasserError('Fehler beim Laden der Charts: ' + error.message);
    }
}

// =============================================================================
// FRISCHWASSER DATEN LADEN
// =============================================================================

// Frischwasser Daten laden
async function loadFrischwasserData() {
    try {
        console.log('Lade Frischwasser-Daten...');
        
        // API-URL mit korrigierter Version
        const apiUrl = 'api_frischwasser.php';
        const response = await fetch(`${apiUrl}?range=${currentFWTimeRange}`);
        
        // Response-Status prüfen
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Content-Type prüfen
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Keine JSON-Antwort von Frischwasser-API:', text);
            throw new Error('Server gab keine JSON-Antwort zurück');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Unbekannter API-Fehler');
        }

        console.log('Frischwasser-Daten erfolgreich geladen:', data);
        
        updateFrischwasserValues(data.current, data.config);
        updateFrischwasserCharts(data.history, data.config);
        updateFrischwasserAlerts(data.active_alerts);
        updateFrischwasserLastUpdate(data.last_update);

    } catch (error) {
        console.error('Fehler beim Laden der Frischwasser-Daten:', error);
        showFrischwasserError('Fehler beim Laden der Frischwasser-Daten: ' + error.message);
        
        // Fallback auf Mock-Daten für Demo
        updateFrischwasserValuesMock();
    }
}

// Frischwasser Werte aktualisieren
function updateFrischwasserValues(current, config) {
    if (!current) {
        console.warn('Keine aktuellen Frischwasser-Daten erhalten');
        updateFrischwasserValuesMock();
        return;
    }

    const decimals = config?.decimal_places || {};

    try {
        // Zählerstand
        const counterElement = document.getElementById('fw-current-counter');
        if (counterElement) {
            counterElement.innerHTML = `${parseFloat(current.counter_m3 || 0).toFixed(decimals.counter_m3 || 3)}<span class="status-unit">m³</span>`;
        }
        
        // Stunden-Verbrauch
        const hourlyElement = document.getElementById('fw-current-hourly');
        if (hourlyElement) {
            hourlyElement.innerHTML = `${parseFloat(current.hourly_consumption || 0).toFixed(decimals.consumption_l || 1)}<span class="status-unit">L</span>`;
        }
        
        // Tages-Verbrauch
        const dailyElement = document.getElementById('fw-current-daily');
        if (dailyElement) {
            dailyElement.innerHTML = `${parseFloat(current.daily_consumption || 0).toFixed(decimals.daily_m3 || 2)}<span class="status-unit">m³</span>`;
        }
        
        // Aktueller Durchfluss
        const flowElement = document.getElementById('fw-current-flow');
        if (flowElement) {
            flowElement.innerHTML = `${parseFloat(current.current_flow_lmin || 0).toFixed(decimals.flow_lmin || 1)}<span class="status-unit">l/min</span>`;
        }
        
        // Wochen-Verbrauch
        const weeklyElement = document.getElementById('fw-current-weekly');
        if (weeklyElement) {
            weeklyElement.innerHTML = `${parseFloat(current.weekly_consumption || 0).toFixed(decimals.weekly_m3 || 2)}<span class="status-unit">m³</span>`;
        }
        
        console.log('Frischwasser-Werte erfolgreich aktualisiert');
        
    } catch (error) {
        console.error('Fehler beim Aktualisieren der Frischwasser-Werte:', error);
        updateFrischwasserValuesMock();
    }
}

// Mock-Daten für Demonstration/Fallback
function updateFrischwasserValuesMock() {
    console.log('Verwende Mock-Daten für Frischwasser');
    
    const mockData = {
        counter: '1.236',
        hourly: '340',
        daily: '2.85',
        flow: '12.5',
        weekly: '18.6'
    };
    
    const elements = {
        'fw-current-counter': { value: mockData.counter, unit: 'm³' },
        'fw-current-hourly': { value: mockData.hourly, unit: 'L' },
        'fw-current-daily': { value: mockData.daily, unit: 'm³' },
        'fw-current-flow': { value: mockData.flow, unit: 'l/min' },
        'fw-current-weekly': { value: mockData.weekly, unit: 'm³' }
    };
    
    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            const { value, unit } = elements[id];
            element.innerHTML = `${value}<span class="status-unit">${unit}</span>`;
        }
    });
}

// Frischwasser Charts aktualisieren (vereinfacht)
function updateFrischwasserCharts(history, config) {
    if (!history || !Array.isArray(history) || history.length === 0) {
        console.warn('Keine Frischwasser-Verlaufsdaten erhalten');
        updateFrischwasserChartsMock();
        return;
    }

    try {
        // Labels erstellen
        const labels = history.map(item => {
            const date = new Date(item.datetime);
            return date.toLocaleString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        });

        // Verbrauchsverlauf Chart
        if (fwCharts.consumption) {
            fwCharts.consumption.data.labels = labels;
            fwCharts.consumption.data.datasets[0].data = history.map(item => parseFloat(item.consumption_l || 0));
            fwCharts.consumption.update('none');
        }

        // Zählerstand Chart
        if (fwCharts.counter) {
            fwCharts.counter.data.labels = labels;
            fwCharts.counter.data.datasets[0].data = history.map(item => parseFloat(item.counter_m3 || 0));
            fwCharts.counter.update('none');
        }

        // Vereinfachte Tagesverbrauch-Berechnung
        if (fwCharts.daily) {
            const dailyData = calculateDailyData(history);
            fwCharts.daily.data.labels = dailyData.labels;
            fwCharts.daily.data.datasets[0].data = dailyData.values;
            fwCharts.daily.update('none');
        }

        // Vereinfachtes Stunden-Pattern
        if (fwCharts.pattern) {
            const hourlyData = calculateHourlyPattern(history);
            fwCharts.pattern.data.labels = hourlyData.labels;
            fwCharts.pattern.data.datasets[0].data = hourlyData.values;
            fwCharts.pattern.update('none');
        }
        
        console.log('Frischwasser-Charts erfolgreich aktualisiert');
        
    } catch (error) {
        console.error('Fehler beim Aktualisieren der Frischwasser-Charts:', error);
        updateFrischwasserChartsMock();
    }
}

// Mock-Charts für Fallback
function updateFrischwasserChartsMock() {
    console.log('Verwende Mock-Charts für Frischwasser');
    
    // Generiere Test-Daten
    const now = new Date();
    const mockLabels = [];
    const mockConsumption = [];
    const mockCounter = [];
    
    for (let i = 23; i >= 0; i--) {
        const time = new Date(now.getTime() - i * 60 * 60 * 1000);
        mockLabels.push(time.toLocaleString('de-DE', { hour: '2-digit', minute: '2-digit' }));
        mockConsumption.push(Math.random() * 200 + 50); // 50-250 L
        mockCounter.push(1236 + (23 - i) * 0.1); // Steigender Zählerstand
    }
    
    if (fwCharts.consumption) {
        fwCharts.consumption.data.labels = mockLabels;
        fwCharts.consumption.data.datasets[0].data = mockConsumption;
        fwCharts.consumption.update('none');
    }
    
    if (fwCharts.counter) {
        fwCharts.counter.data.labels = mockLabels;
        fwCharts.counter.data.datasets[0].data = mockCounter;
        fwCharts.counter.update('none');
    }
}

// =============================================================================
// HILFSFUNKTIONEN
// =============================================================================

// Berechne Tagesverbrauch aus Verlaufsdaten
function calculateDailyData(history) {
    const dailyMap = {};
    
    history.forEach(item => {
        const date = new Date(item.datetime);
        const dateKey = date.toISOString().split('T')[0];
        
        if (!dailyMap[dateKey]) {
            dailyMap[dateKey] = 0;
        }
        dailyMap[dateKey] += parseFloat(item.consumption_l || 0);
    });
    
    const labels = Object.keys(dailyMap).map(dateKey => {
        const date = new Date(dateKey);
        return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
    });
    
    const values = Object.values(dailyMap).map(val => val / 1000); // Liter zu m³
    
    return { labels, values };
}

// Berechne Stunden-Pattern aus Verlaufsdaten
function calculateHourlyPattern(history) {
    const hourlyMap = {};
    
    // Initialisiere alle Stunden
    for (let hour = 0; hour < 24; hour++) {
        hourlyMap[hour] = [];
    }
    
    history.forEach(item => {
        const date = new Date(item.datetime);
        const hour = date.getHours();
        hourlyMap[hour].push(parseFloat(item.consumption_l || 0));
    });
    
    const labels = Object.keys(hourlyMap).map(hour => `${hour.padStart(2, '0')}:00`);
    const values = Object.values(hourlyMap).map(hourData => {
        if (hourData.length === 0) return 0;
        return hourData.reduce((sum, val) => sum + val, 0) / hourData.length;
    });
    
    return { labels, values };
}

// Frischwasser Alarme anzeigen
function updateFrischwasserAlerts(activeAlerts) {
    const alertsDiv = document.getElementById('fw-alerts');
    
    if (!alertsDiv) {
        console.warn('fw-alerts Element nicht gefunden');
        return;
    }
    
    if (!activeAlerts || activeAlerts.length === 0) {
        alertsDiv.innerHTML = '';
        return;
    }

    alertsDiv.innerHTML = activeAlerts.map(alert => 
        `<div class="alert ${alert.type}">${alert.message}</div>`
    ).join('');
}

// Frischwasser Letzte Aktualisierung
function updateFrischwasserLastUpdate(lastUpdateInfo) {
    const lastUpdateElement = document.getElementById('fw-last-update');
    
    if (!lastUpdateElement) {
        console.warn('fw-last-update Element nicht gefunden');
        return;
    }
    
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

// Frischwasser Fehler anzeigen
function showFrischwasserError(message) {
    console.error('Frischwasser-Fehler:', message);
    
    const alertsDiv = document.getElementById('fw-alerts');
    if (alertsDiv) {
        alertsDiv.innerHTML = `<div class="error">❌ ${message}</div>`;
    }
    
    const lastUpdateElement = document.getElementById('fw-last-update');
    if (lastUpdateElement) {
        lastUpdateElement.textContent = 'Verbindungsfehler';
        lastUpdateElement.className = 'last-update error';
    }
}

// =============================================================================
// STEUERUNGSFUNKTIONEN
// =============================================================================

// Frischwasser Zeitraum ändern
function changeFWTimeRange(range) {
    currentFWTimeRange = range;
    
    // Buttons aktualisieren
    document.querySelectorAll('#frischwasser-tab .time-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    loadFrischwasserData();
}

// Frischwasser Daten aktualisieren
function refreshFWData() {
    console.log('Manueller Refresh der Frischwasser-Daten');
    loadFrischwasserData();
}

// Auto-Refresh für Frischwasser
function startFrischwasserAutoRefresh(interval) {
    if (fwAutoRefreshInterval) {
        clearInterval(fwAutoRefreshInterval);
    }
    fwAutoRefreshInterval = setInterval(loadFrischwasserData, interval);
    console.log(`Frischwasser Auto-Refresh gestartet: ${interval}ms`);
}

function stopFrischwasserAutoRefresh() {
    if (fwAutoRefreshInterval) {
        clearInterval(fwAutoRefreshInterval);
        fwAutoRefreshInterval = null;
        console.log('Frischwasser Auto-Refresh gestoppt');
    }
}

// =============================================================================
// ERWEITERTE TAB-FUNKTIONEN
// =============================================================================

// Erweiterte switchTab Funktion (falls nicht in der Hauptdatei definiert)
if (typeof switchTab === 'undefined') {
    window.switchTab = function(tabName) {
        console.log(`Wechsle zu Tab: ${tabName}`);
        
        // Alle Tabs ausblenden
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Alle Tab-Buttons deaktivieren
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Gewählten Tab anzeigen
        const targetTab = document.getElementById(tabName + '-tab');
        if (targetTab) {
            targetTab.classList.add('active');
        }
        if (event && event.target) {
            event.target.classList.add('active');
        }
        
        // Auto-Refresh stoppen
        stopFrischwasserAutoRefresh();
        
        // Charts zerstören wenn weg vom Frischwasser-Tab
        if (tabName !== 'frischwasser') {
            destroyFWCharts();
        }
        
        // Tab-spezifische Aktionen
        switch (tabName) {
            case 'frischwasser':
                setTimeout(() => {
                    initFrischwasserCharts();
                    loadFrischwasserData();
                    startFrischwasserAutoRefresh(60000);
                }, 100);
                break;
            case 'overview':
                if (typeof loadOverviewData === 'function') {
                    loadOverviewData();
                }
                break;
            case 'abwasser':
            default:
                // Abwasser-spezifische Aktionen
                if (typeof initCharts === 'function') {
                    setTimeout(initCharts, 100);
                }
                if (typeof loadData === 'function') {
                    setTimeout(loadData, 200);
                }
                break;
        }
    };
}

// =============================================================================
// ÜBERSICHT-FUNKTIONEN (VEREINFACHT)
// =============================================================================

// Übersichtsdaten laden (Mock für Demo)
function loadOverviewData() {
    console.log('Lade Übersichtsdaten...');
    
    // Mock-Daten für Demo
    const mockData = {
        freshDaily: '2.85',
        wasteDaily: '2.12',
        balance: '0.73',
        efficiency: '74.4'
    };
    
    const overviewElements = {
        'overview-fresh-daily': mockData.freshDaily + ' m³',
        'overview-waste-daily': mockData.wasteDaily + ' m³',
        'overview-balance': mockData.balance + ' m³',
        'overview-efficiency': mockData.efficiency + '%'
    };
    
    Object.keys(overviewElements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = overviewElements[id];
        }
    });
    
    // Effizienz-Farbe setzen
    const efficiencyElement = document.getElementById('overview-efficiency');
    if (efficiencyElement) {
        const efficiency = parseFloat(mockData.efficiency);
        if (efficiency > 80) {
            efficiencyElement.style.color = '#27ae60'; // Grün
        } else if (efficiency > 60) {
            efficiencyElement.style.color = '#f39c12'; // Orange
        } else {
            efficiencyElement.style.color = '#e74c3c'; // Rot
        }
    }
    
    console.log('Übersichtsdaten geladen (Mock)');
}

// =============================================================================
// INITIALISIERUNG
// =============================================================================

// Erweiterte Initialisierung für Frischwasser
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚿 Frischwasser-Module werden initialisiert...');
    
    // Nur initialisieren wenn Frischwasser-Tab existiert
    const frischwasserTab = document.getElementById('frischwasser-tab');
    if (frischwasserTab) {
        console.log('✅ Frischwasser-Tab gefunden');
        
        // Wenn Frischwasser-Tab aktiv ist, Charts initialisieren
        if (frischwasserTab.classList.contains('active')) {
            initFrischwasserCharts();
            loadFrischwasserData();
            startFrischwasserAutoRefresh(60000);
        }
    } else {
        console.warn('⚠️ Frischwasser-Tab nicht gefunden');
    }
    
    // Übersicht-Tab
    const overviewTab = document.getElementById('overview-tab');
    if (overviewTab && overviewTab.classList.contains('active')) {
        loadOverviewData();
    }
    
    console.log('✅ Frischwasser-Module-Initialisierung abgeschlossen');
});

// =============================================================================
// EXPORT-FUNKTIONEN
// =============================================================================

// Frischwasser Daten exportieren (vereinfacht)
async function exportFrischwasserData() {
    try {
        console.log('Starte Frischwasser-Export...');
        
        const exportBtn = document.querySelector('#frischwasser-tab .export-btn');
        if (!exportBtn) {
            console.warn('Export-Button nicht gefunden');
            return;
        }
        
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
        
        if (typeof showSuccessMessage === 'function') {
            showSuccessMessage('Frischwasser-Export erfolgreich');
        }
        
    } catch (error) {
        console.error('Fehler beim Frischwasser-Export:', error);
        alert('Fehler beim Export: ' + error.message);
    } finally {
        const exportBtn = document.querySelector('#frischwasser-tab .export-btn');
        if (exportBtn) {
            exportBtn.textContent = '📊 Export CSV';
            exportBtn.disabled = false;
        }
    }
}

console.log('✅ Frischwasser-JavaScript-Module geladen');