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
require_once('locallib.php');


require_login();

$result = new stdClass();

if (isguestuser()) {
    $result->status = get_string('not_logged_in', 'tsbadge');
    echo json_encode($result);
    exit;
}

$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
$apiKey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;

// Schritt 1: Account synchronisieren
$syncResponse = syncAccount($host, $apiKey);


// Überprüfe, ob $syncResponse eine gültige Antwort ist und der Statuscode vorhanden ist
if ($syncResponse && isset($syncResponse['status_code'])) {
    if ($syncResponse['status_code'] === 204) {
        // Status "polling" zurückgeben, wenn keine neuen Daten vorhanden sind
        //echo "polling";
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
//print relationshipData;
//echo $relationshipData; 


$relationshipData = json_decode($relationshipData, true);  // JSON in ein Array umwandeln

if (!empty($relationshipData['result'])) {

    foreach ($relationshipData['result'] as $relationship) {
        if (isset($relationship['status']) && $relationship['status'] === 'Pending') {
            //echo "relationship['status']" . $relationship['status']; 
            set_user_preference('mod_tsbadge_relationship_id', $relationship['id'], $USER->id);

            // Beziehungsänderung akzeptieren
            acceptRelationshipChange($host, $apiKey, $relationship['id']);
            $result->status = 'request_accepted';
            //var_dump($result);
        }
    }
} else {
    $result->status = 'polling';
}



// Ergebnis als JSON zurückgeben
echo json_encode($result);
