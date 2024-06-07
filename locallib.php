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


/**
 * Issues a new digital certificate.
 *
 * @param certificate $certificate
 * @param stdClass $cm Course module.
 * @return string Returns the metadata of the issued certificate as a json string.
 */
/*
function issue_tsbadge($certificate, $cm) {
    global $DB, $CFG, $SITE;

    $recipient = $DB->get_record('user', array('id' => $certificate->get_subjectid()));
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $cm->id));
    $context = context_module::instance($cm->id);

    // Get enrolmentid.
    $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
             WHERE ue.enrolid = e.id
               and e.courseid = :courseid
               and ue.userid = :userid ';
    $params = array('courseid' => $courseid, 'userid' => $recipient->id);

    $enrolmentid = 0;
    if ($enrolment = $DB->get_records_sql($sql, $params)) {
        if (count($enrolment) > 1) {
            throw new moodle_exception(
                'to_many_enrolments',
                'mod_ilddigitalcert',
                new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
            );
        } else {
            foreach ($enrolment as $em) {
                $enrolmentid = $em->id;
            }
        }
    } else {
        throw new moodle_exception(
            'not_enrolled',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
        );
    }
    if ($issued = $DB->get_record(
        'ilddigitalcert_issued',
        array('userid' => $recipient->id, 'cmid' => $cm->id, 'enrolmentid' => $enrolmentid)
    )) {
        return $issued->metadata;
    }

    // Set new db record data.
    $issued = new stdClass();
    $issued->userid = $recipient->id;
    $issued->cmid = $cm->id;
    $issued->courseid = $courseid;
    $issued->name = $certificate->get_title();
    $issued->inblockchain = false;
    $issued->timecreated = time();
    $issued->timemodified = time();
    $issued->metadata = '';
    $issued->enrolmentid = $enrolmentid;

    $issuedid = $DB->insert_record('ilddigitalcert_issued', $issued);
    $issued->id = $issuedid;

    // Update the metadata certificate.
    $certificate->issue($cm, $issued->id, $issued->timemodified);

    $issued->metadata = $certificate->get_ob();
    $issued->edci = $certificate->get_edci();

    // Update record.
    $DB->update_record('ilddigitalcert_issued', $issued);

    // Log certificate_issued event.
    $event = \mod_ilddigitalcert\event\certificate_issued::create(
        array('context' => $context, 'objectid' => $issued->id, 'relateduserid' => $issued->userid)
    );
    $event->trigger();

    // Get ilddigitalcert settings.
    $certsettingssql = "SELECT cert.automation, cert.auto_certifier, cert.auto_pk
                          FROM {course_modules} cm
                          JOIN {ilddigitalcert} cert
                            ON cm.instance = cert.id
                         WHERE cm.id = :cmid;";
    $certsettings = $DB->get_record_sql($certsettingssql, array('cmid' => $cm->id), IGNORE_MISSING);

    // If automation is enabled, issued certificate will be signed and written
    // to the blockchain using the pk of the selected certifier.
    if ($certsettings->automation && $certsettings->auto_certifier && $certsettings->auto_pk) {
        if ($certifier = $DB->get_record('user', array('id' => $certsettings->auto_certifier), '*', IGNORE_MISSING)) {
            if ($pk = \mod_ilddigitalcert\crypto_manager::decrypt($certsettings->auto_pk)) {
                if (to_blockchain($issued, $certifier, $pk)) {
                    return $issued->metadata;
                }
            }
        }
    }

    // Email to user, if it has to be signed and written to the blockchain still.
    $fromuser = core_user::get_support_user();
    $fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
    $fromuser->firstname = $fullname[0];
    $fromuser->lastname = $fullname[1];
    $subject = get_string('subject_new_certificate', 'mod_ilddigitalcert');
    $a = new stdClass();
    $a->fullname = $recipient->firstname . ' ' . $recipient->lastname;
    $a->url = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $cm->id;
    $a->from = $SITE->fullname;
    $messagehtml = get_string('message_new_certificate_html', 'mod_ilddigitalcert', $a);
    $message = html_to_text($messagehtml);
    email_to_user($recipient, $fromuser, $subject, $message, $messagehtml);

    return $issued->metadata;
}

*/


function get_user_badges_data()
{
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



function get_user_certificates_data()
{
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


function checkConnectorHealth($host)
{
    $url = $host . "/health";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($statusCode === 200) {
        $data = json_decode($response, true);
        if ($data['isHealthy']) {
            echo "<br/>.\n";
        } else {
            echo "<br/>Der Connector hat Probleme.\n";
        }
    } else {
        echo "<br/>Fehler bei der Überprüfung der Connector-Gesundheit.\n";
    }
}


function createConnectorAttribute($host, $apiKey, $connectorAddress)
{
    //demo connector adress
    $payload = [
        "content" => [
            "@type" => "IdentityAttribute",
            "owner" => $connectorAddress,
            "value" => [
                "@type" => "DisplayName",
                "value" => "Trainspot2 THL Test Connector example"
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
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTP-Statuscode der Antwort

    if ($response === false) {
        echo '<br/>cURL-Fehler: ' . curl_error($ch);
    }

    curl_close($ch);

    // Ausgabe des Statuscodes und der Antwort
    //echo "<br/>HTTP-Statuscode: $status\n";
    //echo "<br/>Antwort:\n$response\n";

    // JSON-String in ein PHP-Objekt umwandeln
    $responseObj = json_decode($response);

    // Auf die ID zugreifen und in einer Variablen speichern
    $id = $responseObj->result->id;

    // Ausgabe der ID
    //echo "<br/>Die createConnectorAttribute ID ist: $id\n";
    return $id;
}


function validateOutgoingRequest($host, $apiKey, $peerId, $contentData)
{
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

function createRelationshipTemplate($host, $apiKey, $peerId, $contentData)
{
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

    // Ausgabe der ID
    //echo "<br/>Die createRelationshipTemplate ID ist: $id\n";
    return $id;
}


function getRelationship($host, $relationshipId, $xApiKey)
{
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


function getQrCode($host, $apiKey, $templateId)
{
    $url = $host . "/api/v2/RelationshipTemplates/" . $templateId;
    $apiUrl = $url;

    // Initialisiere cURL
    $ch = curl_init();

    // Setze die URL und verschiedene Optionen
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: image/png",
        "x-api-key: $apiKey"
    ]);

    // Führe die GET-Anfrage aus
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Überprüfe auf Fehler
    if (curl_errno($ch)) {
        // Wenn du einen Fehler im Zusammenhang mit cURL hast, solltest du entscheiden, wie damit umgegangen werden soll.
        // Für die Anzeige eines Bildes könnte ein Standard-Fehlerbild oder eine Fehlermeldung in Bildform passend sein.
        curl_close($ch);
        return; // Beende die Funktion, da ein Fehler aufgetreten ist.
    }

    // Schließe die cURL-Session
    curl_close($ch);

    // Überprüfe den HTTP-Statuscode und verarbeite die Antwort entsprechend
    if ($statusCode === 200 && $response !== false) {
        // Kodiere die Binärdaten des Bildes in Base64
        $base64Image = base64_encode($response);

        // Erstelle den <img> Tag mit dem Base64-kodierten Bild
        echo "<br/>";
        echo '<img src="data:image/png;base64,' . $base64Image . '" alt="QR Code">';
    } else {
        // Fehlerbehandlung, wenn der Statuscode nicht 200 ist oder die Antwort fehlerhaft ist
        // Hier könntest du zum Beispiel eine Fehlermeldung anzeigen
        echo "<br/>";
        echo "Fehler beim Abrufen des QR-Codes: HTTP-Statuscode $statusCode";
    }
}



function syncAccount($host, $apiKey)
{
    $url = $host . "/api/v2/Account/Sync";
    $apiUrl = $url;
    // Initialisiere cURL
    $ch = curl_init();

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);

    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Schließe cURL
    curl_close($ch);

    if ($statusCode === 200) {
        // Verarbeitung der erfolgreichen Antwort
        //echo "<br/>Antwort syncAccount():\n$response\n";
        return json_decode($response, true);
    } else {
        // Fehlerbehandlung
        return null;
    }
}

function acceptRelationshipChange($host, $apiKey, $relationshipId, $changeId)
{
    $url = $host . "/api/v2/Relationships/" . $relationshipId . "/Changes/" . $changeId . "/Accept";
    $apiUrl = $url;


    // Initialisiere cURL
    $ch = curl_init();

    // Setze die notwendigen Optionen für den cURL-Request
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "x-api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

    $payload = json_encode(["content" => new stdClass()]); // oder ["content" => []], abhängig von der API-Spezifikation

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    // Führe den cURL-Request aus und speichere die Antwort
    $response = curl_exec($ch);
    //echo "<br/>Antwort acceptRelationshipChange():\n$response\n";
    global $wallet_id;
    // Die ID extrahieren
    $response_data = json_decode($response, true);

    $wallet_id = $response_data['result']['peer'];

    global $USER;
    set_user_preference('block_walletsend_wallet_id', $wallet_id, $USER->id);


    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Schließe cURL
    curl_close($ch);

    if ($statusCode === 200) {
        //echo $statusCode;
        // Verarbeitung der erfolgreichen Antwort
        //echo "Beziehungsänderung erfolgreich akzeptiert.\n";
    } else {
        echo $statusCode;

        // Fehlerbehandlung
        echo "Fehler beim Akzeptieren der Beziehungsänderung: HTTP-Statuscode $statusCode\n";
    }
}



function handleRelationshipProcess($host, $apiKey, $templateId)
{
    // Schritt 1: QR-Code für das RelationshipTemplate abrufen
    getQrCode($host, $apiKey, $templateId);

    // Warte und gib dem Benutzer Zeit, den QR-Code zu scannen und die Beziehung zu initiieren
    // Dies ist eher ein konzeptioneller Schritt. In einer echten Anwendung müsstest du auf ein Benutzereingriff warten oder regelmäßig den Status prüfen.
    echo "<br/>Warte auf die Beziehungsanfrage...";
    echo "<h1>Wenn Code gescannt ist, bitte 2 x Seite neu laden!</h1>";

    // Schritt 2: Account synchronisieren, um nach neuen Beziehungsanfragen zu suchen
    $relationshipData = syncAccount($host, $apiKey);

    // Prüfen, ob es neue Beziehungsanfragen gibt
    if ($relationshipData && !empty($relationshipData['result']['relationships'])) {
        foreach ($relationshipData['result']['relationships'] as $relationship) {
            if ($relationship['status'] === 'Pending') {
                // Gehe durch alle pending Änderungen
                foreach ($relationship['changes'] as $change) {
                    if ($change['status'] === 'Pending' && $change['type'] === 'Creation') {
                        // Schritt 3: Beziehungsänderung akzeptieren
                        echo "<br/>Akzeptiere Beziehungsanfrage...";
                        global $USER;
                        set_user_preference('block_walletsend_relationship_id', $relationship['id'], $USER->id);


                        acceptRelationshipChange($host, $apiKey, $relationship['id'], $change['id']);
                        break; // Annahme, dass nur eine Änderung akzeptiert werden muss

                    }
                }
            }
        }
    } else {
        echo "<br/>Keine neuen Beziehungsanfragen gefunden.";
    }
}



function get_content_data($id)
{
    return [
        "content" => [
            "items" => [
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
                                "owner" => "",
                                "value" => [
                                    "@type" => "DisplayName",
                                    "value" => "Demo Connector of integration example"
                                ]
                            ],
                            "sourceAttributeId" => $id
                        ]
                    ]
                ],
                [
                    "@type" => "RequestItemGroup",
                    "mustBeAccepted" => true,
                    "title" => "Requested Attributes",
                    "items" => [
                        /*
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
                            */
                    ]
                ]
            ]
        ]
    ];
}


function sendMessage($host, $apiKey, $recipientId, $subject, $body, $cc = [], $attachments = [])
{
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

function uploadbadge($host, $pdfcontentPath, $certname, $xapikey)
{
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

function uploadpdf($host, $pdfcontentPath, $certname, $xapikey)
{
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


function uploadjson($host, $pdfcontentPath, $certname, $xapikey)
{
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

function callapifileupload($filename, $title, $description, $url, $xapikey)
{
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
        echo "Keine Kontext-ID für das Badge vorhanden.";
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
