# 🏊‍♂️ Freibad Dabringhausen - Abwasser Monitoring System

Ein professionelles Web-Dashboard zur Überwachung von Abwasser-Messdaten mit Echtzeit-Visualisierung, Alarmsystem und historischer Datenanalyse.

## 📊 Features

- **Echtzeit-Monitoring:** Live-Überwachung von Wasserstand, Durchfluss, Totalizer und Sensor-Werten
- **Interaktive Charts:** Zeitraum-basierte Visualisierung (1h bis 1 Jahr)
- **Intelligente Alarme:** Automatische Warnungen bei kritischen Werten
- **Vollständiger Daten-Export:** CSV-Export aller Messwerte (bis zu 50.000 Datensätze) 🆕
- **Responsive Design:** Optimiert für Desktop, Tablet und Mobile
- **Auto-Refresh:** Automatische Aktualisierung alle 30 Sekunden
- **Historische Daten:** Vollständige Datentabelle mit Pagination und Sortierung

## 🛠️ Technische Details

- **Backend:** PHP 8.0+ mit MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Chart.js)
- **Datenprotokoll:** Modbus TCP/RTU
- **API:** RESTful JSON-API
- **Sicherheit:** Rate Limiting, IP-Whitelist, Eingabevalidierung

## 📋 Systemanforderungen

- PHP 8.0 oder höher
- MySQL 5.7+ oder MariaDB 10.3+
- Apache/Nginx Webserver
- PDO MySQL Extension
- JSON Extension

## 🚀 Installation

1. **Repository klonen:**
   ```bash
   git clone [repository-url]
   cd abwasser-dashboard
   ```

2. **Dateien auf Server kopieren:**
   ```bash
   cp -r * /var/www/html/dashboard/
   ```

3. **Konfiguration anpassen:**
   ```bash
   cp config.php.example config.php
   nano config.php
   ```

4. **Datenbankverbindung konfigurieren:**
   - Host, Benutzername, Passwort in `config.php` eintragen
   - Tabelle `abwasser_messwerte` muss existieren

5. **Webserver konfigurieren:**
   - DocumentRoot auf `/var/www/html/dashboard/` setzen
   - Schreibrechte für Logs (optional)

## 📖 Nutzung

1. **Dashboard aufrufen:** `http://ihre-domain.de/dashboard/`
2. **Zeitraum wählen:** Buttons für 1h, 6h, 24h, 7d, 30d, 1y
3. **Daten exportieren:** CSV-Export über Tabelle (alle Daten oder aktuelle Seite)
4. **Alarme überwachen:** Automatische Anzeige bei kritischen Werten

## ⚙️ Konfiguration

### Schwellenwerte anpassen
```php
'alerts' => [
    'wasserstand' => [
        'warning_low' => -5.0,
        'critical_low' => -10.0,
        'warning_high' => 40.0,
        'critical_high' => 50.0
    ]
]
```

### Auto-Refresh-Intervall
```php
'dashboard' => [
    'auto_refresh_interval' => 30000  // 30 Sekunden
]
```

## 🔧 API-Endpunkte

- **Dashboard-Daten:** `GET /api.php?range=1h`
- **Tabellen-Daten:** `GET /api.php?action=table&page=1&limit=25`
- **CSV-Export:** `GET /api.php?action=export&format=csv&limit=50000` 🆕
- **System-Status:** Automatisch in Dashboard-Antwort enthalten

## 📁 Projektstruktur

```
dashboard/
├── index.html          # Haupt-Dashboard
├── styles.css          # CSS-Styling
├── scripts.js          # JavaScript-Logik
├── api.php            # Backend-API
├── config.php         # Konfigurationsdatei
├── VERSION            # Aktuelle Versionsnummer
├── README.md          # Diese Datei
└── logs/              # Log-Dateien (optional)
```

## 🏗️ Systemarchitektur

```
Modbus-Sensoren → PHP-Script → MySQL → API → Web-Dashboard
                                     ↓
                              Alarmsystem & Logging
```

## 🚨 Fehlerbehebung

### Häufige Probleme

**Dashboard lädt nicht:**
- Browser-Konsole (F12) auf JavaScript-Fehler prüfen
- `api.php` direkt aufrufen: `/api.php?range=1h`
- Datenbankverbindung in `config.php` prüfen

**Keine Daten sichtbar:**
- Modbus-Verbindung prüfen
- Tabelle `abwasser_messwerte` auf Daten prüfen
- `modbus_status = 'OK'` in Datenbank

**Export funktioniert nicht:**
- Browser-Konsole auf Fehler prüfen
- API-Endpunkt testen: `/api.php?action=export&format=csv&limit=100`
- Datenbankverbindung und Tabellenzugriff prüfen

**Alarme funktionieren nicht:**
- Schwellenwerte in `config.php` prüfen
- Debug-Modus aktivieren: `'debug_mode' => true`

### Debug-Modus aktivieren
```php
// In config.php
'debug_mode' => true,
'security' => [
    'log_errors' => true
]
```

## 🔒 Sicherheitshinweise

- **IP-Whitelist:** Zugriff auf vertrauenswürdige IPs beschränken
- **HTTPS:** SSL-Zertifikat für sichere Datenübertragung
- **Firewall:** Nur notwendige Ports öffnen (80/443)
- **Updates:** System regelmäßig aktualisieren

## 🤝 Support

Bei Problemen oder Fragen:
1. Debug-Modus aktivieren
2. Browser-Konsole und Server-Logs prüfen
3. Konfiguration validieren

---

## 📈 Changelog

### Version 1.2.1 (22.06.2025) - Einheitliche 3-stellige Dezimalformatierung
**🎯 Konsistente Datengenauigkeit für alle Messwerte**

#### ✨ Verbesserungen
- **Einheitliche Präzision:** Alle Messwerte jetzt mit 3 Dezimalstellen angezeigt
- **Konsistente Formatierung:** Wasserstand, Zählerstand und Sensor-Strom jetzt ebenfalls 3-stellig
- **Verbesserte Datenqualität:** Höhere Genauigkeit in Tabellen und CSV-Exporten
- **Einheitliche Benutzeroberfläche:** Konsistente Darstellung aller Messwerte

#### 🔧 Technische Änderungen
- **config.php:** Alle `decimal_places` auf 3 Stellen gesetzt
- **Tabellendarstellung:** Alle Werte mit 3 Dezimalstellen formatiert
- **CSV-Export:** Einheitliche 3-stellige Formatierung im Export
- **API-Konsistenz:** Alle Datenformate anglegeicht

#### 📊 Neue Formatierung
- **Wasserstand:** 0.000 cm (vorher 0.0 cm)
- **Durchfluss:** 0.000 l/s (unverändert)
- **Zählerstand:** 8.290 m³ (vorher 8.29 m³)
- **Verbrauch:** 0.001 m³ (unverändert)
- **Sensor:** 11.400 mA (vorher 11.4 mA)

#### 🎨 UI-Verbesserungen
- **Visuelle Konsistenz:** Alle Zahlen haben gleiche Dezimalstellenanzahl
- **Bessere Lesbarkeit:** Einheitliche Formatierung erleichtert Vergleiche
- **Professioneller Look:** Konsistente Darstellung wirkt aufgeräumter

---

### Version 1.2.0 (22.06.2025) - Gesamtverbrauch & UI-Verbesserungen
**🎯 Erweiterte Verbrauchsüberwachung und bessere Terminologie**

#### ✨ Neue Features
- **Gesamtverbrauch-Anzeige:** Neues Status-Element zeigt summierten Verbrauch aller Messungen
- **Verbesserte Terminologie:** "Totalizer" durch "Zählerstand" ersetzt für bessere Verständlichkeit
- **Erweiterte Status-Bar:** Fünf Status-Elemente statt vier für umfassendere Übersicht
- **Intelligente Verbrauchsberechnung:** Automatische Summierung aller positiven Verbrauchswerte

#### 🔧 Technische Verbesserungen
- **Datenbankoptimierung:** Neue SQL-Abfrage für effizienten Gesamtverbrauch
- **API-Erweiterung:** `total_consumption` Feld in API-Antworten
- **Performance:** Optimierte Berechnung mit SUM()-Funktion
- **Frontend-Logik:** Intelligente Anzeige des Gesamtverbrauchs

#### 🎨 UI/UX Verbesserungen
- **Responsive Status-Bar:** Optimiert für 5 Status-Elemente auf allen Bildschirmgrößen
- **Visuelle Hervorhebung:** Gesamtverbrauch in grüner Farbe hervorgehoben
- **Mobile-Optimierung:** Verbesserte Darstellung auf kleinen Bildschirmen
- **Konsistente Terminologie:** Einheitliche Begriffe im gesamten System

#### 📊 Neue Datenvisualisierung
- **Gesamtverbrauch:** Prominent in der Status-Bar angezeigt
- **Echtzeit-Updates:** Automatische Aktualisierung des Gesamtverbrauchs
- **Präzise Formatierung:** 3 Dezimalstellen für exakte Verbrauchsanzeige
- **Einheitliche Darstellung:** m³-Einheit konsistent verwendet

#### 🔄 Systemverbesserungen
- **Verbrauchslogik:** Nur positive Verbrauchswerte werden summiert
- **Fehlerbehandlung:** Robuste Behandlung bei fehlenden Verbrauchsdaten
- **Backward-Compatibility:** Vollständige Kompatibilität mit bestehenden Daten
- **Zero-Downtime-Update:** Nahtloses Update ohne Systemunterbrechung

#### 📱 Mobile Responsiveness
- **5-Element-Layout:** Optimiert für Status-Bar mit 5 Elementen
- **Adaptive Grid:** Intelligente Anordnung auf verschiedenen Bildschirmgrößen
- **Touch-Friendly:** Verbesserte Touch-Bedienung auf mobilen Geräten
- **Lesbarkeit:** Optimierte Schriftgrößen für alle Bildschirmgrößen

#### 📋 Dokumentation
- **README-Update:** Aktualisierte Dokumentation mit neuen Features
- **API-Dokumentation:** Beschreibung des neuen `total_consumption` Felds
- **Installation Guide:** Anweisungen für Update auf Version 1.2.0

---

### Version 1.1.0 (22.06.2025) - CSV-Export Update
**🎯 Vollständiger Daten-Export für bessere Datenanalyse**

#### ✨ Neue Features
- **Vollständiger CSV-Export:** Export aller verfügbaren Messwerte (bis zu 50.000 Datensätze)
- **Export-API-Endpunkt:** Neuer `/api.php?action=export` Endpunkt für Datenexport
- **Excel-Kompatibilität:** Deutsche CSV-Formatierung mit Semikolon-Trennung
- **Intelligente Dateinamen:** Automatische Timestamp-basierte Benennung
- **Export-Feedback:** Loading-Indikator und Erfolgsmeldungen
- **Benutzerbestätigung:** Sicherheitsabfrage vor großen Exporten

#### 🔧 Technische Verbesserungen
- **Performance-Optimierung:** Streaming-Export für große Datenmengen
- **Memory-Management:** Effizienter Umgang mit großen Datensätzen
- **UTF-8 BOM:** Korrekte Umlaute-Darstellung in Excel
- **Error-Handling:** Verbesserte Fehlerbehandlung beim Export
- **Security:** Rate-Limiting und Größenbeschränkung für Exporte

#### 📊 Export-Features
- **Maximales Limit:** 50.000 Datensätze pro Export (Schutz vor Überlastung)
- **Deutsche Formatierung:** Komma als Dezimaltrennzeichen, dd.mm.yyyy Datumsformat
- **Vollständige Daten:** Alle Messwerte (Wasserstand, Durchfluss, Totalizer, Verbrauch, Sensor)
- **Chronologische Sortierung:** Neueste Daten zuerst
- **Dateiname-Schema:** `abwasser_messwerte_vollstaendig_YYYY-MM-DD_HH-MM-SS.csv`

#### 🎨 UI/UX Verbesserungen
- **Export-Button-Feedback:** Visueller Status während des Exports
- **Bestätigungsdialog:** Warnung bei großen Datenexporten
- **Erfolgsmeldungen:** Grüne Success-Alerts nach erfolgreichem Export
- **Benutzerführung:** Klare Anweisungen und Erwartungsmanagement

#### 📄 Dokumentation
- **API-Dokumentation:** Vollständige Beschreibung des Export-Endpunkts
- **Troubleshooting:** Neue Fehlerbehebung für Export-Probleme
- **README-Update:** Erweiterte Nutzungsanleitung mit Export-Features

#### 🔧 Code-Qualität
- **Modulare Struktur:** Saubere Trennung von Export-Logik
- **Error-Logging:** Detaillierte Protokollierung von Export-Vorgängen
- **Code-Kommentierung:** Ausführliche Dokumentation der neuen Funktionen
- **Backward-Compatibility:** Vollständige Kompatibilität mit bestehenden Features

#### 🚀 Migration von v1.0.0
- **Nahtloses Update:** Keine Datenbankänderungen erforderlich
- **Konfiguration:** Keine zusätzlichen Einstellungen notwendig
- **Instant-Verfügbarkeit:** Export-Feature sofort nach Update verfügbar

---

### Version 1.0.0 (22.06.2025)
**🎉 Initial Release - Vollständiges Abwasser-Monitoring-System**

#### ✨ Neue Features
- **Dashboard-System:** Vollständiges Web-Dashboard mit Live-Daten
- **Echtzeit-Monitoring:** Automatische Aktualisierung alle 30 Sekunden
- **Multi-Zeitraum-Analyse:** 1 Stunde bis 1 Jahr Zeiträume
- **Interaktive Charts:** 4 verschiedene Chart-Typen:
  - 💧 Wasserstand Verlauf
  - 🌊 Durchflussrate
  - 📊 Totalizer & Verbrauch
  - ⚡ Sensor Strom
- **Intelligentes Alarmsystem:** Automatische Warnungen bei kritischen Werten
- **Daten-Tabelle:** Vollständige historische Daten mit:
  - ↕️ Sortierung nach allen Spalten
  - 📄 Pagination (10-1000 Einträge pro Seite)
  - 📊 CSV-Export Funktionalität (nur aktuelle Seite)
  - 🎨 Farbkodierung kritischer Werte
- **System-Gesundheit:** Monitoring der Datenqualität und Fehlerrate
- **Responsive Design:** Optimiert für Desktop, Tablet und Mobile

#### 🛠️ Technische Implementierung
- **Backend:** PHP 8.0+ mit PDO MySQL
- **Frontend:** HTML5, CSS3, JavaScript mit Chart.js
- **API:** RESTful JSON-API mit Pagination
- **Datenbank:** MySQL/MariaDB Integration
- **Sicherheit:** Rate Limiting, IP-Whitelist, Eingabevalidierung
- **Performance:** Intelligente Datenpunkt-Reduzierung für längere Zeiträume
- **Konfiguration:** Umfassende `config.php` mit über 50 Einstellungen

#### 📊 Monitoring-Capabilities
- **Wasserstand:** -50 bis +100 cm Messbereich
- **Durchfluss:** 0-10 l/s mit 0.001 l/s Präzision
- **Totalizer:** Unbegrenzt mit 0.01 m³ Präzision
- **Sensor-Strom:** 4-20 mA Überwachung
- **Verbrauchsberechnung:** Automatische Differenzbildung

#### 🚨 Alarm-Features
- **4-Stufen-Alarmsystem:** Info → Warnung → Kritisch → Notfall
- **Multi-Parameter-Überwachung:** Alle Sensoren werden überwacht
- **Visuelle Alarme:** Farbkodierte Anzeigen und Icons
- **System-Alarme:** Datenalter und Verbindungsstatus

#### 💻 Frontend-Features
- **Moderne UI:** Glassmorphism-Design mit Farbverläufen
- **Live-Status-Bar:** Aktuelle Werte prominent dargestellt
- **Zeitraum-Selektor:** Schnelle Umschaltung zwischen Zeiträumen
- **Auto-Refresh:** Konfigurierbare Aktualisierungsintervalle
- **Tooltip-System:** Detailinformationen bei Hover
- **Loading-States:** Benutzerfreundliche Ladeanzeigen

#### 🔧 System-Features
- **Versionsmanagement:** Automatische Versionsverfolgung
- **Erstes Messdatum:** Automatische Ermittlung und Speicherung
- **Debug-Modus:** Umfassende Fehlerverfolgung
- **Logging-System:** Strukturierte Fehler- und Aktivitätslogs
- **Performance-Optimierung:** Lazy Loading und Caching

#### 📱 Mobile-Optimierung
- **Responsive Grid:** Automatische Anpassung an Bildschirmgröße
- **Touch-Optimierung:** Touch-freundliche Bedienelemente
- **Mobile Navigation:** Optimierte Menüführung
- **Schnelle Ladezeiten:** Minimierte Assets und Kompression

#### 🎨 Design-System
- **Konsistente Farbpalette:** Professionelle Farbgebung
- **Typography:** Optimierte Schriftarten und -größen
- **Spacing-System:** Harmonische Abstände und Proportionen
- **Animation-System:** Sanfte Übergänge und Micro-Interaktionen

#### 📈 Chart-System
- **Chart.js Integration:** Professionelle Diagramm-Bibliothek
- **Intelligente Labels:** Zeitbasierte Achsenbeschriftung
- **Performance-Optimierung:** Datenpunkt-Reduzierung bei großen Datensätzen
- **Interaktive Tooltips:** Detailwerte bei Hover
- **Zoom-Funktionalität:** Bereichsauswahl in Charts

#### 🔐 Sicherheits-Features
- **Input-Validation:** Umfassende Eingabeprüfung
- **XSS-Schutz:** Automatische Ausgabe-Escaping
- **CSRF-Schutz:** Token-basierte Anfrageverifizierung
- **Error-Handling:** Sichere Fehlerbehandlung ohne Informationsleckage
- **Rate-Limiting:** Schutz vor Überlasten der API

#### 📋 Dokumentation
- **Vollständige README:** Umfassende Installations- und Nutzungsanleitung
- **Code-Kommentierung:** Ausführliche Inline-Dokumentation
- **API-Dokumentation:** Vollständige Endpunkt-Beschreibung
- **Konfigurationshilfe:** Detaillierte Konfigurationsoptionen

---

## 📄 Lizenz

Dieses Projekt ist für den internen Gebrauch des Freibads Dabringhausen entwickelt.

## 📞 Kontakt

**Freibad Dabringhausen**  
Abwasser-Monitoring System v1.1.0

---

*Entwickelt für eine nachhaltige Wasserüberwachung im Freibad Dabringhausen* 🏊‍♂️💧