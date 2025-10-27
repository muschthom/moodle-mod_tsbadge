# Trainspot Badge Plugin for Moodle

## Overview

The **Trainspot Badge Plugin** is a Moodle plugin that enables the integration of digital badges with the "Mein Bildungsraum" (My Education Space) wallet system. The plugin is based on the GRETA Competence Model and allows digital competence certificates to be awarded to learners and transferred directly to their digital wallet.

## Main Features

### 🎯 Digital Badge Management
- Creation and management of Trainspot badges
- Support for GRETA Competence Model
- JSON-based badge data structure
- Automatic badge issuance based on activity completion

### Wallet Integration
- Direct connection to "Mein Bildungsraum" wallet
- QR code-based wallet connection
- Secure transfer of badge data
- Relationship management between connector and wallet

### API Integration
- RESTful API communication with the connector
- Automatic account synchronization
- Attribute management and transfer
- Health check functionality

#
## Installation

### 1. Plugin Installation
```bash
# Copy plugin to Moodle directory
cp -r mod_tsbadge /path/to/moodle/mod/

# Run Moodle upgrade
php admin/cli/upgrade.php
```

### 2. Configuration

#### Administrator Settings
Navigate to: **Site Administration > Plugins > Activities > Trainspot Badge**

Configure the following settings:

| Setting | Description | Example |
|---------|-------------|---------|
| **Domain URL** | URL of the connector system | `https://connector.example.com` |
| **API Key** | Authentication key | `your-api-key-here` |
| **Connector Address** | ID of the connector | `connector-id-123` |

#### Database Schema
The plugin automatically creates the following tables:
- `mdl_tsbadge`: Main badge configuration
- `mdl_tsbadge_issued`: Issued badge instances

## Usage

### 1. Creating Badge Activity

1. **Open course** and "Turn editing on"
2. **Select "Add an activity or resource"**
3. **Choose "Trainspot Badge"** from the activity list
4. **Configure badge**:
   - Enter badge name
   - Define attribute name
   - Insert badge data in JSON format

### 2. Badge Data Structure

#### Example JSON for Badge Data:
```json
{
    "trainspotBadge": {
        "learningObjectMetadata": {
            "objectTitle": "Digital Trainer Course Overall Assessment",
            "competencePath": "greta_v2_0/BerufspraktischesWissenUndKoennen/DidaktikUndMethodik/MethodenMedienUndLernmaterialien",
            "competenceID": "https://www.greta-die.de/webpages/kompetenzfacetten-index/kompetenzfacette-methoden-medien-und-lernmaterialien",
            "tree": "GRETA Competence Model 2.0",
            "aspect": "Professional Practical Knowledge and Skills",
            "area": "Didactics and Methodology",
            "facet": "Methods, Media and Learning Materials",
            "level": 2,
            "educational_typicalLearningTime_duration": "4h30m",
            "issuer": {
                "id": "https://www.th-luebeck.de/",
                "name": "Technische Hochschule Lübeck"
            }
        },
        "badge": {
            "badgeType": "Provider Badge",
            "badgeName": "TrainSpot Provider Badge",
            "badgeID": "id123456789",
            "badgeValue": 0.2,
            "badgeUnit": "ECTS",
            "creditStandard": "ECTS"
        }
    }
}
```


## Wallet Connection

### Connection Process

1. **Scan QR code**: Learners open the "Mein Bildungsraum" app and scan the QR code
2. **Confirm connection**: Confirm the connection in the app
3. **Automatic synchronization**: Plugin automatically detects the new connection
4. **Badge transfer**: After successful connection, badges can be transferred

### Wallet Apps

The "Mein Bildungsraum" wallet is available at:
- **Apple App Store**: [Mein Bildungsraum Wallet](https://apps.apple.com/de/app/mein-bildungsraum-wallet/id6467007352)
- **Google Play Store**: [Mein Bildungsraum Wallet](https://play.google.com/store/apps/details?id=de.bildungsraum.wallet.beta&pli=1)

## Technical Architecture

### Component Overview

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Moodle LMS    │    │  Trainspot       │    │ Mein Bildungs-  │
│                 │◄──►│  Connector       │◄──►│ raum Wallet     │
│  Badge Plugin   │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```
