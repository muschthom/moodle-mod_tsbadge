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
 * Activity view page for the plugintype_pluginname plugin.
 *
 * @package   plugintype_pluginname
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require "locallib.php";

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'tsbadge');
$instance = $DB->get_record('tsbadge', ['id' => $cm->instance], '*', MUST_EXIST);


require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

$PAGE->set_url('/mod/tsbadge/view.php', array('id' => $cm->id));
//$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

global $DB, $CFG;
//$host = get_config('block_walletsend', 'block_walletsend_domain_url');
$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
//$xapikey = get_config('block_walletsend', 'block_walletsend_api_key');
$xapikey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;
//$connectoraddress = get_config('block_walletsend', 'block_walletsend_connector_address');
$connectoraddress = $DB->get_record('config', ['name' => 'mod_tsbadge_connector_address'])->value;

checkConnectorHealth($host);


echo $OUTPUT->heading(get_string('tsbadgedata', 'mod_tsbadge'));

echo $instance->tsbadgedata;
echo "<br/>";
$courseid = $cm->course;
echo "<br/>";

//echo $courseid;



$peerId = createConnectorAttribute($host, $xapikey, $connectoraddress);

$contentData = get_content_data($peerId);
//echo "<br/><br/>contentData var_dump: ";
//var_dump($contentData);
//echo "<br/><br/> contentData['content']";
//var_dump($contentData['content']);
validateOutgoingRequest($host, $xapikey, $peerId, $contentData);


//create badge from data

$id = 1;
$record = $DB->get_record('tsbadge', array('id' => $id), 'name, tsbadgedata');

// Überprüfen, ob ein Datensatz gefunden wurde
if ($record) {
    // Werte in Variablen speichern
    $tsbadgename = $record->name;
    $tsbadgename = str_replace(' ', '', $tsbadgename);
    $tsbadgedata = $record->tsbadgedata;
    $tsbadge_filepath = create_json_badge($tsbadgedata, $tsbadgename);
} else {
    // Fehlerbehandlung, falls kein Datensatz gefunden wurde
    echo 'Kein Datensatz gefunden mit ID = ' . $id;
}





$walletid = get_user_preferences('block_walletsend_wallet_id', 'error', $USER->id);
$relationshipid = get_user_preferences('block_walletsend_relationship_id', 'error', $USER->id);
$userId = $USER->id;
if ($walletid != 'error' and $relationshipid != 'error') {
    //$url = new moodle_url('/blocks/walletsend/connect.php?userid=' .  $USER->id . "&badgeid=" . $badgeId);
    //$url = new moodle_url('/block/walletsend/connect.php?badgeid=' . $badgeId);
    $url = new moodle_url('/mod/tsbadge/view.php', array('id' => $cm->id));

    $mform = new \mod_tsbadge\output\form\walletsendconfirm_form($url);
    if ($fromform = $mform->get_data()) {
        $relresult = getRelationship($host, $relationshipid, $xapikey);
        //$relresult = json_decode($relresult);
        //var_dump($relresult);

        /*
        if (
            isset($relresult->result->peer) and
            $relresult->result->peer == $walletid
        ) {
*/
        //if (isset($relresult->result->peer)) {

/*
        //erste Nachricht
        //echo "<br/><br/>wallet_id: " . $walletid . "<br/><br/>";
        $subject = "Willkommen";
        $body = "Hallo. erste Nachricht.";
        $cc = []; //keine CC
        $attachments = []; // Keine Anhänge
        sendMessage($host, $xapikey, $walletid, $subject, $body, $cc, $attachments);


        //2. message with badge attached
        $subject = "Badgeupload";
        $body = "Hallo. Zweite Nachricht mit Badge-Anhang.";
        $cc = [];


        $attachments = [];
        $badgecontent = "files\dummybadge.png";
        //$badgecontent =fetchBadgeDataFromDB($badgeid, $userid); 
        $fileid = uploadbadge($host, $badgecontent, "dummybadge", $xapikey);
        //echo "fileid in connect: " . $fileid;
        $attachments[] = $fileid;

        if ($fileid) {
            sendMessage($host, $xapikey, $walletid, $subject, $body, $cc, $attachments);
        } else {
            echo "Fehler beim Hochladen der Datei.\n";
        }

        /*

        $attachments = [];

        // Zuerst holen wir die Badge-Daten
        $badgecontent = fetchBadgeDataFromDB($badgeId, $userId);

        // Prüfen, ob Badge-Daten erfolgreich abgerufen wurden
        if (!$badgecontent) {
            echo "Fehler beim Abrufen der Badge-Daten.";
            return;
        }

        // Generiere einen einzigartigen Dateinamen basierend auf UUID
        $newBadgeName = 'badge_' . uniqid() . '.png';

        // Pfad, wo der Badge gespeichert wird
        $outputDir = '/files/';
        $outputPath = $outputDir . $newBadgeName;

        // Sicherstellen, dass das Verzeichnis existiert
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Erstellen des Badge-Bildes und Speichern auf dem Server
        $createBadgeSuccess = createBadgePngFromUrl($badgecontent, $outputPath);
        if (!$createBadgeSuccess) {
            echo "Fehler beim Erstellen des Badge-Bildes.";
            return;
        }

        // Hochladen der Badge-Datei und Speichern der File-ID
        $fileid = uploadbadge($host, $outputPath, $newBadgeName, $xapikey);
        if ($fileid) {
            //echo "fileid in connect: " . $fileid;
            $attachments[] = $fileid;
        } else {
            echo "Fehler beim Hochladen der Badge-Datei.";
        }

        //3 message with pdf
        $subject = "PDF-Upload";
        $body = "Hallo. Dritte Nachricht mit PDF-Anhang.";
        $cc = [];
        $attachments = [];
        $pdfcontent = "files\dummycertificate.pdf";
        //$pdfcontent = "/files/dummybadge.png";
        $fileid = uploadpdf($host, $pdfcontent, "dummycertificate", $xapikey);
        //echo "fileid in connect: " . $fileid;
        $attachments[] = $fileid;

        if ($fileid) {
            sendMessage($host, $xapikey, $walletid, $subject, $body, $cc, $attachments);
        } else {
            echo "Fehler beim Hochladen der Datei.\n";
        }
*/

        //4 message with trainspot json
        $subject = "json-Upload";
        $body = "Hallo. Vierte Nachricht mit json-Anhang.";
        $cc = [];
        $attachments = [];
        //$pdfcontent = "files/trainspotbadgedata.json";
        $pdfcontent = $tsbadge_filepath;
        //$pdfcontent = "/files/dummybadge.png";
        $fileid = uploadjson($host, $pdfcontent, "dummybadge trainspot", $xapikey);
        //echo "fileid in connect: " . $fileid;
        $attachments[] = $fileid;

        if ($fileid) {
            sendMessage($host, $xapikey, $walletid, $subject, $body, $cc, $attachments);
        } else {
            echo "Fehler beim Hochladen der Datei.\n";
        }

        echo '<p>' . get_string('send_files_to_wallet_success', 'block_walletsend') . '</p>';
        echo '<p>' . html_writer::link(
            new moodle_url('/course/view.php?id=' . $courseid),
            get_string('previous')
        ) . '</p>';
    } else if ($mform->is_cancelled()) {
        // Verarbeitung der Daten
    } else {
        // Anzeigen des Formulars
        $mform->display();
    }
} else {
    $templateid = get_user_preferences('block_walletsend_template_id', 'error', $USER->id);

    if ($templateid != 'error') { // TODO check if template is not expired!!!
        handleRelationshipProcess($host, $xapikey, $templateid);
    } else {
        //set user preference
        require_once "dummydata.php";
        global $relationshipData;
        $templateid = createRelationshipTemplate($host, $xapikey, $peerId, $relationshipData);

        set_user_preference('block_walletsend_template_id', $templateid, $userId);
    }
}
echo $OUTPUT->footer();
