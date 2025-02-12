<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library of functions for module ilddigitalcert
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


function get_user_badges_data() {
    global $DB, $USER;
    $badgesData = [];

    if (empty($DB) || empty($USER) || empty($USER->id)) {
        return $badgesData;
    }

    $issuedBadges = $DB->get_records('badge_issued', array('userid' => $USER->id));

    if (empty($issuedBadges)) {
        return $badgesData;
    }


    global $CFG;
    require_once($CFG->dirroot . '/badges/classes/badge.php');

    foreach ($issuedBadges as $issuedBadge) {
        $badgeObj = new badge($issuedBadge->badgeid);
        $badgeDetails = $DB->get_record('badge', array('id' => $issuedBadge->badgeid));

        if ($badgeDetails && $badgeObj) {
            $badge_context = $badgeObj->get_context();
            $imageurl = moodle_url::make_pluginfile_url($badge_context->id, 'badges', 'badgeimage', $issuedBadge->badgeid, '/', 'f1', false); // f1 für großes Bild

            $badgesData[] = [
                'title' => $badgeDetails->name,
                'description' => 'Beschreibung für ' . $badgeDetails->name,
                'imageurl' => $imageurl->out(false), // Wandelt die URL in einen String um, ohne escaping
                'badgeid' => $badgeDetails->id // Badge-ID für den Button
            ];
        }
    }

    return $badgesData;
}



function get_user_certificates_data() {
    global $DB, $USER;
    $certificatesData = [];

    $issuedCerts = $DB->get_records('tool_certificate_issues', array('userid' => $USER->id));

    foreach ($issuedCerts as $issuedCert) {
        $certData = json_decode($issuedCert->data, true);
        global $CFG;
        $certificatesData[] = [
            'certtitle' => isset($certData['coursefullname']) ? $certData['courseshortname'] : 'Unbekannter Titel',
            //'imageurlcert' => $CFG->dirroot . "/mod/coursecertificate/pix/monologo.png",
            'imageurlcert' => $CFG->wwwroot . "/blocks/walletsend/files/monologo.svg",


        ];
    }
    return $certificatesData;
}


function checkConnectorHealth($host) {
    $url = $host . "/health";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($statusCode === 200) {
        $data = json_decode($response, true);
        if ($data['isHealthy']) {
            echo "<br/>Connector is healthy.\n";
        } else {
            echo "<br/>Problem with connector.\n";
        }
    } else {
        echo "<br/>Error checking connector health.\n";
    }
    return $data['isHealthy'];
}


function getConnectorAttributes($host, $apiKey) {
    $url = $host . "/api/v2/Attributes";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-KEY: $apiKey"
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        echo '<br/>cURL-Fehler: ' . curl_error($ch);
        return null;
    }
    curl_close($ch);
    //return json_decode($response, true);
    return $response;
}

function deleteConnectorAttribute($host, $apiKey, $attributeId) {
    $url = $host . "/api/v2/Attributes/" . $attributeId;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-KEY: $apiKey"
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        echo 'cURL-Fehler: ' . curl_error($ch);
    } else {
        echo 'Antwort: ' . $response;
    }
    curl_close($ch);
}


function createConnectorAttribute($host, $apiKey, $connectorAddress) {
    $payload = [
        "content" => [
            "value" => [
                "@type" => "DisplayName",
                "value" => "Trainspot2 THL Test Connector"
            ]
        ]
    ];

    $url = $host . "/api/v2/Attributes";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-KEY: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTP-Statuscode der Antwort
    if ($response === false) {
        echo '<br/>cURL-Fehler: ' . curl_error($ch);
    }
    curl_close($ch);
    echo "<br/>createConnectorAttribute response: $response<br/>";
    $responseObj = json_decode($response);
    $id = $responseObj->result->id;
    return $id;
}

function getDisplayNameId($json) {
    // JSON-Daten dekodieren
    $data = json_decode($json, true);

    // Überprüfen, ob das "result"-Array vorhanden ist
    if (!isset($data['result']) || !is_array($data['result'])) {
        return null;
    }

    // Durch die Einträge iterieren und nach "@type":"DisplayName" suchen
    foreach ($data['result'] as $item) {
        if (isset($item['content']['value']['@type']) && $item['content']['value']['@type'] === 'DisplayName') {
            return $item['id']; // ID zurückgeben, wenn "DisplayName" gefunden wurde
        }
    }

    // Wenn kein "DisplayName" gefunden wurde, null zurückgeben
    return null;
}

function validateOutgoingRequest($host, $apiKey, $peerId, $contentData) {
    $url = $host . "/api/v2/Requests/Outgoing/Validate";
    $apiUrl = $url;
    // Hier erfolgt die Kodierung von $contentData, das bereits die richtige Struktur hat
    $payload = json_encode([
        "content" => $contentData['content'], // Zugriff auf das 'content'-Array direkt
        "peer" => $peerId
    ]);

    // Initialisiere cURL
    $ch = curl_init($apiUrl);

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-KEY: $apiKey" // Füge den API-Schlüssel im Header hinzu
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Schließe cURL
    curl_close($ch);

    // Überprüfe den Statuscode der Antwort
    if ($statusCode < 400) {
        // Die Antwort erfolgreich verarbeiten
        //echo "<br/>validateOutgoingRequest Validierung erfolgreich: $response\n";
    } else {
        // Fehlerbehandlung
        echo "<br/>validateOutgoingRequest Validierung fehlgeschlagen: HTTP-Statuscode $statusCode\n";
    }
}

function createRelationshipTemplate($host, $apiKey, $contentData) {
    $url = $host . "/api/v2/RelationshipTemplates/Own";
    $apiUrl = $url;

    // Hier erfolgt die Kodierung von $contentData, das bereits die richtige Struktur hat
    $payload = json_encode($contentData);

    // Initialisiere cURL
    $ch = curl_init($apiUrl);

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-KEY: $apiKey" // Füge den API-Schlüssel im Header hinzu
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    echo $response;
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Schließe cURL
    curl_close($ch);

    // Überprüfe den Statuscode der Antwort
    if ($statusCode < 400) {
        // Die Antwort erfolgreich verarbeiten
        //echo "<br/>createRelationshipTemplate Validierung erfolgreich: $response\n";
    } else {
        // Fehlerbehandlung
        echo "<br/>createRelationshipTemplate Validierung fehlgeschlagen: HTTP-Statuscode $statusCode\n";
    }
    // Auf die ID zugreifen und in einer Variablen speichern
    $responseObj = json_decode($response);

    $id = $responseObj->result->id;

    // Ausgabe der RelationshipTemplate ID
    return $id;
}


function getRelationship($host, $relationshipId, $xApiKey) {
    // Erstelle die vollständige URL für die Anfrage
    $apiUrl = $host . '/api/v2/Relationships/' . $relationshipId;

    // Initialisiere cURL
    $ch = curl_init($apiUrl);

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Rückgabe der Antwort als String
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', // Header für den JSON-Typ
        "X-API-KEY: $xApiKey" // Füge den API-Schlüssel im Header hinzu
    ]);

    // cURL sollte eine GET-Anfrage senden
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Überprüfe auf Fehler
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        // Logge oder behandle den Fehler hier nach Bedarf
        error_log('cURL error: ' . $error_msg);
        $response = null;
    }

    // Schließe cURL
    curl_close($ch);

    // Überprüfe den HTTP-Statuscode und dekodiere die Antwort nur bei Erfolg
    if ($statusCode == 200) {
        return json_decode($response, true);
    } else {
        // Fehlerbehandlung bei nicht erfolgreicher Antwort (z.B. Logging)
        error_log("API request failed with status code: $statusCode");
        return null;
    }
}


function getQrCode($host, $apiKey, $templateId) {
    $url = $host . "/api/v2/RelationshipTemplates/" . $templateId;
    $apiUrl = $url;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: image/png",
        "x-api-key: $apiKey"
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return;
    }

    curl_close($ch);

    if ($statusCode === 200 && $response !== false) {
        $base64Image = base64_encode($response);

        echo "<br/>";
        echo '<img src="data:image/png;base64,' . $base64Image . '" alt="QR Code">';
    } else {
        echo "<br/>";
        echo "Fehler beim Abrufen des QR-Codes: HTTP-Statuscode $statusCode";
    }
}


function syncAccount($host, $apiKey) {
    $url = $host . "/api/v2/Account/Sync";
    // Initialisiere cURL
    $ch = curl_init();

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Prüfe auf cURL-Fehler
    if (curl_errno($ch)) {
        echo "cURL Fehler: " . curl_error($ch);
    }

    // Schließe cURL
    curl_close($ch);


    // Wenn der Statuscode 204 ist, keine Inhalte zurückgeben, aber als Erfolg werten
    if ($statusCode === 204) {
        return ['status_code' => 204];
    }

    // Wenn der Statuscode erfolgreich (<300) ist und eine Antwort vorliegt
    if ($statusCode < 300 && $response !== false) {
        return json_decode($response, true);
    } else {
        // Fehlerbehandlung für alle anderen Fälle
        return null;
    }
}


function acceptRelationshipChange($host, $apiKey, $relationshipId) {
    global $DB, $USER;

    $url = $host . "/api/v2/Relationships/" . $relationshipId . "/Accept";

    // Initialisiere cURL
    $ch = curl_init();

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);

    $response_data = json_decode($response, true);

    // Prüfe auf cURL-Fehler
    if (curl_errno($ch)) {
        echo "cURL Fehler: " . curl_error($ch) . "\n";
        curl_close($ch);
        return;
    }

    // Statuscode und Antwortinhalt für Debugging anzeigen
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (isset($response_data['result']['peer'])) {
        $wallet_id = $response_data['result']['peer'];
        set_user_preference('mod_tsbadge_wallet_id', $wallet_id, $USER->id);
    }
}



/*
function handleRelationshipProcess($host, $apiKey, $templateId) {
    global $DB, $USER;

    //hole relationship id, wenn vorhanden
    $relationshipid = get_user_preferences('mod_tsbadge_relationship_id', 'error', $USER->id);
    $templateid = get_user_preferences('mod_tsbadge_template_id', 'error', $USER->id);
    $walletid = get_user_preferences('mod_tsbadge_wallet_id', 'error', $USER->id);
    //if ($relationshipid == 'error') {
    if ($relationshipid == 'error' || $templateid == 'error' || $walletid == 'error') {
        //echo "keine relationshipid vorhanden \n"; 
        echo "<p>Um Ihr digitales Zertifikat an die Wallet zu senden, müssen Sie erst 
    eine Verbindung zu dieser herstellen. Öffnen Sie dazu die <a href='https://www.meinbildungsraum.de/' 
    target='blank'>Mein Bildungsraum-App</a> und scannen Sie den QR-Code. Folgen Sie anschließend 
    den Anweisungen in der App. Die App kann im <a href = 'https://apps.apple.com/de/app/mein-bildungsraum-wallet/id6467007352' 
    target='blank'>Apple Store</a> und im <a href='https://play.google.com/store/apps/details?id=de.bildungsraum.wallet.beta&pli=1'  
    target='blank'>Google Play Store</a> heruntergeladen werden.
    <br/>
    <h2>Nach dem Scannen Seite bitte neu laden!</h2>";


        // Schritt 1: QR-Code für das RelationshipTemplate abrufen        
        getQrCode($host, $apiKey, $templateId);

        echo '<p id="poll-info" style="color:black;display:none;">' . get_string('waiting_for_request', 'mod_tsbadge') . '</p>';
        // Warte und gib dem Benutzer Zeit, den QR-Code zu scannen und die Beziehung zu initiieren
        // Dies ist eher ein konzeptioneller Schritt. In einer echten Anwendung müsstest du auf ein Benutzereingriff warten oder regelmäßig den Status prüfen.
        //echo "<br/>Warte auf die Beziehungsanfrage...";
        //echo "<h1>Wenn Code gescannt ist, bitte 1 x Seite neu laden!</h1>";

        //nach bestätigung des neuen kontakts in app
        // Schritt 2: Account synchronisieren, um nach neuen Beziehungsanfragen zu suchen
        $relationshipData = syncAccount($host, $apiKey);
        //echo "relationshipdata = " . "<br/>";
        //var_dump($relationshipData);
        //echo "<br/>";

        // Prüfen, ob es neue Beziehungsanfragen gibt
        //if ($relationshipData && !empty($relationshipData['result']['relationships'])) {
        if (!empty($relationshipData['result']['relationships'])) {
            echo "<script>location.reload();</script>";

            foreach ($relationshipData['result']['relationships'] as $relationship) {
                if ($relationship['status'] === 'Pending') {
                    // Gehe durch alle pending Änderungen
                    foreach ($relationship['changes'] as $change) {
                        if ($change['status'] === 'Pending' && $change['type'] === 'Creation') {
                            // Schritt 3: Beziehungsänderung akzeptieren
                            echo "<br/>Akzeptiere Beziehungsanfrage...";
                            set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);


                            acceptRelationshipChange($host, $apiKey, $relationship['id'], $change['id']);
                            break; // Annahme, dass nur eine Änderung akzeptiert werden muss

                        }
                    }
                    echo "<script>window.location.reload();</script>";
                }
                echo "<script>window.location.reload();</script>";
            }
        } else {
            //echo "<br/>Keine neuen Beziehungsanfragen gefunden.";
        }
    }
}
    */

function handleRelationshipProcess($host, $apiKey, $templateId) {
    global $USER, $CFG;

    // Check required preferences
    $relationshipid = get_user_preferences('mod_tsbadge_relationship_id', 'error', $USER->id);
    $templateid = get_user_preferences('mod_tsbadge_template_id', 'error', $USER->id);
    $walletid = get_user_preferences('mod_tsbadge_wallet_id', 'error', $USER->id);

    // If all preferences exist, nothing to do
    if ($relationshipid !== 'error' && $templateid !== 'error' && $walletid !== 'error') {
        return true;
    }

    // Show wallet connection instructions
    echo '<div class="wallet-instructions">';
    echo '<p>' . get_string('wallet_connect_instructions', 'mod_tsbadge') . '</p>';
    echo '</div>';

    // Generate and display QR code
    getQrCode($host, $apiKey, $templateId);

    // Setup polling
    echo '<p id="poll-info" style="color:black;display:none;">' . get_string('waiting_for_request', 'mod_tsbadge') . '</p>';

    // Check for pending relationships
    $relationshipData = syncAccount($host, $apiKey);
    if (!empty($relationshipData['result']['relationships'])) {
        foreach ($relationshipData['result']['relationships'] as $relationship) {
            if ($relationship['status'] === 'Pending') {
                // Store relationship ID and accept it
                set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);
                acceptRelationshipChange($host, $apiKey, $relationship['id']);

                // Single reload point
                echo "<script>window.location.reload();</script>";
                exit();
            }
        }
    }

    return false;
}

/*
function getQrCodeAndSync($host, $apiKey, $templateId)
{
    // Generate and display QR code
    getQrCode($host, $apiKey, $templateId);

    // Display status indicator
    echo "<br/>Waiting for relationship request...";

    // Asynchronous account synchronization using XMLHttpRequest
    $xhr = new XMLHttpRequest();
    $xhr->open('GET', syncAccountUrl($host, $apiKey));
    $xhr->onload = function () {
        if ($xhr->status === 200) {
            $relationshipData = JSON . parse($xhr->responseText);

            // Check for new relationship requests and accept
            if ($relationshipData && !empty($relationshipData['result']['relationships'])) {
                foreach ($relationshipData['result']['relationships'] as $relationship) {
                    if ($relationship['status'] === 'Pending') {
                        foreach ($relationship['changes'] as $change) {
                            if ($change['status'] === 'Pending' && $change['type'] === 'Creation') {
                                acceptRelationshipChange($host, $apiKey, $relationship['id'], $change['id']);
                                // Automatic page reload (assuming browser environment)
                                location . reload();
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            console . error('Error synchronizing account:', $xhr->statusText);
        }
    };
    xhr . send();
}
*/

function get_relationshipData($validatedItems) {
    return [
        "maxNumberOfAllocations" => 1,
        "expiresAt" => "2030-12-31T00:00:00.000Z",
        "content" => [
            "@type" => "RelationshipTemplateContent",
            "title" => "Connector  Contact",
            "onNewRelationship" => [
                "items" => $validatedItems
            ]
        ]
    ];
}

function get_validatedItems($connectoraddress, $sourceAttributeId) {
    return [
        [
            "@type" => "RequestItemGroup",
            "mustBeAccepted" => true,
            "title" => "Shared Attributes",
            "items" => [
                [
                    "@type" => "ShareAttributeRequestItem",
                    "mustBeAccepted" => true,
                    "attribute" => [
                        "@type" => "IdentityAttribute",
                        "owner" => $connectoraddress,
                        "value" => [
                            "@type" => "DisplayName",
                            "value" => "Trainspot2 THL Test Connector"
                        ]
                    ],
                    "sourceAttributeId" => $sourceAttributeId
                ]
            ]
        ]
        /*,
    [
        "@type" => "RequestItemGroup",
        "mustBeAccepted" => true,
        "title" => "Requested Attributes",
        "items" => [

            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "GivenName"
                ]
            ],
            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "Surname"
                ]
            ],
            [
                "@type" => "ReadAttributeRequestItem",
                "mustBeAccepted" => true,
                "query" => [
                    "@type" => "IdentityAttributeQuery",
                    "valueType" => "EMailAddress"
                ]
            ]

        ]
            
    ]
        */
    ];
}




function get_content_data($id) {
    return [
        "content" => [
            "items" => [
                [
                    "@type" => "RequestItemGroup",
                    "title" => "Shared Attributes",
                    "items" => [
                        [
                            "@type" => "ShareAttributeRequestItem",
                            "mustBeAccepted" => true,
                            "attribute" => [
                                "@type" => "IdentityAttribute",
                                "owner" => "",
                                "value" => [
                                    "@type" => "DisplayName",
                                    "value" => "Demo Connector of integration"
                                ]
                            ],
                            "sourceAttributeId" => $id
                        ]
                    ]
                ],

                /*
                [
                    "@type" => "RequestItemGroup",
                    "mustBeAccepted" => true,
                    "title" => "Requested Attributes",
                    "items" => [
                        
                        [
                            "@type" => "ReadAttributeRequestItem",
                            "mustBeAccepted" => true,
                            "query" => [
                                "@type" => "IdentityAttributeQuery",
                                "valueType" => "GivenName"
                            ]
                        ],
                        [
                            "@type" => "ReadAttributeRequestItem",
                            "mustBeAccepted" => true,
                            "query" => [
                                "@type" => "IdentityAttributeQuery",
                                "valueType" => "Surname"
                            ]
                        ],
                        [
                            "@type" => "ReadAttributeRequestItem",
                            "mustBeAccepted" => true,
                            "query" => [
                                "@type" => "IdentityAttributeQuery",
                                "valueType" => "EMailAddress"
                            ]
                        ]
                    ]
                ]

                */


            ]
        ]
    ];
}


function sendMessage($host, $apiKey, $recipientId, $subject, $body, $cc = [], $attachments = []) {
    $url = $host . "/api/v2/Messages";

    $payload = json_encode([
        "recipients" => [$recipientId],
        "content" => [
            "@type" => "Mail",
            "to" => [$recipientId],
            "cc" => $cc,
            "subject" => $subject,
            "body" => $body,
        ],
        "attachments" => $attachments
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($statusCode === 200 || $statusCode === 201) {
        //echo "Nachricht erfolgreich gesendet.\n";
    } else {
        echo "Fehler beim Senden der Nachricht: HTTP-Statuscode $statusCode\n";
        echo "Antwort: $response\n";
    }
}


function addRelationshipAttribute($host, $apiKey, $recipientId, $subject, $body, $cc = [], $attachments = []) {
    $url = $host . "/api/v2/Messages";

    $payload = json_encode([
        "recipients" => [$recipientId],
        "content" => [
            "@type" => "Mail",
            "to" => [$recipientId],
            "cc" => $cc,
            "subject" => $subject,
            "body" => $body,
        ],
        "attachments" => $attachments
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($statusCode === 200 || $statusCode === 201) {
        //echo "Nachricht erfolgreich gesendet.\n";
    } else {
        echo "Fehler beim Senden der Nachricht: HTTP-Statuscode $statusCode\n";
        echo "Antwort: $response\n";
    }
}

/*

function send_rl_attributes($walletid, $connectorAddress, $value, $title, $host, $xapikey)
{
    $decodedValue = json_decode($value);
    //echo ("walletid = " . $walletid . "<br/>");
    //$connectorAddress = "id1PxibPC2v2zQ9SPfaoHsrfAagFiMqKGFdP"; 
    //$connectorAddress = "id1PxibPC2v2zQ9SPfaoHsrfAagFiMqKGiii";
    //echo ("connectorAddress = " . $connectorAddress . "<br/>");

    $data = [
        "content" => [
            "@type" => "Request",
            "items" => [
                [
                    "@type" => "CreateAttributeRequestItem",
                    "attribute" => [
                        "@type" => "RelationshipAttribute",
                        //"owner" => "THLuebeck",
                        //"owner" => $walletid,
                        "owner" => $connectorAddress,
                        "key" => "id123456789",
                        "confidentiality" => "public",
                        "value" => [
                            "@type" => "ProprietaryJSON",
                            //"title" => "Trainspot Testbadge Facette: Methoden, Medien und Lernmaterialien, Level 2",
                            "title" => $title,
                            "value" =>
                            $decodedValue

                        ]

                    ]
                ]
            ]
        ],
        //"peer" => $walletid
        "peer" => $connectorAddress
    ];
    //echo $connectorAddress; 
    $message = json_encode($data);
/*
    $url = $host . "/api/v2/Requests/Outgoing";
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $xapikey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
*/
//echo "Status Code: " . $statusCode . "\n";
//echo "Response: " . $response . "\n";
/*
    $messagedata = new stdClass();
    $messagedata->recipients = array($walletid);
    $messagedata->content = json_decode($response)->result->content;
    $messagedata = json_encode($messagedata, JSON_PRETTY_PRINT);
    //print_object($messagedata);die();

    $msgresult = callAPI('POST', $host . '/api/v2/Messages', $messagedata, $xapikey);
    //print_object($msgresult);die();

    return $msgresult;
}

*/
function send_rl_attributes($walletid, $connectorAddress, $value, $title, $host, $xapikey, $customAttributeKey) {
    $decodedValue = json_decode($value);

    $data = [
        "content" => [
            "@type" => "Request",
            "items" => [
                [
                    "@type" => "CreateAttributeRequestItem",
                    "attribute" => [
                        "@type" => "RelationshipAttribute",
                        "owner" => $connectorAddress,
                        "key" => $customAttributeKey, // Beliebiger eindeutiger Schlüssel für das Attribut
                        "confidentiality" => "public",
                        "value" => [
                            "@type" => "ProprietaryJSON",
                            "title" => $title,
                            "value" => $decodedValue
                        ]
                    ],
                    "mustBeAccepted" => true, // Optional, je nach Anforderung
                    "requireManualDecision" => true // Optional, je nach Anforderung
                ]
            ]
        ],
        "peer" => $walletid
    ];

    $message = json_encode($data);
    //echo "<br/>message send to wallet: " . $message ."<br><br/>"; 
    $url = $host . "/api/v2/Requests/Outgoing";
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $xapikey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($statusCode > 300) {
        // Fehlerbehandlung für fehlgeschlagene Anfrage
        return ["status" => $statusCode, "error" => $response];
    }
    echo "<br/><br>server response = " . $response . "<br/><br/>"; 
    // Bereite Nachricht zur Weiterleitung an den Peer vor
    $messagedata = new stdClass();
    $messagedata->recipients = array($walletid);
    $messagedata->content = json_decode($response)->result->content;
    //var_dump($messagedata); 
    $messagedatajson = json_encode($messagedata, JSON_PRETTY_PRINT);
    //echo $messagedatajson; 
    $msgresult = callAPI('POST', $host . '/api/v2/Messages', $messagedatajson, $xapikey);

    return $msgresult;
}


function callAPI($method, $url, $data, $xapikey, $image = false) {
    $curl = curl_init();
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        default:
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    $headerarray = array(
        'X-API-KEY: ' . $xapikey,
        'Content-Type: application/json'
    );
    if ($image) {
        $headerarray[] = 'Accept: image/png';
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headerarray);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $result = curl_exec($curl);
    if (!$result) {
        throw new moodle_exception("Connection Failure");
    }
    curl_close($curl);
    return $result;
}


function uploadbadge($host, $pdfcontentPath, $certname, $xapikey) {
    $filename = tempnam(sys_get_temp_dir(), $certname) . '.png';

    if (!file_exists($pdfcontentPath)) {
        echo "Die Datei $pdfcontentPath existiert nicht.";
        return false;
    }

    $fileContent = file_get_contents($pdfcontentPath);
    if ($fileContent === false) {
        echo "Fehler beim Lesen der Datei $pdfcontentPath.";
        return false;
    }
    file_put_contents($filename, $fileContent);
    $uploadresult = callapifileupload($filename, $certname, "description", $host . '/api/v2/Files/Own', $xapikey);
    unlink($filename);

    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        return $resultobj->result->id;
    }
    return false;
}

function uploadpdf($host, $pdfcontentPath, $certname, $xapikey) {
    $filename = tempnam(sys_get_temp_dir(), $certname) . '.pdf';

    if (!file_exists($pdfcontentPath)) {
        echo "Die Datei $pdfcontentPath existiert nicht.";
        return false;
    }

    $fileContent = file_get_contents($pdfcontentPath);
    if ($fileContent === false) {
        echo "Fehler beim Lesen der Datei $pdfcontentPath.";
        return false;
    }
    file_put_contents($filename, $fileContent);
    $uploadresult = callapifileupload($filename, $certname, "description", $host . '/api/v2/Files/Own', $xapikey);
    unlink($filename);

    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        return $resultobj->result->id;
    }
    return false;
}


function uploadjson($host, $pdfcontentPath, $certname, $xapikey) {
    $filename = tempnam(sys_get_temp_dir(), $certname) . '.json';


    if (!file_exists($pdfcontentPath)) {
        echo "Die Datei $pdfcontentPath existiert nicht.";
        return false;
    }


    $fileContent = file_get_contents($pdfcontentPath);
    if ($fileContent === false) {
        echo "Fehler beim Lesen der Datei $pdfcontentPath.";
        return false;
    }
    file_put_contents($filename, $fileContent);
    $uploadresult = callapifileupload($filename, $certname, "description", $host . '/api/v2/Files/Own', $xapikey);
    unlink($filename);

    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        return $resultobj->result->id;
    }
    return false;
}

function uploadjsondata($host, $data, $certname, $xapikey) {
    $jsondata = json_encode($data) ?: $data;
    $filename = tempnam(sys_get_temp_dir(), $certname) . '.json';


    file_put_contents($filename, $jsondata);
    $uploadresult = callapifileupload($filename, $certname, "description", $host . '/api/v2/Files/Own', $xapikey);
    unlink($filename);

    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        return $resultobj->result->id;
    }
    return false;
}

function callapifileupload($filename, $title, $description, $url, $xapikey) {
    $ch = curl_init();
    $headers = [
        'X-API-KEY: ' . $xapikey,
        'Content-Type: multipart/form-data'
    ];
    $fields = [
        'file' => new \CurlFile($filename, mime_content_type($filename), basename($filename)),
        'description' => $description,
        'title' => $title,
        'expiresAt' => (date('Y') + 10) . '-01-01T00:00:00.000Z'
    ];
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_RETURNTRANSFER => true
    ];

    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    } else {
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 200 || $info['http_code'] == 201) {
            //echo "File uploaded successfully";
        } else {
            echo "Upload failed, HTTP status: " . $info['http_code'];
        }
    }

    curl_close($ch);
    return $result;
}



function fetchBadgeDataFromDB($badgeid, $userid) {
    global $DB;

    // Lade die Badge-Informationen aus `mdl_badge`
    $badgeRecord = $DB->get_record('badge', ['id' => $badgeid]);

    if (!$badgeRecord) {
        echo "Kein Badge mit der ID $badgeid in der Datenbank gefunden.";
        return false;
    }
    $contextId = $badgeRecord->contextid; // oder erhalte es über eine andere Methode
    // Lade alle Kriterien, die mit diesem Badge verbunden sind
    $criteriaRecords = $DB->get_records('badge_criteria', ['badgeid' => $badgeid]);

    // Lade die Kriterien, die der Benutzer erfüllt hat
    $criteriaMetRecords = $DB->get_records('badge_criteria_met', ['userid' => $userid]);

    // Lade die Ausstellungsinformationen für dieses Badge und den Benutzer
    $issuedRecord = $DB->get_record('badge_issued', ['badgeid' => $badgeid, 'userid' => $userid]);

    // Organisiere alle relevanten Daten in einem Array
    $badgeData = [
        'id' => $badgeRecord->id,
        'name' => $badgeRecord->name,
        'description' => $badgeRecord->description,
        'timecreated' => $badgeRecord->timecreated,
        'timemodified' => $badgeRecord->timemodified,
        'usercreated' => $badgeRecord->usercreated,
        'usermodified' => $badgeRecord->usermodified,
        'issuername' => $badgeRecord->issuername,
        'criteria' => [],
        'criteria_met' => [],
        'issued' => null
    ];

    // Füge die Kriterieninformationen hinzu
    foreach ($criteriaRecords as $criteria) {
        $badgeData['criteria'][] = [
            'id' => $criteria->id,
            'criteriatype' => $criteria->criteriatype,
            'method' => $criteria->method,
            'description' => $criteria->description,
            'descriptionformat' => $criteria->descriptionformat
        ];
    }

    // Füge die erfüllten Kriterieninformationen hinzu
    foreach ($criteriaMetRecords as $criteriaMet) {
        if (in_array($criteriaMet->critid, array_column($badgeData['criteria'], 'id'))) {
            $badgeData['criteria_met'][] = [
                'id' => $criteriaMet->id,
                'issuedid' => $criteriaMet->issuedid,
                'critid' => $criteriaMet->critid,
                'userid' => $criteriaMet->userid,
                'datemet' => $criteriaMet->datemet
            ];
        }
    }

    // Füge die Ausstellungsinformationen hinzu
    if ($issuedRecord) {
        $badgeData['issued'] = [
            'id' => $issuedRecord->id,
            'badgeid' => $issuedRecord->badgeid,
            'userid' => $issuedRecord->userid,
            'uniquehash' => $issuedRecord->uniquehash,
            'dateissued' => $issuedRecord->dateissued,
            'dateexpire' => $issuedRecord->dateexpire,
            'visible' => $issuedRecord->visible,
            'issuernotified' => $issuedRecord->issuernotified
        ];
    }
    $badgeData['contextid'] = $contextId; // Dies wird hinzugefügt
    return $badgeData;
}



function createBadgePngFromUrl($badgeData, $outputPath) {
    global $CFG;

    // Stelle sicher, dass alle notwendigen Daten vorhanden sind
    if (!isset($badgeData['contextid'])) {
        //echo "Keine Kontext-ID für das Badge vorhanden.";
        return false;
    }

    $badge_context_id = $badgeData['contextid'];
    $badgeid = $badgeData['id'];
    $imageurl = moodle_url::make_pluginfile_url($badge_context_id, 'badges', 'badgeimage', 0, '/', 'f1.png', false);

    // Nutze cURL, um das Bild herunterzuladen, da file_get_contents oft in Server-Konfigurationen deaktiviert ist
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $imageData = curl_exec($ch);
    curl_close($ch);

    if (!$imageData) {
        echo "Fehler beim Herunterladen des Badge-Bildes von $imageurl";
        return false;
    }

    // Erzeuge ein Bild aus den heruntergeladenen Daten
    $image = imagecreatefromstring($imageData);
    if (!$image) {
        echo "Fehler beim Erstellen des Bildes aus den heruntergeladenen Daten.";
        return false;
    }

    // Schriftfarbe festlegen
    $textColor = imagecolorallocate($image, 255, 255, 255); // Weiß

    // Schriftarten festlegen (Beispiel-Schriftart Pfad anpassen)
    $fontPath = '/path/to/your/font.ttf';
    $fontSize = 12;

    // Textinformationen zum Badge hinzufügen
    $text = "Issued by: " . $badgeData['issuername'];
    $xPosition = 10; // Horizontaler Abstand vom linken Rand
    $yPosition = imagesy($image) - 20; // Vertikal 20 Pixel vom unteren Rand

    // Text zum Bild hinzufügen
    imagettftext($image, $fontSize, 0, $xPosition, $yPosition, $textColor, $fontPath, $text);

    // Erstelle die PNG-Datei und speichere sie
    imagepng($image, $outputPath);

    // Ressourcen freigeben
    imagedestroy($image);

    return true;
}


function create_json_badge($data, $name) {
    // Konvertiere den JSON-String in ein Array
    $json_data = json_decode($data, true);

    // Verzeichnis überprüfen und erstellen, falls nicht vorhanden
    $dir_path = __DIR__ . "/files";
    if (!file_exists($dir_path)) {
        mkdir($dir_path, 0777, true);
    }

    // Speicherpfad für die JSON-Datei
    $file_path = $dir_path . '/' . $name . '.json';

    // Schreibe das JSON-Array in eine Datei
    if (file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Fehler beim Schreiben der Datei: " . $file_path);
    }

    return $file_path;
}



function send_attributes($attributes, $walletid, $reason, $url, $xapikey) {
    $requesttype = new stdClass();
    //$requesttype -> {'@type'} = "Request"; 
    $items = array();
    $attribute = new stdClass();
    $attribute->{'@type'} = 'CreateAttributeRequestItem';
    //$attribute->{'@type'} = 'AuthenticationRequestItem';
    $attribute->mustBeAccepted = true;
    $attribute->title = get_string('subject_new_attribute', 'mod_ilddigitalcert');
    //*
    $attribute->attribute = new stdClass();
    $attribute->attribute->{'@type'} = 'RelationshipAttribute';
    $attribute->attribute->owner = get_config('mod_ilddigitalcert', 'dcconnectoraddress');
    $attribute->attribute->validFrom = date('Y-m-d', time());
    $attribute->attribute->validTo = date('Y-m-d', time() + 60 * 60 * 24 * 365 * 10);
    $attribute->attribute->key = "name";
    $attribute->attribute->value = new stdClass();
    $attribute->attribute->value->{'@type'} = 'ProprietaryString';
    $attribute->attribute->value->title = "Study.planning.field_of_interest";
    $attribute->attribute->value->value = "Informatik";
    $attribute->attribute->isTechnical = false;
    $attribute->attribute->confidentiality = 'protected'; // "public" | "protected" | "private"
    //*/
    $items[] = $attribute;


    $request = new stdClass();
    //$request->{'@type'} = 'AttributesChangeRequest';
    $request->content = new stdClass();
    $request->{'@type'} = "Request";

    $request->content->items = $items;
    //$request->reason = $reason;
    //$request->attributes = $dcattributes;
    //$request->applyTo = $walletid;
    $request->peer = $walletid;
    print_object(json_encode($request, JSON_PRETTY_PRINT));
    //$msgresult = callAPI('POST', $url.'/api/v2/Requests/Outgoing/Validate', json_encode($request), $xapikey);
    //print_object(json_encode(json_decode($msgresult), JSON_PRETTY_PRINT));die();

    $response = callAPI('POST', $url . '/api/v2/Requests/Outgoing', json_encode($request), $xapikey);
    print_object(json_encode(json_decode($response), JSON_PRETTY_PRINT));
    die();

    $messagedata = new stdClass();
    $messagedata->recipients = array($walletid);
    $messagedata->content = json_decode($response)->result->content;
    $messagedata = json_encode($messagedata, JSON_PRETTY_PRINT);
    //print_object($messagedata);die();

    $msgresult = callAPI('POST', $url . '/api/v2/Messages', $messagedata, $xapikey);
    return $msgresult;
}
