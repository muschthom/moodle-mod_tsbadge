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
 * Script that is polled to check for relationship requests
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('dcconnectorlib.php');
require_once('locallib.php');

 
require_login();

$result = new stdClass();

if (isguestuser()) {
    $result->status = get_string('not_logged_in', 'mod_ilddigitalcert');
    echo json_encode($result);
    exit;
}
$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
$apiKey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;
/*
callAPI('POST', $host.'/api/v2/Account/Sync', false, $xapikey);

$apiresult = callAPI('GET', $host.'/api/v2/Relationships', false, $xapikey);

$apiresult = json_decode($apiresult);
$templateid = get_user_preferences('mod_tsbadge_template_id', 'error', $USER->id);
foreach ($apiresult->result as $ar) {
    if ($templateid == $ar->template->id) {
        if (count($ar->changes) == 1) {
            
            if (checkrequest($ar)) {
                // Accept request.
                $data = '{"content": {}}';
                $acceptresult = callAPI(
                    'PUT',
                    $host.'/api/v2/Relationships/'.$ar->id.'/Changes/'.$ar->changes[0]->id.'/Accept',
                    $data,
                    $xapikey
                );
                $accept = json_decode($acceptresult);
                if (isset($accept->result->id)) {
                    set_user_preference('mod_tsbadge_relationship_id', $accept->result->id, $USER->id);
                    unset_user_preference('mod_tsbadge_template_id', $USER->id);
                    set_user_preference('mod_tsbadge_wallet_id', $accept->result->peer, $USER->id);
                    $result->status = 'request_accepted';
                }
            } else {
                $result->status = 'bad_request';
            }
        } else {
            $result->status = 'bad_request';
        }
        exit(json_encode($result));
    }
}
    

//nach bestätigung des neuen kontakts in app
// Schritt 2: Account synchronisieren, um nach neuen Beziehungsanfragen zu suchen
$relationshipData = syncAccount($host, $apiKey);
//echo "relationshipdata = " . "<br/>";
//var_dump($relationshipData);
//echo "<br/>";
/*
// Prüfen, ob es neue Beziehungsanfragen gibt
//if ($relationshipData && !empty($relationshipData['result']['relationships'])) {
if (!empty($relationshipData['result']['relationships'])) {
    //echo "<script>location.reload();</script>";

    foreach ($relationshipData['result']['relationships'] as $relationship) {
        if ($relationship['status'] === 'Pending') {
            // Gehe durch alle pending Änderungen
            foreach ($relationship['changes'] as $change) {
                if ($change['status'] === 'Pending' && $change['type'] === 'Creation') {
                    // Schritt 3: Beziehungsänderung akzeptieren
                    //echo "<br/>Akzeptiere Beziehungsanfrage...";
                    set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);
                    unset_user_preference('mod_tsbadge_template_id', $USER->id);


                    acceptRelationshipChange($host, $apiKey, $relationship['id'], $change['id']);
                    break; // Annahme, dass nur eine Änderung akzeptiert werden muss

                }
            }
            $result->status = 'request_accepted';

            //echo "<script>location.reload();</script>";
        } else {
            $result->status = 'polling';

            //echo "<br/>Keine neuen Beziehungsanfragen gefunden.";
        }
    }
    echo json_encode($result);
}
*/

require_once(__DIR__ . '/../../config.php');
require_once('dcconnectorlib.php');
require_once('locallib.php');

require_login();

$result = new stdClass();

if (isguestuser()) {
    $result->status = get_string('not_logged_in', 'mod_ilddigitalcert');
    echo json_encode($result);
    exit;
}

$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
$apiKey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;

// Schritt 1: Account synchronisieren
// Schritt 1: Account synchronisieren
$syncResponse = syncAccount($host, $apiKey);


// Überprüfe, ob $syncResponse eine gültige Antwort ist und der Statuscode vorhanden ist
if ($syncResponse && isset($syncResponse['status_code'])) {
    echo "statuscode: " . $syncResponse['status_code'];
    if ($syncResponse['status_code'] === 204) {
        // Status "polling" zurückgeben, wenn keine neuen Daten vorhanden sind
        echo "polling"; 
        $result->status = 'polling';
    } else {
        // Fehlerbehandlung für andere Statuscodes oder Antworten
        $result->status = 'error';
        $result->message = 'Fehler beim Synchronisieren des Accounts: Unerwarteter Statuscode ' . $syncResponse['status_code'];
        echo json_encode($result);
        exit;
    }
} else {
    // Fehlerbehandlung, wenn $syncResponse null ist oder keinen Statuscode enthält
    $result->status = 'error';
    $result->message = 'Fehler beim Synchronisieren des Accounts: Keine gültige Antwort von der API erhalten.';
    echo json_encode($result);
    exit;
}

// Schritt 2: Beziehungsdaten abrufen
$relationshipData = callAPI('GET', $host . '/api/v2/Relationships', false, $apiKey);
$relationshipData = json_decode($relationshipData, true);  // JSON in ein Array umwandeln

// Prüfen, ob es neue Beziehungen gibt
//if (!empty($relationshipData['result']['relationships'])) {
/*
    foreach ($relationshipData['result']['relationships'] as $relationship) {
        if ($relationship['newStatus'] === 'Pending') {
            // Beziehungs-ID speichern
            echo "<br/>set_user_preference <br/>"; 
            set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);

            // Beziehungsänderung akzeptieren
            acceptRelationshipChange($host, $apiKey, $relationship['id']);
            $result->status = 'request_accepted';
        } else {
            $result->status = 'polling';
        }
    }
        */
if (!empty($relationshipData['result'])) {
    echo "relationshipData456 <br/><br/>"; 
    var_dump($relationshipData); 
    echo "<br/><br/>"; 

    foreach ($relationshipData['result'] as $relationship) {
        if (isset($relationship['status']) && $relationship['status'] === 'Pending') {
            echo "<br/>set_user_preference <br/>";
            set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);

            // Beziehungsänderung akzeptieren
            acceptRelationshipChange($host, $apiKey, $relationship['id']);
            $result->status = 'request_accepted';    
            var_dump( $result); 
        }
    }
} else {
    $result->status = 'polling';
}



// Ergebnis als JSON zurückgeben
echo "Ergebnis als JSON zurückgeben 123: <br/><br/>"; 

echo json_encode($result);
