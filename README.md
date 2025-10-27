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

### 3. GRETA Competence Model Integration

The plugin supports the GRETA Competence Model 2.0 with the following main areas:

#### Competence Areas:
1. **Professional Practical Knowledge and Skills**
   - Didactics and Methodology
   - Counseling/Individualized Learning Support
   - Communication and Interaction
   - Organization

2. **Subject-Specific and Field-Specific Knowledge**
   - Field Reference

3. **Professional Values and Beliefs**
   - Professional Ethics
   - Profession-Related Beliefs

4. **Professional Self-Regulation**
   - Motivational Orientations
   - Self-Regulation
   - Professional Practical Experience

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

### File Structure

```
mod_tsbadge/
├── classes/
│   ├── badge/tsbadge.php              # Badge class
│   └── output/form/
│       └── walletsendconfirm_form.php # Confirmation form
├── data/                              # Badge sample data
├── db/
│   ├── access.php                     # Permissions
│   └── install.xml                    # Database schema
├── files/                             # Badge files
├── js/
│   └── tsconnectorpoll.js            # JavaScript for polling
├── lang/
│   ├── de/tsbadge.php                # German language strings
│   └── en/tsbadge.php                # English language strings
├── ressources/                        # Resources and examples
├── delete_connection.php              # Delete connection
├── get_connector_adress.php          # Get connector address
├── index.php                         # Plugin index
├── lib.php                           # Main plugin functions
├── locallib.php                      # Local helper functions
├── mod_form.php                      # Activity form
├── settings.php                      # Admin settings
├── tsconnectorpoll.php              # Polling script
├── version.php                       # Plugin version
└── view.php                          # Activity view
```

### API Endpoints

The plugin communicates with the following connector endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Connector health status |
| `/api/v2/Account/Sync` | POST | Account synchronization |
| `/api/v2/Account/IdentityInfo` | GET | Account information |
| `/api/v2/Attributes` | GET/POST | Manage attributes |
| `/api/v2/Relationships` | GET | Retrieve relationships |
| `/api/v2/RelationshipTemplates/Own` | POST | Create relationship templates |
| `/api/v2/Requests/Outgoing` | POST | Outgoing requests |
| `/api/v2/Messages` | POST | Send messages |

## Development

### Customizing Badge Data

Badge data can be customized through the form interface or directly in the database:

```php
// Example: Setting badge data programmatically
$badgedata = json_encode([
    'trainspotBadge' => [
        'learningObjectMetadata' => [
            'objectTitle' => 'Your Course Title',
            'competencePath' => 'greta_v2_0/...',
            // additional metadata
        ],
        'badge' => [
            'badgeType' => 'Provider Badge',
            'badgeName' => 'Your Badge Name',
            // additional badge properties
        ]
    ]
]);
```

### Event Handling

The plugin responds to Moodle events for automatic badge issuance:

```php
// In lib.php - automatic badge issuance on course completion
function tsbadge_cm_info_dynamic(cm_info $cm) {
    // Check availability conditions
    // Automatic badge issuance
}
```

### Debugging

Enable debugging output by:

1. **Enable Moodle Debugging**
2. **Check Console Logs** in `tsconnectorpoll.js`
3. **Review API Responses** in functions in `locallib.php`

## Security

### Authentication
- API key-based authentication
- Secure cURL connections
- User session validation

### Privacy
- Minimal data transmission
- User preferences for connection data
- Optional connection deletion

### Encryption
- HTTPS communication required
- JSON data transmission
- Secure token management

## Troubleshooting

### Common Issues

#### 1. Connector not reachable
```
Symptom: "Error checking connector health"
Solution: 
- Check connector URL
- Test network connection
- Validate API key
```

#### 2. Badge transfer failed
```
Symptom: "Error sending message"
Solution:
- Check wallet connection
- Validate JSON format
- Analyze API response
```

#### 3. QR code not displayed
```
Symptom: Empty QR code display
Solution:
- Check relationship template ID
- Validate API permissions
- Check browser console
```

## Support and Community

### Documentation
- [GRETA Competence Model](https://www.greta-die.de/)
- [Mein Bildungsraum](https://www.meinbildungsraum.de/)
- [Moodle Developer Docs](https://docs.moodle.org/dev/)

### Developer Contact
- **Institution**: ILD TH Lübeck
- **Email**: dev.ild@th-luebeck.de
- **License**: GNU GPL v3 or later

### Version and Changelog

#### Version 2025.02.10.01
- Initial release
- GRETA Competence Model 2.0 integration
- Wallet connection implementation
- Multi-language support (DE/EN)

## Contributing and Development

### Code Standards
- Follow Moodle Coding Guidelines
- Use PHPDoc comments
- Apply secure coding practices

### Testing
```bash
# Run unit tests
php admin/tool/phpunit/cli/util.php --install
php vendor/bin/phpunit mod/tsbadge/tests/

# Behat tests
php admin/tool/behat/cli/init.php
php vendor/bin/behat --config mod/tsbadge/tests/behat/
```

### Contributions
1. Create fork of repository
2. Create feature branch
3. Write and run tests
4. Create pull request

---

**Note**: This plugin is part of the TrainSpot project and enables seamless integration of digital competence certificates into the modern education ecosystem.

