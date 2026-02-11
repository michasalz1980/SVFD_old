let charts = {};
let currentTimeRange = '1h';

// Chart-Konfigurationen für Chart.js 4.5.0
const chartConfigs = {
    totalPower: {
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
                            const value = context.parsed.y;
                            return `Leistung: ${formatPower(value)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Leistung (W)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' },
                    beginAtZero: true
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
    phasePower: {
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
                            const value = context.parsed.y;
                            return `${context.dataset.label}: ${formatPower(value)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Leistung (W)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' },
                    beginAtZero: true
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
    energy: {
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
                            const value = context.parsed.y;
                            const unit = context.dataset.label.includes('Monatsertrag') ? 'kWh' : 'kWh';
                            return `${context.dataset.label}: ${value.toFixed(2)} ${unit}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Tagesertrag (kWh)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' },
                    beginAtZero: true
                },
                y1: {
                    title: { display: true, text: 'Monatsertrag (kWh)' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
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
    temperature: {
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
                            return `Temperatur: ${context.parsed.y.toFixed(1)} °C`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Temperatur (°C)' },
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

// Hilfsfunktion für Leistungsformatierung
function formatPower(watts) {
    if (watts >= 1000) {
        return `${(watts / 1000).toFixed(1)} kW`;
    }
    return `${watts.toFixed(0)} W`;
}

// Hilfsfunktion für Energie-Formatierung
function formatEnergy(wh) {
    if (wh >= 1000000) {
        return `${(wh / 1000000).toFixed(2)} MWh`;
    } else if (wh >= 1000) {
        return `${(wh / 1000).toFixed(1)} kWh`;
    }
    return `${wh.toFixed(0)} Wh`;
}

// Charts initialisieren mit Chart.js 4.5.0
function initCharts() {
    // Gesamtleistung Chart
    const ctx1 = document.getElementById('totalPowerChart').getContext('2d');
    charts.totalPower = new Chart(ctx1, {
        ...chartConfigs.totalPower,
        data: {
            labels: [],
            datasets: [{
                label: 'Gesamtleistung',
                data: [],
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 3
            }]
        }
    });

    // Phasen-Leistung Chart
    const ctx2 = document.getElementById('phasePowerChart').getContext('2d');
    charts.phasePower = new Chart(ctx2, {
        ...chartConfigs.phasePower,
        data: {
            labels: [],
            datasets: [{
                label: 'Phase L1',
                data: [],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: false,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2
            }, {
                label: 'Phase L2',
                data: [],
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                fill: false,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2
            }, {
                label: 'Phase L3',
                data: [],
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: false,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2
            }]
        }
    });

    // Energie Chart (erweitert - Tages- und berechneter Monatsertrag)
    const ctx3 = document.getElementById('energyChart').getContext('2d');
    charts.energy = new Chart(ctx3, {
        ...chartConfigs.energy,
        data: {
            labels: [],
            datasets: [{
                label: 'Tagesertrag',
                data: [],
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2,
                yAxisID: 'y'
            }, {
                label: 'Monatsertrag (berechnet)',
                data: [],
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                fill: false,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            ...chartConfigs.energy.options,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Tagesertrag (kWh)' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Monatsertrag (kWh)' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
                },
                x: {
                    title: { display: true, text: 'Zeit' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            }
        }
    });

    // Temperatur Chart
    const ctx4 = document.getElementById('temperatureChart').getContext('2d');
    charts.temperature = new Chart(ctx4, {
        ...chartConfigs.temperature,
        data: {
            labels: [],
            datasets: [{
                label: 'Temperatur',
                data: [],
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointHoverRadius: 6,
                borderWidth: 2
            }]
        }
    });
}

// Daten laden
async function loadData() {
    try {
        const response = await fetch(`power-api.php?range=${currentTimeRange}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        updateCurrentValues(data.current, data.config);
        updateEnergyValues(data.current, data.config);
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
        console.error('Fehler beim Laden der Daten:', error);
        showError('Fehler beim Laden der Daten: ' + error.message);
    }
}

// Aktuelle Werte aktualisieren
function updateCurrentValues(current, config) {
    if (!current) return;

    const decimals = config?.decimal_places || {};
    const units = config?.units || {};

    // Gesamtleistung
    const totalPower = parseFloat(current.current_feed_total || 0);
    document.getElementById('current-total-power').innerHTML = 
        `${formatPower(totalPower)}<span class="status-unit"></span>`;

    // Betriebsstatus mit farblicher Kodierung (device_status)
    const deviceStatus = parseInt(current.device_status || 0);
    const statusElement = document.getElementById('operation-status');
    let statusText = '';
    let statusClass = '';

    switch (deviceStatus) {
        case 35:
            statusText = '❌ Fehler';
            statusClass = 'status-error';
            break;
        case 303:
            statusText = '⏸️ Aus';
            statusClass = 'status-off';
            break;
        case 307:
            statusText = '✅ OK';
            statusClass = 'status-ok';
            break;
        case 455:
            statusText = '⚠️ Warnung';
            statusClass = 'status-warning';
            break;
        default:
            statusText = `🔧 ${deviceStatus}`;
            statusClass = 'status-unknown';
    }

    statusElement.innerHTML = `${statusText}<span class="status-unit"></span>`;
    statusElement.className = `status-value ${statusClass}`;

    // Temperatur (skaliert)
    const tempRaw = parseFloat(current.temperature || 0);
    const tempCelsius = tempRaw / 10.0; // Skalierung wie im Python-Script
    document.getElementById('current-temperature').innerHTML = 
        `${tempCelsius.toFixed(1)}<span class="status-unit">°C</span>`;
}

// Monatsertrag aus Daily Feeds berechnen
function calculateMonthlyTotal(data) {
    if (!data || data.length === 0) return 0;
    
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    let monthlyTotal = 0;
    
    data.forEach(item => {
        const itemDate = new Date(item.datetime);
        if (itemDate.getMonth() === currentMonth && itemDate.getFullYear() === currentYear) {
            const dailyWh = parseFloat(item.daily_feed_wh || 0);
            monthlyTotal += dailyWh;
        }
    });
    
    return monthlyTotal / 1000; // Wh -> kWh
}

// Neue Funktion für Energie-Werte mit berechnetem Monatsertrag
function updateEnergyValues(current, config, allData = []) {
    if (!current) return;

    // Gesamtenergie (in MWh anzeigen)
    const totalEnergyWh = parseFloat(current.total_feed_wh || 0);
    const totalEnergyMWh = totalEnergyWh / 1000000;
    document.getElementById('total-energy').innerHTML = 
        `${totalEnergyMWh.toFixed(2)}<span class="energy-unit">MWh</span>`;

    // Berechneter Monatsertrag aus Daily Feeds
    const calculatedMonthlyKwh = calculateMonthlyTotal(allData);
    document.getElementById('monthly-energy').innerHTML = 
        `${calculatedMonthlyKwh.toFixed(1)}<span class="energy-unit">kWh</span>`;

    // Tagesertrag
    const dailyWh = parseFloat(current.daily_feed_wh || 0);
    const dailyKwh = dailyWh / 1000;
    document.getElementById('daily-energy').innerHTML = 
        `${dailyKwh.toFixed(1)}<span class="energy-unit">kWh</span>`;

    // Geschätzte Tagesersparnis (30 Cent pro kWh)
    const dailySavings = dailyKwh * 0.30;
    document.getElementById('daily-savings').innerHTML = 
        `${dailySavings.toFixed(2)}<span class="energy-unit">€</span>`;
}

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

    // Gesamtenergie-Info
    const totalEnergyInfoElement = document.getElementById('total-energy-info');
    if (totalEnergyInfoElement) {
        // Wird in updateEnergyValues gesetzt
        const totalElement = document.getElementById('total-energy');
        if (totalElement) {
            totalEnergyInfoElement.textContent = totalElement.textContent;
        }
    }

    // Gerätestatus
    if (systemInfo.device_status) {
        const deviceStatusElement = document.getElementById('device-status');
        if (deviceStatusElement) {
            deviceStatusElement.textContent = systemInfo.device_status;
        }
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

// Charts aktualisieren für Chart.js 4.5.0
function updateCharts(history, config) {
    if (!history || !Array.isArray(history)) return;

    // Intelligente Label-Formatierung basierend auf Zeitraum
    const labels = history.map(item => {
        const date = new Date(item.datetime);
        
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

    // Gesamtleistung Chart
    if (charts.totalPower) {
        charts.totalPower.data.labels = labels;
        charts.totalPower.data.datasets[0].data = history.map(item => parseFloat(item.current_feed_total || 0));
        charts.totalPower.update('none');
    }

    // Phasen-Leistung Chart
    if (charts.phasePower) {
        charts.phasePower.data.labels = labels;
        charts.phasePower.data.datasets[0].data = history.map(item => parseFloat(item.current_feed_l1 || 0));
        charts.phasePower.data.datasets[1].data = history.map(item => parseFloat(item.current_feed_l2 || 0));
        charts.phasePower.data.datasets[2].data = history.map(item => parseFloat(item.current_feed_l3 || 0));
        charts.phasePower.update('none');
    }

// Kumulative Monatserträge für Chart berechnen
function calculateCumulativeMonthlyData(data) {
    if (!data || data.length === 0) return [];
    
    const cumulativeData = [];
    const monthlyTotals = new Map(); // Format: "YYYY-MM" -> total kWh
    
    // Sortiere Daten nach Datum
    const sortedData = [...data].sort((a, b) => new Date(a.datetime) - new Date(b.datetime));
    
    sortedData.forEach(item => {
        const itemDate = new Date(item.datetime);
        const monthKey = `${itemDate.getFullYear()}-${String(itemDate.getMonth() + 1).padStart(2, '0')}`;
        
        // Alle Tageserträge des Monats bis zu diesem Datum summieren
        let monthlyTotal = 0;
        for (const prevItem of sortedData) {
            const prevDate = new Date(prevItem.datetime);
            const prevMonthKey = `${prevDate.getFullYear()}-${String(prevDate.getMonth() + 1).padStart(2, '0')}`;
            
            // Nur Einträge des gleichen Monats und bis zum aktuellen Datum
            if (prevMonthKey === monthKey && prevDate <= itemDate) {
                const dailyWh = parseFloat(prevItem.daily_feed_wh || 0);
                monthlyTotal += dailyWh / 1000; // Wh -> kWh
            }
        }
        
        cumulativeData.push(monthlyTotal);
    });
    
    return cumulativeData;
}

    // Energie Chart (erweitert - Tages- und berechneter Monatsertrag)
    if (charts.energy) {
        charts.energy.data.labels = labels;
        charts.energy.data.datasets[0].data = history.map(item => {
            const dailyWh = parseFloat(item.daily_feed_wh || 0);
            return dailyWh / 1000; // Wh -> kWh
        });
        charts.energy.data.datasets[1].data = calculateCumulativeMonthlyData(history);
        charts.energy.update('none');
    }

    // Temperatur Chart
    if (charts.temperature) {
        charts.temperature.data.labels = labels;
        charts.temperature.data.datasets[0].data = history.map(item => {
            const tempRaw = parseFloat(item.temperature || 0);
            return tempRaw / 10.0; // Skalierung
        });
        charts.temperature.update('none');
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
    document.getElementById('alerts').innerHTML = 
        `<div class="error">❌ ${message}</div>`;
    
    // Letzte Aktualisierung als Fehler markieren
    const lastUpdateElement = document.getElementById('last-update');
    lastUpdateElement.textContent = 'Verbindungsfehler';
    lastUpdateElement.className = 'last-update error';
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

// Daten aktualisieren
function refreshData() {
    loadData();
}

// Hilfsfunktion für Erfolgsmeldung
function showSuccessMessage(message) {
    const alertsDiv = document.getElementById('alerts');
    const successAlert = `<div class="alert success">✅ ${message}</div>`;
    
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

// Initialisierung
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadData();
    loadTableData();
    
    // Event-Listener für Tabellen-Controls
    document.getElementById('entriesPerPage').addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });

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
    
    // Initiales Auto-Refresh (wird von der API überschrieben)
    autoRefreshInterval = setInterval(() => {
        loadData();
        loadTableData();
    }, 30000);
});

// =============================================================================
// TABELLEN-FUNKTIONALITÄT
// =============================================================================

let tableData = [];
let filteredData = [];
let currentPage = 1;
let entriesPerPage = 25;
let totalPages = 1;
let sortColumn = 0;
let sortDirection = 'desc'; // Neueste zuerst

// Tabellendaten laden
async function loadTableData() {
    try {
        const response = await fetch('power-api.php?action=table&page=' + currentPage + '&limit=' + entriesPerPage + '&sort=' + sortColumn + '&direction=' + sortDirection);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        tableData = data.data || [];
        totalPages = data.total_pages || 1;
        const totalEntries = data.total_entries || 0;
        
        renderTable();
        updatePaginationInfo(totalEntries);
        updatePaginationControls();

    } catch (error) {
        console.error('Fehler beim Laden der Tabellendaten:', error);
        document.getElementById('tableBody').innerHTML = 
            '<tr><td colspan="5" class="loading-row"><div class="error">❌ Fehler beim Laden der Tabellendaten</div></td></tr>';
    }
}

// Tabelle rendern
function renderTable() {
    const tbody = document.getElementById('tableBody');
    
    if (!tableData || tableData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="loading-row">Keine Daten verfügbar</td></tr>';
        return;
    }

    const rows = tableData.map(row => {
        const date = new Date(row.datetime);
        const formattedDate = date.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Werte formatieren und färben
        const totalPower = parseFloat(row.current_feed_total || 0);
        const deviceStatus = parseInt(row.device_status || 0);
        const tempRaw = parseFloat(row.temperature || 0);
        const tempCelsius = tempRaw / 10.0;
        const dailyWh = parseFloat(row.daily_feed_wh || 0);
        const dailyKwh = dailyWh / 1000;

        // CSS-Klassen für Werte
        const totalPowerClass = totalPower > 20000 ? 'value-high' : totalPower > 0 ? 'value-normal' : 'value-zero';
        const tempClass = tempCelsius > 60 ? 'value-danger' : tempCelsius > 45 ? 'value-warning' : 'value-normal';
        const dailyClass = dailyKwh > 50 ? 'value-high' : dailyKwh > 0 ? 'value-normal' : 'value-zero';

        // Device Status formatieren
        let statusText = '';
        let statusClass = '';
        switch (deviceStatus) {
            case 35:
                statusText = '❌ Fehler';
                statusClass = 'value-danger';
                break;
            case 303:
                statusText = '⏸️ Aus';
                statusClass = 'value-low';
                break;
            case 307:
                statusText = '✅ OK';
                statusClass = 'value-normal';
                break;
            case 455:
                statusText = '⚠️ Warnung';
                statusClass = 'value-warning';
                break;
            default:
                statusText = `🔧 ${deviceStatus}`;
                statusClass = 'value-zero';
        }

        return `
            <tr>
                <td>${formattedDate}</td>
                <td class="${totalPowerClass}">${formatPower(totalPower)}</td>
                <td class="${statusClass}">${statusText}</td>
                <td class="${tempClass}">${tempCelsius.toFixed(1)} °C</td>
                <td class="${dailyClass}">${dailyKwh.toFixed(1)} kWh</td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;
}

// Pagination-Info aktualisieren
function updatePaginationInfo(totalEntries) {
    const start = (currentPage - 1) * entriesPerPage + 1;
    const end = Math.min(currentPage * entriesPerPage, totalEntries);
    
    document.getElementById('paginationInfo').textContent = 
        `Zeige ${start} - ${end} von ${totalEntries} Einträgen`;
}

// Pagination-Controls aktualisieren
function updatePaginationControls() {
    const firstBtn = document.getElementById('firstPageBtn');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const lastBtn = document.getElementById('lastPageBtn');
    const pageNumbers = document.getElementById('pageNumbers');

    // Buttons aktivieren/deaktivieren
    firstBtn.disabled = currentPage === 1;
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;
    lastBtn.disabled = currentPage === totalPages;

    // Seitenzahlen generieren
    let pagesHtml = '';
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        pagesHtml += `<button class="page-btn ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
    }

    pageNumbers.innerHTML = pagesHtml;
}

// Zu bestimmter Seite springen
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    
    currentPage = page;
    loadTableData();
}

// Tabelle sortieren
function sortTable(column) {
    if (sortColumn === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = column;
        sortDirection = 'desc';
    }

    // Sort-Icons aktualisieren
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.textContent = '⇅';
    });

    const currentIcon = document.querySelectorAll('.sort-icon')[column];
    currentIcon.textContent = sortDirection === 'asc' ? '⬆️' : '⬇️';

    currentPage = 1;
    loadTableData();
}

// =============================================================================
// CSV-EXPORT
// =============================================================================

// Vollständiger Export aller Daten
async function exportTableData() {
    try {
        // Loading-Anzeige
        const exportBtn = document.querySelector('.export-btn');
        const originalText = exportBtn.textContent;
        exportBtn.textContent = '⏳ Exportiere...';
        exportBtn.disabled = true;
        
        // Benutzer fragen ob wirklich alle Daten exportiert werden sollen
        const confirmExport = confirm(
            'Möchten Sie alle verfügbaren Stromdaten exportieren?\n\n' +
            'Je nach Datenmenge kann dies einige Sekunden dauern.\n' +
            'Es werden maximal 50.000 Datensätze exportiert.'
        );
        
        if (!confirmExport) {
            exportBtn.textContent = originalText;
            exportBtn.disabled = false;
            return;
        }
        
        // Export-Request senden
        const response = await fetch('power-api.php?action=export&format=csv&limit=50000');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Prüfen ob es sich um eine Fehlermeldung handelt
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Unbekannter Fehler beim Export');
        }
        
        // CSV-Datei herunterladen
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        
        // Dateiname aus Response-Header oder Fallback
        const contentDisposition = response.headers.get('content-disposition');
        let filename = 'stromdaten_vollstaendig.csv';
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="(.+)"/);
            if (filenameMatch) {
                filename = filenameMatch[1];
            }
        }
        
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        // Erfolg anzeigen
        showSuccessMessage(`Export erfolgreich: ${filename}`);
        
    } catch (error) {
        console.error('Fehler beim Export:', error);
        alert('Fehler beim Export: ' + error.message);
    } finally {
        // Button zurücksetzen
        const exportBtn = document.querySelector('.export-btn');
        exportBtn.textContent = '📊 Export CSV';
        exportBtn.disabled = false;
    }
}