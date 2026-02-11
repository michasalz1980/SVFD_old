let charts = {};
let currentTimeRange = '1h';

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
                            return `Durchfluss: ${context.parsed.y.toFixed(1)} l/s`;
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
                legend: { display: false }, // Keine Legende nötig, da nur Verbrauch
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Verbrauch: ${context.parsed.y.toFixed(1)} m³`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Verbrauch (m³)' },
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
    // Sensor Chart Konfiguration entfernt
};

// Charts initialisieren mit Chart.js 4.5.0
function initCharts() {
    // Wasserstand Chart
    const ctx1 = document.getElementById('wasserstanChart').getContext('2d');
    charts.wasserstand = new Chart(ctx1, {
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

    // Durchfluss Chart
    const ctx2 = document.getElementById('durchflussChart').getContext('2d');
    charts.durchfluss = new Chart(ctx2, {
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

    // Verbrauch Chart (nur Verbrauch, kein Totalizer mehr)
    const ctx3 = document.getElementById('totalizerChart').getContext('2d');
    charts.totalizer = new Chart(ctx3, {
        ...chartConfigs.totalizer,
        data: {
            labels: [],
            datasets: [{
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

    // Sensor Chart entfernt
}

// Daten laden
async function loadData() {
    try {
        const response = await fetch(`api.php?range=${currentTimeRange}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        updateCurrentValues(data.current, data.config);
        updateCharts(data.history, data.config);
        updateAlertsFromAPI(data.active_alerts);
        updateSystemHealth(data.system_health);
        updateLastUpdateDisplay(data.last_update);
        
        // System-Informationen aktualisieren (NEUE FUNKTION)
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

    document.getElementById('current-wasserstand').innerHTML = 
        `${parseFloat(current.wasserstand || 0).toFixed(decimals.wasserstand || 1)}<span class="status-unit">${units.wasserstand?.symbol || 'cm'}</span>`;
    document.getElementById('current-durchfluss').innerHTML = 
        `${parseFloat(current.durchflussrate || 0).toFixed(decimals.durchflussrate || 1)}<span class="status-unit">${units.durchflussrate?.symbol || 'l/s'}</span>`;
    document.getElementById('current-totalizer').innerHTML = 
        `${parseFloat(current.totalizer || 0).toFixed(decimals.totalizer || 1)}<span class="status-unit">${units.totalizer?.symbol || 'm³'}</span>`;
    document.getElementById('current-sensor').innerHTML = 
        `${parseFloat(current.sensor_strom || 0).toFixed(decimals.sensor_strom || 1)}<span class="status-unit">${units.sensor_strom?.symbol || 'mA'}</span>`;
    
    // Neues Element: Gesamtverbrauch (v1.2.0)
    if (current.total_consumption !== undefined) {
        document.getElementById('current-total-consumption').innerHTML = 
            `${parseFloat(current.total_consumption || 0).toFixed(decimals.consumption || 1)}<span class="status-unit">${units.consumption?.symbol || 'm³'}</span>`;
    }
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
}

// System-Info-Panel umschalten
function toggleSystemInfo() {
    const panel = document.getElementById('systemInfoPanel');
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
        // Scroll to panel
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
        
        charts.wasserstand.update('none'); // 'none' für bessere Performance
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

    // Verbrauch Chart (nur noch Verbrauch)
    if (charts.totalizer) {
        charts.totalizer.data.labels = labels;
        charts.totalizer.data.datasets[0].data = history.map(item => parseFloat(item.consumption || 0));
        
        if (chartConfig.consumption) {
            charts.totalizer.data.datasets[0].borderColor = chartConfig.consumption.color || '#e67e22';
            charts.totalizer.data.datasets[0].backgroundColor = chartConfig.consumption.background || 'rgba(230, 126, 34, 0.1)';
        }
        
        charts.totalizer.update('none');
    }

    // Sensor Chart entfernt
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
                message: `⚠ Letzter Fehler: ${systemHealth.last_error.message}`
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

// Letzte Aktualisierung anzeigen - OHNE relative Zeit
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
    
    // Nur den formatierten Zeitstempel anzeigen, OHNE relative Zeit
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
        `<div class="error">⚠ ${message}</div>`;
    
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
        const response = await fetch('api.php?action=table&page=' + currentPage + '&limit=' + entriesPerPage + '&sort=' + sortColumn + '&direction=' + sortDirection);
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
            '<tr><td colspan="6" class="loading-row"><div class="error">⚠ Fehler beim Laden der Tabellendaten</div></td></tr>';
    }
}

// Tabelle rendern
function renderTable() {
    const tbody = document.getElementById('tableBody');
    
    if (!tableData || tableData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="loading-row">Keine Daten verfügbar</td></tr>';
        return;
    }

    const rows = tableData.map(row => {
        const date = new Date(row.timestamp);
        const formattedDate = date.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Werte formatieren und färben
        const wasserstand = parseFloat(row.wasserstand || 0);
        const durchfluss = parseFloat(row.durchflussrate || 0);
        const totalizer = parseFloat(row.totalizer || 0);
        const consumption = parseFloat(row.consumption || 0);
        const sensor = parseFloat(row.sensor_strom || 0);

        const wasserstadClass = wasserstand < -10 ? 'value-negative' : wasserstand > 50 ? 'value-high' : '';
        const durchflussClass = durchfluss > 1.0 ? 'value-high' : durchfluss === 0 ? 'value-zero' : '';
        const consumptionClass = consumption > 0 ? 'value-positive' : consumption < 0 ? 'value-negative' : 'value-zero';
        const sensorClass = sensor < 4 || sensor > 20 ? 'value-negative' : '';

        return `
            <tr>
                <td>${formattedDate}</td>
                <td class="${wasserstadClass}">${wasserstand.toFixed(1)} cm</td>
                <td class="${durchflussClass}">${durchfluss.toFixed(1)} l/s</td>
                <td>${totalizer.toFixed(1)} m³</td>
                <td class="${consumptionClass}">${consumption.toFixed(1)} m³</td>
                <td class="${sensorClass}">${sensor.toFixed(1)} mA</td>
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
// CSV-EXPORT - VOLLSTÄNDIG ÜBERARBEITET
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
            'Möchten Sie alle verfügbaren Messwerte exportieren?\n\n' +
            'Je nach Datenmenge kann dies einige Sekunden dauern.\n' +
            'Es werden maximal 50.000 Datensätze exportiert.'
        );
        
        if (!confirmExport) {
            exportBtn.textContent = originalText;
            exportBtn.disabled = false;
            return;
        }
        
        // Export-Request senden
        const response = await fetch('api.php?action=export&format=csv&limit=50000');
        
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
        let filename = 'abwasser_messwerte_vollstaendig.csv';
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

// Neue Funktion für lokalen Export (nur aktuelle Tabellendaten)
function exportCurrentPageData() {
    if (!tableData || tableData.length === 0) {
        alert('Keine Daten auf der aktuellen Seite zum Exportieren verfügbar');
        return;
    }

    const headers = ['Datum/Zeit', 'Wasserstand (cm)', 'Durchfluss (l/s)', 'Zählerstand (m³)', 'Verbrauch (m³)', 'Sensor (mA)'];
    const csvContent = [
        headers.join(';'),
        ...tableData.map(row => {
            const date = new Date(row.timestamp).toLocaleString('de-DE');
            return [
                date,
                parseFloat(row.wasserstand || 0).toFixed(1),
                parseFloat(row.durchflussrate || 0).toFixed(1),
                parseFloat(row.totalizer || 0).toFixed(1),
                parseFloat(row.consumption || 0).toFixed(1),
                parseFloat(row.sensor_strom || 0).toFixed(1)
            ].join(';');
        })
    ].join('\n');

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `abwasser_messwerte_seite_${currentPage}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}