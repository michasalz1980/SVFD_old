# 📄 CSV-Import Anleitung für DATEV

## 🎯 **Schritt-für-Schritt Anleitung**

### **1. CSV-Datei exportieren**
- Im Dashboard: Zeitraum auswählen
- Export-Typ wählen (empfohlen: "Tageszusammenfassung")
- Format: "Einfaches CSV (ASCII-Import)" 
- ✅ **Funktioniert sofort ohne weitere Konfiguration!**

### **2. In DATEV importieren**
1. **DATEV Rechnungswesen** öffnen
2. Gehen Sie zu: **Bestand > Importieren > ASCII-Daten**
3. **CSV-Datei auswählen** (z.B. `datev_export_2025-06-01_2025-06-30_daily_summary.csv`)

### **3. Import-Einstellungen konfigurieren**
- **Überschriftenzeile:** ✅ Ja (erste Zeile enthält Spaltennamen)
- **Trennzeichen:** `;` (Semikolon)
- **Textqualifizierer:** `"` (Anführungszeichen)
- **Datumsformat:** `TTMM` (z.B. 2706 für 27. Juni)

### **4. Spalten zuordnen**
| CSV-Spalte | DATEV-Feld | Beschreibung |
|------------|------------|--------------|
| **Währung** | Währung | EUR |
| **VorzBetrag** | Umsatz (mit Vorzeichen) | +6232,00 / -2872,00 |
| **RechNr** | Belegnummer | 2025-0001 |
| **BelegDatum** | Belegdatum | 2706 |
| **Belegtext** | Buchungstext | "Tageseinnahme Registrierkasse" |
| **Gegenkonto** | Gegenkonto | 43000, 13720, 13721 |
| **Nachricht** | (optional) | "Kasse Import Standardformat" |

**Nicht benötigte Spalten:** UStSatz, BU, Kost1, Kost2, Kostmenge, Skonto → **"wird nicht verarbeitet"**

### **5. Import durchführen**
- **Vorschau prüfen** - sollte korrekte Buchungssätze anzeigen
- **Import starten** 
- ✅ **Fertig!**

---

## 📊 **Export-Typen erklärt**

### **Tageszusammenfassung** (empfohlen)
```csv
EUR;+6232,00;2025-0001;2706;"Tageseinnahme Registrierkasse";;;43000;;;;;"Kasse Import Standardformat"
EUR;-2872,00;2025-0002;2706;"Entnahme Registrierkasse";;;13720;;;;;"Kasse Import Standardformat"
```
- **Vorteil:** Übersichtlich, wenige Buchungszeilen
- **Ideal für:** Monatliche/wöchentliche Buchung

### **Detailliert**
```csv
EUR;+12,50;2025-0001;2706;"Kassenumsatz: Kaffee";;;43000;;;;;"Kasse Import Standardformat"
EUR;+3,50;2025-0002;2706;"Kassenumsatz: Getränk";;;43000;;;;;"Kasse Import Standardformat"
```
- **Vorteil:** Jede Transaktion einzeln sichtbar
- **Ideal für:** Detaillierte Analyse

### **Nur Kassenbewegungen**
```csv
EUR;+2500,00;2025-0001;2706;"Einlage Registrierkasse";;;43000;;;;;"Kasse Import Standardformat"
EUR;+6232,00;2025-0002;2706;"Tageseinnahme Registrierkasse";;;43000;;;;;"Kasse Import Standardformat"
EUR;-2872,00;2025-0003;2706;"Entnahme Registrierkasse";;;13720;;;;;"Kasse Import Standardformat"
```
- **Vorteil:** Alle kassenwirksamen Bewegungen
- **Ideal für:** Vollständige Kassenführung

---

## 🏦 **Konten-Zuordnung**

| Bewegung | Konto | Gegenkonto | Beispiel |
|----------|-------|------------|----------|
| **Einlage** | 1200 (Kasse) | **43000** | Anfangsbestand, Wechselgeld |
| **Einnahme** | 1200 (Kasse) | **43000** | Verkaufserlöse, Tickets |
| **Entnahme Bar** | 1200 (Kasse) | **13720** | Bargeldentnahme |
| **Entnahme EC** | 1200 (Kasse) | **13721** | SUMUP/EC-Cash Entnahme |

---

## ⚠️ **Häufige Probleme & Lösungen**

### Problem: "Spalten werden nicht richtig erkannt"
**Lösung:** 
- Trennzeichen auf `;` (Semikolon) stellen
- Textqualifizierer auf `"` setzen

### Problem: "Datum wird nicht erkannt"
**Lösung:**
- Datumsformat auf `TTMM` stellen
- Nicht `TT.MM.JJJJ` verwenden

### Problem: "Negative Beträge werden falsch importiert"
**Lösung:**
- Das ist normal - DATEV erkennt `-` als Haben-Buchung
- Entnahmen werden automatisch korrekt gebucht

### Problem: "Umlaute werden falsch dargestellt"
**Lösung:**
- Das einfache CSV-Format nutzt UTF-8
- In DATEV: Encoding auf "UTF-8" stellen

---

## 🎯 **Tipps für optimalen Import**

1. **Klein anfangen:** Testen Sie zunächst mit einem Tag oder einer Woche
2. **Regelmäßig importieren:** Monatlich oder wöchentlich für bessere Übersicht  
3. **Vor Import prüfen:** Kontrollieren Sie die CSV-Datei vor dem Import
4. **Testmandant nutzen:** Erste Versuche in einem Testmandanten durchführen
5. **Backup:** Erstellen Sie vor Import ein Backup Ihrer DATEV-Daten

---

## ✅ **Erfolgreicher Import - Was passiert dann?**

Nach erfolgreichem Import sehen Sie in DATEV:
- **Neue Buchungszeilen** in der Buchungsübersicht
- **Korrekte Kontenbuchungen** (1200 ↔ 43000/13720/13721)
- **Nachvollziehbare Belegnummern** (2025-0001, 2025-0002, ...)
- **Aussagekräftige Buchungstexte** ("Tageseinnahme Registrierkasse")

**→ Ihre Kassenbuchführung ist jetzt digital und DATEV-konform! 🎉**