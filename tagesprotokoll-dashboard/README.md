# 🏊‍♂️ Freibad Dabringhausen - Monitoring Dashboard

Ein umfassendes Dashboard zur Visualisierung von Tagesprotokoll-Daten und Wasserqualitätsmessungen des Freibads Dabringhausen.

## 📊 Features

### Tagesprotokoll Dashboard
- **Besucherzahlen:** Tägliche Besucherstatistiken mit Balkendiagramm
- **Temperaturverläufe:** Luft-, MZB-, NSB- und KKB-Temperaturen
- **Zählerstände:** Wasserleitungsnetz und Abwasser-Zählerstände
- **Wetterbedingungen:** Verteilung der Wetterbedingungen als Doughnut-Chart

### Wasserqualität Dashboard
- **pH-Wert Monitoring:** Verlaufskurven für alle Becken (MZB, NSB, KKB)
- **Chlorwerte:** Freies und gebundenes Chlor im Zeitverlauf
- **Redox-Wert:** Überwachung des Redoxpotentials
- **Beckenvergleich:** Scatter-Plot pH vs. Chlor für alle Becken

### Allgemeine Features
- **Responsive Design:** Optimiert für Desktop, Tablet und Mobile
- **Zeitraum-Auswahl:** 7 Tage, 30 Tage, 3 Monate, 1 Jahr oder alle Daten
- **Auto-Refresh:** Automatische Aktualisierung alle 5 Minuten
- **Tab-basierte Navigation:** Einfacher Wechsel zwischen den Dashboards

## 🛠️ Installation

### Systemanforderungen
- PHP 7.4 oder höher
- MySQL 5.7+ oder MariaDB 10.3+
- Apache/Nginx Webserver
- PDO MySQL Extension

### Schritt 1: Dateien kopieren
```bash
# Dashboard-Dateien auf den Webserver kopieren
cp freibad_dashboard.html /var/www/html/freibad/index.html
cp freibad_api.php /var/www/html/freibad/api.php
```

### Schritt 2: Datenbankzugriff konfigurieren
Bearbeiten Sie die `api.php` Datei und passen Sie die Datenbankverbindung an:

```php
$db_config = [
    'host' => 'localhost',
    'username' => 'ihr_db_benutzer',
    'password' => 'ihr_db_passwort',
    'database' => 'ihre_datenbank',
    'charset' => 'utf8'
];
```

### Schritt 3: Webserver konfigurieren
**Apache .htaccess Beispiel:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/?$ api.php [L]
```

### Schritt 4: Berechtigung setzen
```bash
# Ausführungsrechte für PHP-Dateien
chmod 644 /var/www/html/freibad/*.php
chmod 644 /var/www/html/freibad/*.html
```

## 📋 Datenbankstruktur

Das Dashboard erwartet folgende Tabellen:

### Tagesprotokoll
```sql
CREATE TABLE `Tagesprotokoll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Datum` date NOT NULL,
  `Tagesbesucherzahl` int(11) NOT NULL,
  `Lufttemperatur` float NOT NULL,
  `Temperatur_MZB` float NOT NULL,
  `Temperatur_NSB` float NOT NULL,
  `Temperatur_KKB` float NOT NULL,
  `Zaehlerstand_Wasserleitungsnetz` int(11) NOT NULL,
  `Zaehlerstand_Abwasser` int(11) NOT NULL,
  `Wetter_S` tinyint(4) NOT NULL,
  `Wetter_H` tinyint(4) NOT NULL,
  `Wetter_B` tinyint(4) NOT NULL,
  `Wetter_R` tinyint(4) NOT NULL,
  `Wetter_G` tinyint(4) NOT NULL,
  `Protokollunterzeichner` varchar(40) NOT NULL,
  `Bemerkungen` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Datum` (`Datum`)
);
```

### Wasserqualitaet
```sql
CREATE TABLE `Wasserqualitaet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Datum` date NOT NULL,
  `Uhrzeit` time NOT NULL,
  `Becken` varchar(3) NOT NULL,
  `Cl_frei` float NOT NULL,
  `Cl_gesamt` float NOT NULL,
  `pH_Wert` float NOT NULL,
  `Redox_Wert` float NOT NULL,
  `Wasserhaerte` float NOT NULL,
  PRIMARY KEY (`id`)
);
```

## 🔧 API-Endpunkte

### Tagesprotokoll abrufen
```
GET /api.php?type=tagesprotokoll&range=7d
```

### Wasserqualität abrufen
```
GET /api.php?type=wasserqualitaet&range=30d
```

### Parameter
- **type:** `tagesprotokoll` oder `wasserqualitaet`
- **range:** `7d`, `30d`, `90d`, `1y`, `all`

### Antwort-Format
```json
{
  "success": true,
  "type": "tagesprotokoll",
  "range": "7d",
  "timestamp": "2025-07-01 12:00:00",
  "current": {...},
  "data": [...],
  "stats": {...},
  "count": 7
}
```

## 🎨 Anpassungen

### Design anpassen
Das CSS ist inline im HTML enthalten und kann direkt bearbeitet werden:
- Farben: Suchen Sie nach Hex-Codes wie `#667eea`
- Schriftarten: Ändern Sie `font-family` Eigenschaften
- Layout: Anpassen der Grid-Eigenschaften

### Zeitintervalle erweitern
In der `api.php` neue Zeitintervalle hinzufügen:
```php
case '6m':
    $whereClause = "WHERE Datum >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    break;
```

### Neue Chart-Typen
Chart.js unterstützt viele Chart-Typen:
- `bar`, `line`, `doughnut`, `pie`, `scatter`, `bubble`
- Dokumentation: https://www.chartjs.org/docs/

## 🚨 Fehlerbehebung

### Dashboard lädt nicht
1. **Browser-Konsole prüfen:** F12 → Console Tab
2. **API direkt testen:** `/api.php?type=tagesprotokoll&range=7d`
3. **Datenbankverbindung prüfen:** Credentials in `api.php`

### Keine Daten sichtbar
1. **Tabellen prüfen:** 
   ```sql
   SELECT COUNT(*) FROM Tagesprotokoll;
   SELECT COUNT(*) FROM Wasserqualitaet;
   ```
2. **Datumsformat prüfen:** Format sollte `YYYY-MM-DD` sein
3. **PHP Error Log:** `/var/log/apache2/error.log`

### Charts werden nicht angezeigt
1. **Chart.js geladen:** Netzwerk-Tab in Entwicklertools
2. **Canvas-Elemente vorhanden:** HTML-Struktur prüfen
3. **JavaScript-Fehler:** Browser-Konsole prüfen

## 📈 Performance-Optimierung

### Datenbankindizes
```sql
-- Für bessere Performance
CREATE INDEX idx_tagesprotokoll_datum ON Tagesprotokoll(Datum);
CREATE INDEX idx_wasserqualitaet_datum ON Wasserqualitaet(Datum);
CREATE INDEX idx_wasserqualitaet_becken ON Wasserqualitaet(Becken);
```

### Caching implementieren
```php
// Einfaches File-Caching in api.php
$cache_file = "cache_" . md5($type . $range) . ".json";
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300) {
    echo file_get_contents($cache_file);
    exit;
}
```

## 🔒 Sicherheit

### Produktionsumgebung
1. **Error Reporting deaktivieren:**
   ```php
   ini_set('display_errors', 0);
   error_reporting(0);
   ```

2. **IP-Whitelist implementieren:**
   ```php
   $allowed_ips = ['192.168.1.100', '10.0.0.50'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
       http_response_code(403);
       exit('Access denied');
   }
   ```

3. **HTTPS verwenden:**
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

## 🤝 Support & Updates

### Version
Aktuelle Version: **2.0.0**

### Updates
- Dashboard-Updates durch Ersetzen der HTML-Datei
- API-Updates durch Ersetzen der PHP-Datei
- Backup vor Updates empfohlen

### Support
Bei Problemen:
1. Error-Logs prüfen
2. Browser-Entwicklertools verwenden
3. API-Antworten direkt testen

---

**Entwickelt für das Freibad Dabringhausen** 🏊‍♂️  
*Ein professionelles Monitoring-Dashboard für optimalen Badebetrieb*