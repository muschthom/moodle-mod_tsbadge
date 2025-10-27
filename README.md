# Trainspot Badge Plugin für Moodle

## Übersicht

Das **Trainspot Badge Plugin** ist ein Moodle-Plugin, das die Integration digitaler Badges mit dem "Mein Bildungsraum" Wallet-System ermöglicht. Das Plugin basiert auf dem GRETA Kompetenzmodell 2.0 und erlaubt es, digitale Kompetenznachweise an Lernende zu vergeben und diese direkt in deren digitale Wallet zu übertragen.

## Hauptfunktionen

### Digitale Badge-Erstellung
- Erstellung von Trainspot Badges
- Unterstützung für GRETA Kompetenzmodell 2.0
- JSON-basierte Badge-Datenstruktur
- Automatische Badge-Vergabe basierend auf Aktivitätsabschluss

### Wallet-Integration
- Direkte Verbindung zu "Mein Bildungsraum" Wallet
- QR-Code-basierte Wallet-Verbindung
- Sichere Übertragung von Badge-Daten
- Relationship-Management zwischen Connector und Wallet

### 📊 API-Integration
- RESTful API-Kommunikation mit dem Connector
- Automatische Account-Synchronisation
- Attribute-Management und -Übertragung
- Health-Check-Funktionalität

## Installation

### 1. Plugin-Installation
```bash
# Plugin in das Moodle-Verzeichnis kopieren
cp -r mod_tsbadge /path/to/moodle/mod/

# Moodle-Upgrade durchführen
php admin/cli/upgrade.php
```

### 2. Konfiguration

#### Administrator-Einstellungen
Navigieren Sie zu: **Website-Administration > Plugins > Aktivitäten > Trainspot Badge**

Konfigurieren Sie folgende Einstellungen:

| Einstellung | Beschreibung | Beispiel |
|-------------|--------------|----------|
| **Domain URL** | URL des Connector-Systems | `https://connector.example.com` |
| **API Key** | Authentifizierungs-Schlüssel | `your-api-key-here` |
| **Connector Address** | ID des Connectors | `connector-id-123` |

#### Datenbank-Schema
Das Plugin erstellt automatisch folgende Tabellen:
- `mdl_tsbadge`: Haupt-Badge-Konfiguration
- `mdl_tsbadge_issued`: Ausgestellte Badge-Instanzen

## Verwendung

### 1. Badge-Aktivität erstellen

1. **Kurs aufrufen** und "Bearbeiten einschalten"
2. **"Material oder Aktivität hinzufügen"** wählen
3. **"Trainspot Badge"** aus der Aktivitätenliste auswählen
4. **Badge konfigurieren**:
   - Badge-Name eingeben
   - Attributname definieren
   - Badge-Daten im JSON-Format einfügen

### 2. Badge-Datenstruktur

#### Beispiel-JSON für Badge-Daten:
```json
{
    "trainspotBadge": {
        "learningObjectMetadata": {
            "objectTitle": "Digital Trainer Kurs Gesamtbilanz",
            "competencePath": "greta_v2_0/BerufspraktischesWissenUndKoennen/DidaktikUndMethodik/MethodenMedienUndLernmaterialien",
            "competenceID": "https://www.greta-die.de/webpages/kompetenzfacetten-index/kompetenzfacette-methoden-medien-und-lernmaterialien",
            "tree": "GRETA Kompetenzmodell 2.0",
            "aspect": "Berufspraktisches Wissen und Können",
            "area": "Didaktik und Methodik",
            "facet": "Methoden, Medien und Lernmaterialien",
            "level": 2,
            "educational_typicalLearningTime_duration": "4h30m",
            "issuer": {
                "id": "https://www.th-luebeck.de/",
                "name": "Technische Hochschule Lübeck"
            }
        },
        "badge": {
            "badgeType": "Anbieter-Badge",
            "badgeName": "TrainSpot Anbieter-Badge",
            "badgeID": "id123456789",
            "badgeValue": 0.2,
            "badgeUnit": "ECTS",
            "creditStandard": "ECTS"
        }
    }
}
```

### 3. GRETA Kompetenzmodell Integration

Das Plugin unterstützt das GRETA Kompetenzmodell 2.0 mit folgenden Hauptbereichen:

#### Kompetenzbereiche:
1. **Berufspraktisches Wissen und Können**
   - Didaktik und Methodik
   - Beratung/Individualisierte Lernunterstützung
   - Kommunikation und Interaktion
   - Organisation

2. **Fach- und Feldspezifisches Wissen**
   - Feldbezug

3. **Professionelle Werthaltungen und Überzeugungen**
   - Berufsethos
   - Berufsbezogene Überzeugungen

4. **Professionelle Selbststeuerung**
   - Motivationale Orientierungen
   - Selbstregulation
   - Berufspraktische Erfahrung

## Wallet-Verbindung

### Verbindungsprozess

1. **QR-Code scannen**: Lernende öffnen die "Mein Bildungsraum" App und scannen den QR-Code
2. **Verbindung bestätigen**: In der App die Verbindung bestätigen
3. **Automatische Synchronisation**: Plugin erkennt die neue Verbindung automatisch
4. **Badge-Übertragung**: Nach erfolgreicher Verbindung können Badges übertragen werden

### Wallet Apps

Die "Mein Bildungsraum" Wallet ist verfügbar in:
- **Apple App Store**: [Mein Bildungsraum Wallet](https://apps.apple.com/de/app/mein-bildungsraum-wallet/id6467007352)
- **Google Play Store**: [Mein Bildungsraum Wallet](https://play.google.com/store/apps/details?id=de.bildungsraum.wallet.beta&pli=1)

## Technische Architektur

### Komponenten-Übersicht

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Moodle LMS    │    │  Trainspot       │    │ Mein Bildungs-  │
│                 │◄──►│  Connector       │◄──►│ raum Wallet     │
│  Badge Plugin   │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

