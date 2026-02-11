// =============================================================================
// FARB-KORREKTUREN FÜR JAVASCRIPT
// Diese Funktionen ersetzen/erweitern die bestehenden Funktionen
// =============================================================================

// Erweiterte Übersichtswerte mit Farbkodierung
function updateOverviewValues(abwasserCurrent, frischwasserCurrent) {
    const freshDaily = frischwasserCurrent?.daily_consumption || 0;
    const wasteDaily = abwasserCurrent?.total_consumption || 0;
    const balance = freshDaily - wasteDaily;
    const efficiency = freshDaily > 0 ? ((wasteDaily / freshDaily) * 100) : 0;

    // Werte setzen
    const freshElement = document.getElementById('overview-fresh-daily');
    const wasteElement = document.getElementById('overview-waste-daily');
    const balanceElement = document.getElementById('overview-balance');
    const efficiencyElement = document.getElementById('overview-efficiency');

    if (freshElement) {
        freshElement.textContent = `${freshDaily.toFixed(2)} m³`;
        freshElement.style.color = '#2980b9'; // Kräftiges Blau
    }

    if (wasteElement) {
        wasteElement.textContent = `${wasteDaily.toFixed(2)} m³`;
        wasteElement.style.color = '#d35400'; // Kräftiges Orange
    }

    if (balanceElement) {
        balanceElement.textContent = `${balance.toFixed(2)} m³`;
        if (balance >= 0) {
            balanceElement.style.color = '#27ae60'; // Grün für positive Bilanz
            balanceElement.classList.remove('negative');
        } else {
            balanceElement.style.color = '#c0392b'; // Rot für negative Bilanz
            balanceElement.classList.add('negative');
        }
    }

    if (efficiencyElement) {
        efficiencyElement.textContent = `${efficiency.toFixed(1)}%`;
        
        // Effizienz-Farben
        if (efficiency >= 90) {
            efficiencyElement.style.color = '#27ae60'; // Grün für > 90%
            efficiencyElement.className = 'comparison-value excellent';
        } else if (efficiency >= 70) {
            efficiencyElement.style.color = '#f39c12'; // Orange für 70-90%
            efficiencyElement.className = 'comparison-value good';
        } else {
            efficiencyElement.style.color = '#c0392b'; // Rot für < 70%
            efficiencyElement.className = 'comparison-value poor';
        }
    }
}

// Mock-Daten für Übersicht mit korrekten Farben
function updateOverviewValuesMock() {
    const mockData = {
        freshDaily: 2.85,
        wasteDaily: 2.12,
        balance: 0.73,
        efficiency: 74.4
    };

    const elements = {
        'overview-fresh-daily': { value: mockData.freshDaily.toFixed(2) + ' m³', color: '#2980b9' },
        'overview-waste-daily': { value: mockData.wasteDaily.toFixed(2) + ' m³', color: '#d35400' },
        'overview-balance': { value: mockData.balance.toFixed(2) + ' m³', color: '#27ae60' },
        'overview-efficiency': { value: mockData.efficiency.toFixed(1) + '%', color: '#f39c12' }
    };

    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            const { value, color } = elements[id];
            element.textContent = value;
            element.style.color = color;
            
            // Spezielle Klasse für Effizienz
            if (id === 'overview-efficiency') {
                if (mockData.efficiency >= 90) {
                    element.className = 'comparison-value excellent';
                } else if (mockData.efficiency >= 70) {
                    element.className = 'comparison-value good';
                } else {
                    element.className = 'comparison-value poor';
                }
            }
        }
    });
}

// Frischwasser-Werte mit verbesserter Farbkodierung
function updateFrischwasserValuesWithColors(current, config) {
    if (!current) {
        updateFrischwasserValuesMockWithColors();
        return;
    }

    const decimals = config?.decimal_places || {};
    
    const elements = [
        { id: 'fw-current-counter', value: current.counter_m3, decimals: decimals.counter_m3 || 3, unit: 'm³', color: '#2c3e50' },
        { id: 'fw-current-hourly', value: current.hourly_consumption, decimals: decimals.consumption_l || 1, unit: 'L', color: '#c0392b' },
        { id: 'fw-current-daily', value: current.daily_consumption, decimals: decimals.daily_m3 || 2, unit: 'm³', color: '#27ae60' },
        { id: 'fw-current-flow', value: current.current_flow_lmin, decimals: decimals.flow_lmin || 1, unit: 'l/min', color: '#d68910' },
        { id: 'fw-current-weekly', value: current.weekly_consumption, decimals: decimals.weekly_m3 || 2, unit: 'm³', color: '#8e44ad' }
    ];

    elements.forEach(({ id, value, decimals, unit, color }) => {
        const element = document.getElementById(id);
        if (element) {
            const formattedValue = parseFloat(value || 0).toFixed(decimals);
            element.innerHTML = `${formattedValue}<span class="status-unit">${unit}</span>`;
            element.style.color = color;
            element.style.textShadow = `0 1px 2px ${color}33`;
        }
    });
}

// Mock-Daten für Frischwasser mit Farben
function updateFrischwasserValuesMockWithColors() {
    const mockData = [
        { id: 'fw-current-counter', value: '1.236', unit: 'm³', color: '#2c3e50' },
        { id: 'fw-current-hourly', value: '340', unit: 'L', color: '#c0392b' },
        { id: 'fw-current-daily', value: '2.85', unit: 'm³', color: '#27ae60' },
        { id: 'fw-current-flow', value: '12.5', unit: 'l/min', color: '#d68910' },
        { id: 'fw-current-weekly', value: '18.6', unit: 'm³', color: '#8e44ad' }
    ];

    mockData.forEach(({ id, value, unit, color }) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerHTML = `${value}<span class="status-unit">${unit}</span>`;
            element.style.color = color;
            element.style.textShadow = `0 1px 2px ${color}33`;
        }
    });
}

// Alert-Nachrichten mit besseren Farben
function showColoredAlert(message, type = 'info') {
    const alertsDiv = document.getElementById('alerts') || document.getElementById('fw-alerts');
    if (!alertsDiv) return;

    const alertClasses = {
        'success': 'alert success',
        'warning': 'alert warning', 
        'danger': 'alert danger',
        'error': 'error',
        'info': 'alert info'
    };

    const alertClass = alertClasses[type] || 'alert info';
    const alertHtml = `<div class="${alertClass}">${message}</div>`;
    
    const existingAlerts = alertsDiv.innerHTML;
    alertsDiv.innerHTML = alertHtml + existingAlerts;
    
    // Nach 5 Sekunden entfernen (außer bei Fehlern)
    if (type !== 'error' && type !== 'danger') {
        setTimeout(() => {
            const alertElement = alertsDiv.querySelector(`.${alertClass.replace(' ', '.')}`);
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }
}

// Last-Update mit verbesserter Farbkodierung
function updateLastUpdateWithColors(lastUpdateInfo, elementId = 'last-update') {
    const lastUpdateElement = document.getElementById(elementId);
    
    if (!lastUpdateElement) {
        console.warn(`${elementId} Element nicht gefunden`);
        return;
    }
    
    if (!lastUpdateInfo) {
        lastUpdateElement.textContent = 'Letzte Aktualisierung: Keine Daten verfügbar';
        lastUpdateElement.className = 'last-update error';
        lastUpdateElement.style.color = '#a93226';
        return;
    }

    const { formatted, age_minutes, is_stale, needs_attention } = lastUpdateInfo;
    
    let cssClass = 'last-update';
    let textColor = '#1e8449'; // Grün
    
    if (is_stale) {
        cssClass += ' warning';
        textColor = '#c0392b'; // Rot
    } else if (needs_attention) {
        cssClass += ' attention';
        textColor = '#b7950b'; // Orange
    }
    
    lastUpdateElement.textContent = `Letzte Messung: ${formatted}`;
    lastUpdateElement.className = cssClass;
    lastUpdateElement.style.color = textColor;
}

// Erweiterte Funktionen für bessere Farbverwaltung
function applyColorTheme() {
    // Prüfe ob Dark Mode aktiv ist
    const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        console.log('🌙 Dark Mode erkannt - Angepasste Farben werden verwendet');
    } else {
        document.body.classList.remove('dark-mode');
        console.log('☀️ Light Mode erkannt - Standard-Farben werden verwendet');
    }
}

// Verbesserte Erfolgsmeldung mit Farben
function showColoredSuccessMessage(message) {
    showColoredAlert(`✅ ${message}`, 'success');
}

// Verbesserte Fehlermeldung mit Farben
function showColoredErrorMessage(message) {
    showColoredAlert(`❌ ${message}`, 'error');
}

// =============================================================================
// BESTEHENDE FUNKTIONEN ERWEITERN
// =============================================================================

// Bestehende updateFrischwasserValues Funktion erweitern
if (typeof updateFrischwasserValues === 'function') {
    const originalUpdateFrischwasserValues = updateFrischwasserValues;
    updateFrischwasserValues = function(current, config) {
        originalUpdateFrischwasserValues(current, config);
        updateFrischwasserValuesWithColors(current, config);
    };
}

// Bestehende updateFrischwasserValuesMock Funktion erweitern
if (typeof updateFrischwasserValuesMock === 'function') {
    const originalUpdateFrischwasserValuesMock = updateFrischwasserValuesMock;
    updateFrischwasserValuesMock = function() {
        originalUpdateFrischwasserValuesMock();
        updateFrischwasserValuesMockWithColors();
    };
}

// Bestehende updateOverviewData Funktion erweitern
if (typeof loadOverviewData === 'function') {
    const originalLoadOverviewData = loadOverviewData;
    loadOverviewData = function() {
        originalLoadOverviewData();
        updateOverviewValuesMock();
    };
}

// Bestehende showSuccessMessage Funktion erweitern
if (typeof showSuccessMessage === 'function') {
    const originalShowSuccessMessage = showSuccessMessage;
    showSuccessMessage = function(message) {
        showColoredSuccessMessage(message);
    };
}

// =============================================================================
// INITIALISIERUNG DER FARBKORREKTUREN
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Farbkorrekturen werden angewendet...');
    
    // Farb-Theme anwenden
    applyColorTheme();
    
    // Dark Mode Listener
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeQuery.addEventListener('change', applyColorTheme);
    }
    
    // Kurze Verzögerung für DOM-Rendering
    setTimeout(() => {
        // Übersicht-Farben anwenden falls Tab aktiv
        const overviewTab = document.getElementById('overview-tab');
        if (overviewTab && overviewTab.classList.contains('active')) {
            updateOverviewValuesMock();
        }
        
        // Frischwasser-Farben anwenden falls Tab aktiv
        const frischwasserTab = document.getElementById('frischwasser-tab');
        if (frischwasserTab && frischwasserTab.classList.contains('active')) {
            updateFrischwasserValuesMockWithColors();
        }
        
        console.log('✅ Farbkorrekturen erfolgreich angewendet');
    }, 500);
});

console.log('🎨 Farb-JavaScript-Module geladen');