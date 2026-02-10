# Alternative Arbeitszeitberechnung für Kimai

[English](README.md) | **Deutsch**

Ein Kimai-Plugin, das Arbeitszeiten anhand der tatsächlichen Start- und Endzeiten von Zeiteinträgen berechnet, anstatt die (oft gerundeten) abrechenbaren Zeiten der einzelnen Einträge zu summieren. Überlappende Einträge werden automatisch zusammengeführt, doppelt gezählte Zeiten eliminiert und Pausen korrekt berücksichtigt.

## Das Problem

Kimais Standard-Arbeitszeitberechnung summiert die Dauer aller Zeiteinträge. Das führt zu zwei Problemen:

### 1. Überlappende Einträge

Bei überlappenden Einträgen wird die überlappende Zeit doppelt gezählt:

| Eintrag | Start | Ende | Dauer |
|---------|-------|------|-------|
| A | 14:34 | 15:19 | 0:45 |
| B | 14:35 | 16:03 | 1:28 |
| **Standard** | | | **2:13** |
| **Dieses Plugin** | | | **1:29** |

### 2. Rundungen verfälschen die Arbeitszeit

Abrechenbare Zeiten werden für die Kundenabrechnung oft gerundet (z.B. auf 15-Minuten-Schritte), während Start- und Endzeiten minutengenau erfasst werden. Wenn Kimai die gerundeten Zeiten summiert, entspricht das Ergebnis nicht mehr der tatsächlichen Arbeitszeit gemäß Arbeitsvertrag.

| Eintrag | Start | Ende | Tatsächlich | Gerundet (15 min) |
|---------|-------|------|-------------|-------------------|
| A | 08:02 | 08:47 | 0:45 | 0:45 |
| B | 10:00 | 10:22 | 0:22 | 0:30 |
| C | 14:15 | 14:29 | 0:14 | 0:15 |
| **Summe** | | | **1:21** | **1:30** |

Dieses Plugin verwendet die genauen Start- und Endzeiten. Dadurch können die Rundungseinstellungen für abrechenbare Zeiten den Vorgaben der Kundenabrechnung entsprechend angepasst werden, ohne die Arbeitszeiterfassung zu beeinflussen.

### Empfohlene Kimai-Einstellungen

Für eine genaue Arbeitszeiterfassung mit diesem Plugin:

- **Start- und Endzeiten auf ganze Minuten runden** – Ohne Rundung gibt es Unstimmigkeiten zwischen der Anzeige (auf Minuten geclipped) und den berechneten Zeiten
- **Rundung der abrechenbaren Dauer** – Kann frei nach Kundenabrechnungs-Vorgaben konfiguriert werden, ohne die Arbeitszeitberechnung zu beeinflussen

## Funktionen

- **Überlappende Einträge zusammenführen**: Verbundene Einträge werden zu Zeitspannen zusammengeführt
- **Lückentoleranz**: Kleine Lücken zwischen Einträgen (Standard: 3 Minuten) werden ignoriert
- **Pausenabzug**: Pausen aus allen zusammengeführten Einträgen werden abgezogen
- **Nachtarbeit**: Einträge über Mitternacht hinaus gehören zum Starttag
- **Überlappungserkennung**: Einträge an Tag X+1, die mit Nachteinträgen von Tag X überlappen, werden zusammengeführt
- **Konfigurierbare Grenzen**: Maximale Einzelaufgaben-Dauer (Standard: 16 Stunden)

## Installation

1. Plugin nach `var/plugins/` kopieren:
   ```bash
   cd /path/to/kimai
   git clone https://github.com/ralf1070/WorkingTimeSpanBundle.git var/plugins/WorkingTimeSpanBundle
   ```

2. Cache leeren:
   ```bash
   bin/console cache:clear
   ```

3. Installation überprüfen:
   ```bash
   bin/console kimai:plugins
   ```

## Konfiguration

Navigiere zu **System → Einstellungen → Arbeitszeitberechnung**

| Einstellung | Standard | Beschreibung |
|-------------|----------|--------------|
| Alternative Berechnung aktivieren | An | Plugin aktivieren/deaktivieren |
| Lückentoleranz | 3 min | Maximale Lücke zwischen Einträgen, um noch als verbunden zu gelten |
| Max. Aufgabendauer | 16 h | Einzeleinträge länger als dieser Wert werden ausgeschlossen |

### Behandlung der Soll-Zeit

Die Konfiguration, wie Abwesenheiten (Feiertage, Urlaub, Krankheit) die Soll- vs. Ist-Zeit beeinflussen, erfolgt über **WorkContractBundle v1.30.0+** (System → Einstellungen → Arbeitsvertrag).

## So funktioniert es

### Einfaches Beispiel

Drei Einträge am selben Tag:
- 08:00 - 12:00 (4h)
- 11:30 - 13:00 (1,5h, überlappt!)
- 14:00 - 17:00 (3h)

**Standard Kimai**: 4h + 1,5h + 3h = 8,5h

**Dieses Plugin**:
- Spanne 1: 08:00 - 13:00 (5h, zusammengeführt wegen Überlappung)
- Spanne 2: 14:00 - 17:00 (3h, getrennt wegen Lücke > 3 min)
- Gesamt: 8h

### Nachtarbeit-Beispiel

- Eintrag A: 13.01., 15:35 → 14.01., 01:06 (gehört zum 13.01.)
- Eintrag B: 14.01., 00:45 → 14.01., 01:16 (überlappt mit A!)

Eintrag B wird dem 13.01. zugeordnet, weil er mit dem Nachteintrag überlappt. Der 14.01. zeigt 0 Stunden.

## Tests

Das Plugin enthält Unit-Tests für den TimeSpanCalculator, die alle Berechnungsszenarien abdecken (Lückentoleranz, Nachtarbeit, Überlappungen, Jahresgrenzen, Pausen).

```bash
# Alle Plugin-Test-Suites
composer tests-plugins

# Nur dieses Plugin
composer tests-plugins -- --plugin WorkingTimeSpanBundle
```

Erfordert Kimai mit `LOAD_PLUGINS_IN_TEST`-Unterstützung (Branch `feature/plugin-test-support` auf https://github.com/ralf1070/kimai).

## Voraussetzungen

- Kimai 2.32.0 oder höher

## Lizenz

MIT

## Autor

Ralf Müller - [bj-ig.de](https://www.bj-ig.de)
