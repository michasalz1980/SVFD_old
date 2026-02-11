// =============================================================================
// KORRIGIERTE SCRIPTS MIT CHART-MANAGEMENT
// Behebt Canvas-Konflikte und verbessert Fehlerbehandlung
// =============================================================================

let charts = {};
let currentTimeRange = '1h';

// =============================================================================
// CHART-MANAGEMENT MIT CLEANUP
// =============================================================================

// Charts sicher zerstören
function destroyCharts() {
    Object.keys(charts).forEach(chartKey => {
        if (charts[chartKey] && typeof charts[chartKey].destroy === 'function') {
            try {
                charts[chartKey].destroy();
                console.log(`Chart ${chartKey} zerstört`);
            } catch (error) {
                console.error(`Fehler beim Zerstören von Chart ${chartKey}:`, error);
            }
        }
    });
    charts = {};
}

// Chart-Konfigurationen für Chart.js 4.5.0
const chartConfigs = {
    wasserstand: {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Wasserstand: ${context.parsed.y.toFixed(1)} cm`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Wasserstand (cm)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                },
                x: {
                    title: { display: true, text: 'Zeit' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            },
            elements: {
                line: { tension: 0.4 },
                point: { radius: 2, hoverRadius: 6 }
            }
        }
    },
    durchfluss: {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Durchfluss: ${context.parsed.y.toFixed(3)} l/s`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Durchfluss (l/s)' },
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                },
                x: {
                    title: { display: true, text: 'Zeit' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            },
            elements: {
                line: { tension: 0.4 },
                point: { radius: 2, hoverRadius: 6 }
            }
        }
    },
    totalizer: {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { 
                    display: true,
                    position: 'top',
                    labels: { usePointStyle: true }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            if (label === 'Totalizer') {
                                return `${label}: ${context.parsed.y.toFixed(2)} m³`;
                            } else {
                                return `${label}: ${context.parsed.y.toFixed(3)} m³`;
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Volumen (m³)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                },
                x: {
                    title: { display: true, text: 'Zeit' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            },
            elements: {
                line: { tension: 0.4 },
                point: { radius: 2, hoverRadius: 6 }
            }
        }
    },
    sensor: {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Sensor Strom: ${context.parsed.y.toFixed(1)} mA`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Strom (mA)' },
                    beginAtZero: true,
                    max: 25,
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                },
                x: {
                    title: { display: true, text: 'Zeit' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            },
            elements: {
                line: { tension: 0.4 },
                point: { radius: 2, hoverRadius: 6 }
            }
        }
    }
};

// Charts sicher initialisieren
function initCharts() {
    console.log('Initialisiere Abwasser-Charts...');
    
    // Alte Charts zerstören falls vorhanden
    destroyCharts();
    
    try {
        // Wasserstand Chart
        const ctx1 = document.getElementById('wasserstanChart');
        if (ctx1) {
            charts.wasserstand = new Chart(ctx1.getContext('2d'), {
                ...chartConfigs.wasserstand,
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Wasserstand',
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                }
            });
            console.log('Wasserstand-Chart erstellt');
        }

        // Durchfluss Chart
        const ctx2 = document.getElementById('durchflussChart');
        if (ctx2) {
            charts.durchfluss = new Chart(ctx2.getContext('2d'), {
                ...chartConfigs.durchfluss,
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Durchfluss',
                        data: [],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                }
            });
            console.log('Durchfluss-Chart erstellt');
        }

        // Totalizer Chart
        const ctx3 = document.getElementById('totalizerChart');
        if (ctx3) {
            charts.totalizer = new Chart(ctx3.getContext('2d'), {
                ...chartConfigs.totalizer,
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Totalizer',
                        data: [],
                        borderColor: '#9b59b6',
                        backgroundColor: 'rgba(155, 89, 182, 0.1)',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }, {
                        label: 'Verbrauch',
                        data: [],
                        borderColor: '#e67e22',
                        backgroundColor: 'rgba(230, 126, 34, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                }
            });
            console.log('Totalizer-Chart erstellt');
        }

        // Sensor Chart
        const ctx4 = document.getElementById('sensorChart');
        if (ctx4) {
            charts.sensor = new Chart(ctx4.getContext('2d'), {
                ...chartConfigs.sensor,
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Sensor Strom',
                        data: [],
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 2
                    }]
                }
            });
            console.log('Sensor-Chart erstellt');
        }
        
        console.log('Alle Abwasser-Charts erfolgreich initialisiert');
        
    } catch (error) {
        console.error('Fehler beim Initialisieren der Charts:', error);
        showError('Fehler beim Laden der Charts: ' + error.message);
    }
}

// =============================================================================
// VERBESSERTE DATENLADUNG MIT FEHLERBEHANDLUNG
// =============================================================================

// Daten laden
async function loadData() {
    try {
        console.log('Lade Abwasser-Daten...');
        const response = await fetch(`api.php?range=${currentTimeRange}`);
        
        // Response-Status prüfen
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Content-Type prüfen
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Keine JSON-Antwort erhalten:', text);
            throw new Error('Server gab keine JSON-Antwort zurück');
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        console.log('Abwasser-Daten erfolgreich geladen:', data);
        
        updateCurrentValues(data.current, data.config);
        updateCharts(data.history, data.config);
        updateAlertsFromAPI(data.active_alerts);
        updateSystemHealth(data.system_health);
        updateLastUpdateDisplay(data.last_update);
        
        // System-Informationen aktualisieren
        if (data.system_info) {
            updateSystemInfo(data.system_info);
        }
        
        // Auto-Refresh-Intervall aus Konfiguration aktualisieren
        if (data.config && data.config.auto_refresh_interval) {
            updateAutoRefreshInterval(data.config.auto_refresh_interval);
        }

    } catch (error) {
        console.error('Fehler beim Laden der Abwasser-Daten:', error);
        showError('Fehler beim Laden der Abwasser-Daten: ' + error.message);
    }
}

// Aktuelle Werte aktualisieren
function updateCurrentValues(current, config) {
    if (!current) {
        console.warn('Keine aktuellen Daten erhalten');
        return;
    }

    const decimals = config?.decimal_places || {};
    const units = config?.units || {};

    try {
        const elements = {
            'current-wasserstand': { value: current.wasserstand, decimals: decimals.wasserstand || 1, unit: units.wasserstand?.symbol || 'cm' },
            'current-durchfluss': { value: current.durchflussrate, decimals: decimals.durchflussrate || 3, unit: units.durchflussrate?.symbol || 'l/s' },
            'current-totalizer': { value: current.totalizer, decimals: decimals.totalizer || 2, unit: units.totalizer?.symbol || 'm³' },
            'current-sensor': { value: current.sensor_strom, decimals: decimals.sensor_strom || 1, unit: units.sensor_strom?.symbol || 'mA' }
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                const { value, decimals, unit } = elements[id];
                element.innerHTML = `${parseFloat(value || 0).toFixed(decimals)}<span class="status-unit">${unit}</span>`;
            }
        });

        // Gesamtverbrauch (v1.2.0)
        if (current.total_consumption !== undefined) {
            const element = document.getElementById('current-total-consumption');
            if (element) {
                element.innerHTML = `${parseFloat(current.total_consumption || 0).toFixed(decimals.consumption || 3)}<span class="status-unit">${units.consumption?.symbol || 'm³'}</span>`;
            }
        }
        
    } catch (error) {
        console.error('Fehler beim Aktualisieren der aktuellen Werte:', error);
    }
}

// Charts aktualisieren für Chart.js 4.5.0
function updateCharts(history, config) {
    if (!history || !Array.isArray(history)) {
        console.warn('Keine Verlaufsdaten erhalten');
        return;
    }

    try {
        // Intelligente Label-Formatierung basierend auf Zeitraum
        const labels = history.map(item => {
            const date = new Date(item.timestamp);
            
            switch (currentTimeRange) {
                case '1h':
                case '6h':
                    return date.toLocaleString('de-DE', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                case '24h':
                    return date.toLocaleString('de-DE', {
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                case '7d':
                    return date.toLocaleString('de-DE', {
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit'
                    });
                case '30d':
                    return date.toLocaleString('de-DE', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit'
                    });
                case '1y':
                    return date.toLocaleString('de-DE', {
                        day: '2-digit',
                        month: '2-digit',
                        year: '2-digit'
                    });
                default:
                    return date.toLocaleString('de-DE', {
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
            }
        });

        // Chart-Konfiguration aus API anwenden
        const chartConfig = config?.charts || {};

        // Wasserstand Chart
        if (charts.wasserstand) {
            charts.wasserstand.data.labels = labels;
            charts.wasserstand.data.datasets[0].data = history.map(item => parseFloat(item.wasserstand || 0));
            
            if (chartConfig.wasserstand) {
                charts.wasserstand.data.datasets[0].borderColor = chartConfig.wasserstand.color || '#3498db';
                charts.wasserstand.data.datasets[0].backgroundColor = chartConfig.wasserstand.background || 'rgba(52, 152, 219, 0.1)';
            }
            
            charts.wasserstand.update('none');
        }

        // Durchfluss Chart
        if (charts.durchfluss) {
            charts.durchfluss.data.labels = labels;
            charts.durchfluss.data.datasets[0].data = history.map(item => parseFloat(item.durchflussrate || 0));
            
            if (chartConfig.durchflussrate) {
                charts.durchfluss.data.datasets[0].borderColor = chartConfig.durchflussrate.color || '#2ecc71';
                charts.durchfluss.data.datasets[0].backgroundColor = chartConfig.durchflussrate.background || 'rgba(46, 204, 113, 0.1)';
            }
            
            charts.durchfluss.update('none');
        }

        // Totalizer & Verbrauch Chart
        if (charts.totalizer) {
            charts.totalizer.data.labels = labels;
            charts.totalizer.data.datasets[0].data = history.map(item => parseFloat(item.totalizer || 0));
            charts.totalizer.data.datasets[1].data = history.map(item => parseFloat(item.consumption || 0));
            
            if (chartConfig.totalizer) {
                charts.totalizer.data.datasets[0].borderColor = chartConfig.totalizer.color || '#9b59b6';
                charts.totalizer.data.datasets[0].backgroundColor = chartConfig.totalizer.background || 'rgba(155, 89, 182, 0.1)';
            }
            
            if (chartConfig.consumption) {
                charts.totalizer.data.datasets[1].borderColor = chartConfig.consumption.color || '#e67e22';
                charts.totalizer.data.datasets[1].backgroundColor = chartConfig.consumption.background || 'rgba(230, 126, 34, 0.1)';
            }
            
            charts.totalizer.update('none');
        }

        // Sensor Chart
        if (charts.sensor) {
            charts.sensor.data.labels = labels;
            charts.sensor.data.datasets[0].data = history.map(item => parseFloat(item.sensor_strom || 0));
            
            if (chartConfig.sensor_strom) {
                charts.sensor.data.datasets[0].borderColor = chartConfig.sensor_strom.color || '#f39c12';
                charts.sensor.data.datasets[0].backgroundColor = chartConfig.sensor_strom.background || 'rgba(243, 156, 18, 0.1)';
            }
            
            charts.sensor.update('none');
        }
        
        console.log('Charts erfolgreich aktualisiert');
        
    } catch (error) {
        console.error('Fehler beim Aktualisieren der Charts:', error);
    }
}

// =============================================================================
// RESTLICHE FUNKTIONEN (UNVERÄNDERT)
// =============================================================================

// System-Informationen aktualisieren
function updateSystemInfo(systemInfo) {
    if (!systemInfo) return;

    // Version aktualisieren
    const versionElement = document.getElementById('version-info');
    const systemVersionElement = document.getElementById('system-version');
    if (versionElement) versionElement.textContent = `v${systemInfo.version}`;
    if (systemVersionElement) systemVersionElement.textContent = `v${systemInfo.version}`;

    // Erstes Messdatum
    const firstMeasurementElement = document.getElementById('first-measurement');
    if (firstMeasurementElement && systemInfo.first_measurement) {
        firstMeasurementElement.textContent = systemInfo.first_measurement.formatted;
    }

    // System-Laufzeit
    const systemUptimeElement = document.getElementById('system-uptime');
    if (systemUptimeElement && systemInfo.first_measurement) {
        const days = systemInfo.first_measurement.days_since;
        const years = Math.floor(days / 365);
        const remainingDays = days % 365;
        
        if (years > 0) {
            systemUptimeElement.textContent = `${years} Jahr(e), ${remainingDays} Tage`;
        } else if (days > 0) {
            systemUptimeElement.textContent = `${days} Tage`;
        } else {
            systemUptimeElement.textContent = 'Weniger als 1 Tag';
        }
    }

    // Letzte API-Aktualisierung
    const lastApiCallElement = document.getElementById('last-api-call');
    if (lastApiCallElement) {
        lastApiCallElement.textContent = new Date().toLocaleString('de-DE');
    }
}

// System-Info-Panel umschalten
function toggleSystemInfo() {
    const panel = document.getElementById('systemInfoPanel');
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        panel.style.display = 'none';
    }
}

// Warnungen aus API anzeigen
function updateAlertsFromAPI(activeAlerts) {
    const alertsDiv = document.getElementById('alerts');
    
    if (!activeAlerts || activeAlerts.length === 0) {
        alertsDiv.innerHTML = '';
        return;
    }

    alertsDiv.innerHTML = activeAlerts.map(alert => 
        `<div class="alert ${alert.type}">${alert.message}</div>`
    ).join('');
}

// System-Gesundheit anzeigen
function updateSystemHealth(systemHealth) {
    if (!systemHealth) return;

    const alertsDiv = document.getElementById('alerts');
    let healthAlerts = [];

    if (systemHealth.status === 'WARNING') {
        if (systemHealth.data_age_warning) {
            healthAlerts.push({
                type: 'warning',
                message: `⏰ Daten veraltet: Letzte Aktualisierung vor ${systemHealth.data_age_minutes} Minuten`
            });
        }

        if (systemHealth.error_rate > systemHealth.max_error_rate) {
            healthAlerts.push({
                type: 'warning',
                message: `🔧 Hohe Fehlerrate: ${systemHealth.error_rate}% (max. ${systemHealth.max_error_rate}%)`
            });
        }

        if (systemHealth.last_error) {
            healthAlerts.push({
                type: 'warning',
                message: `❌ Letzter Fehler: ${systemHealth.last_error.message}`
            });
        }
    }

    // System-Warnungen zu bestehenden Alerts hinzufügen
    if (healthAlerts.length > 0) {
        const existingAlerts = alertsDiv.innerHTML;
        const newAlerts = healthAlerts.map(alert => 
            `<div class="alert ${alert.type}">${alert.message}</div>`
        ).join('');
        alertsDiv.innerHTML = existingAlerts + newAlerts;
    }
}

// Letzte Aktualisierung anzeigen
function updateLastUpdateDisplay(lastUpdateInfo) {
    const lastUpdateElement = document.getElementById('last-update');
    
    if (!lastUpdateInfo) {
        lastUpdateElement.textContent = 'Letzte Aktualisierung: Keine Daten verfügbar';
        lastUpdateElement.className = 'last-update error';
        return;
    }

    const { formatted, age_minutes, is_stale, needs_attention, max_age_minutes, attention_age_minutes } = lastUpdateInfo;
    
    // CSS-Klasse basierend auf Datenalter
    let cssClass = 'last-update';
    if (is_stale) {
        cssClass += ' warning';
    } else if (needs_attention) {
        cssClass += ' attention';
    }
    
    lastUpdateElement.textContent = `Letzte Messung: ${formatted}`;
    lastUpdateElement.className = cssClass;
    
    // Tooltip mit detaillierten Informationen
    const tooltipText = [
        `Daten sind ${age_minutes.toFixed(1)} Minuten alt`,
        `Achtung ab: ${attention_age_minutes} Minuten`,
        `Warnung ab: ${max_age_minutes} Minuten`,
        `Status: ${is_stale ? 'Veraltet' : needs_attention ? 'Achtung' : 'Aktuell'}`
    ].join('\n');
    
    lastUpdateElement.title = tooltipText;
}

// Auto-Refresh-Intervall aktualisieren
let autoRefreshInterval;
function updateAutoRefreshInterval(interval) {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    autoRefreshInterval = setInterval(loadData, interval);
}

// Fehler anzeigen
function showError(message) {
    console.error('Zeige Fehler:', message);
    const alertsDiv = document.getElementById('alerts');
    if (alertsDiv) {
        alertsDiv.innerHTML = `<div class="error">❌ ${message}</div>`;
    }
    
    // Letzte Aktualisierung als Fehler markieren
    const lastUpdateElement = document.getElementById('last-update');
    if (lastUpdateElement) {
        lastUpdateElement.textContent = 'Verbindungsfehler';
        lastUpdateElement.className = 'last-update error';
    }
}

// Zeitraum ändern
function changeTimeRange(range) {
    currentTimeRange = range;
    
    // Buttons aktualisieren
    document.querySelectorAll('#abwasser-tab .time-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    loadData();
}

// Daten aktualisieren
function refreshData() {
    console.log('Manueller Refresh der Abwasser-Daten');
    loadData();
}

// Hilfsfunktion für Erfolgsmeldung
function showSuccessMessage(message) {
    const alertsDiv = document.getElementById('alerts');
    const successAlert = `<div class="alert success" style="background: #d4edda; border-color: #c3e6cb; color: #155724;">✅ ${message}</div>`;
    
    if (alertsDiv) {
        const existingAlerts = alertsDiv.innerHTML;
        alertsDiv.innerHTML = successAlert + existingAlerts;
        
        // Nach 5 Sekunden automatisch entfernen
        setTimeout(() => {
            const successAlerts = alertsDiv.querySelectorAll('.alert.success');
            if (successAlerts.length > 0) {
                successAlerts[0].remove();
            }
        }, 5000);
    }
}

// =============================================================================
// ERWEITERTE INITIALISIERUNG
// =============================================================================

// Initialisierung
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard wird initialisiert...');
    
    // Charts nur initialisieren wenn Abwasser-Tab aktiv
    const abwasserTab = document.getElementById('abwasser-tab');
    if (abwasserTab && abwasserTab.classList.contains('active')) {
        initCharts();
        loadData();
    }
    
    // Tabellen-Funktionen nur wenn vorhanden
    if (typeof loadTableData === 'function') {
        loadTableData();
    }
    
    // Event-Listener für Tabellen-Controls
    const entriesSelect = document.getElementById('entriesPerPage');
    if (entriesSelect) {
        entriesSelect.addEventListener('change', function() {
            if (typeof entriesPerPage !== 'undefined' && typeof currentPage !== 'undefined' && typeof renderTable === 'function') {
                entriesPerPage = parseInt(this.value);
                currentPage = 1;
                renderTable();
            }
        });
    }

    // ESC-Taste zum Schließen des System-Info-Panels
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const panel = document.getElementById('systemInfoPanel');
            if (panel && panel.style.display !== 'none') {
                toggleSystemInfo();
            }
        }
    });

    // Klick außerhalb des Panels zum Schließen
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('systemInfoPanel');
        const infoBtn = event.target.closest('.info-btn');
        const panelContent = event.target.closest('.system-info-panel');
        
        if (panel && panel.style.display !== 'none' && !infoBtn && !panelContent) {
            toggleSystemInfo();
        }
    });
    
    // Initiales Auto-Refresh nur für Abwasser-Tab
    if (abwasserTab && abwasserTab.classList.contains('active')) {
        autoRefreshInterval = setInterval(() => {
            loadData();
            if (typeof loadTableData === 'function') {
                loadTableData();
            }
        }, 30000);
    }
    
    console.log('✅ Dashboard-Initialisierung abgeschlossen');
});

// =============================================================================
// TAB-SWITCHING MIT CHART-MANAGEMENT
// =============================================================================

// Erweiterte Tab-Switch Funktion
function switchTab(tabName) {
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
    
    // Auto-Refresh für alle Tabs stoppen
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
    
    // Charts für Abwasser zerstören wenn weg vom Tab
    if (tabName !== 'abwasser') {
        destroyCharts();
    }
    
    // Spezifische Funktionen für jeden Tab
    switch (tabName) {
        case 'frischwasser':
            if (typeof loadFrischwasserData === 'function') {
                loadFrischwasserData();
            }
            if (typeof startFrischwasserAutoRefresh === 'function') {
                startFrischwasserAutoRefresh(60000);
            }
            break;
        case 'overview':
            if (typeof loadOverviewData === 'function') {
                loadOverviewData();
            }
            break;
        case 'abwasser':
        default:
            // Charts neu initialisieren für Abwasser
            setTimeout(() => {
                initCharts();
                loadData();
            }, 100); // Kurze Verzögerung für DOM-Update
            
            // Auto-Refresh für Abwasser wieder starten
            autoRefreshInterval = setInterval(() => {
                loadData();
                if (typeof loadTableData === 'function') {
                    loadTableData();
                }
            }, 30000);
            break;
    }
}

// =============================================================================
// TABELLEN-FUNKTIONALITÄT (FALLS NOCH NICHT DEFINIERT)
// =============================================================================

// Placeholder-Funktionen falls scripts.js nicht vollständig geladen
if (typeof loadTableData === 'undefined') {
    window.loadTableData = function() {
        console.log('loadTableData Placeholder - vollständige Implementierung in scripts.js');
    };
}

if (typeof exportTableData === 'undefined') {
    window.exportTableData = function() {
        console.log('exportTableData Placeholder - vollständige Implementierung in scripts.js');
    };
}