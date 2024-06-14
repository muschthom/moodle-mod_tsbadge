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

global $USER; 
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

$PAGE->set_url('/mod/tsbadge/view.php', array('id' => $cm->id));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

global $DB, $CFG;
$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
$xapikey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;
$connectoraddress = $DB->get_record('config', ['name' => 'mod_tsbadge_connector_address'])->value;

checkConnectorHealth($host);


echo $OUTPUT->heading(get_string('tsbadgedatasend', 'mod_tsbadge'));

echo ' 
<details>
    <summary>Badge Data Details</summary>
    ' . $instance->tsbadgedata . '
</details>
';

echo "<br/>";
$courseid = $cm->course;

$peerId = createConnectorAttribute($host, $xapikey, $connectoraddress);
$contentData = get_content_data($peerId);


$tsbadgedata = $instance->tsbadgedata;

$walletid = get_user_preferences('mod_tsbadge_wallet_id', 'error', $USER->id);
$relationshipid = get_user_preferences('mod_tsbadge_relationship_id', 'error', $USER->id);
$userId = $USER->id;
if ($walletid != 'error' and $relationshipid != 'error') {
    //$url = new moodle_url('/blocks/walletsend/connect.php?userid=' .  $USER->id . "&badgeid=" . $badgeId);
    //$url = new moodle_url('/block/walletsend/connect.php?badgeid=' . $badgeId);
    $url = new moodle_url('/mod/tsbadge/view.php', array('id' => $cm->id));

    $mform = new \mod_tsbadge\output\form\walletsendconfirm_form($url);
    if ($fromform = $mform->get_data()) {
        $relresult = getRelationship($host, $relationshipid, $xapikey);


        //4 message with trainspot json
        $subject = "Trainspot-Badge erhalten";
        $body = "Hallo! Du hast deinen Trainspt-Badge erfolgreich an deine Wallet übertragen.";
        $cc = [];
        $attachments = [];
        //$pdfcontent = "files/trainspotbadgedata.json";
        //$pdfcontent = $tsbadge_filepath;
        //$pdfcontent = "/files/dummybadge.png";
        $badgedatasend = json_decode($tsbadgedata, true);


        //$fileid = uploadjson($host, $badgedatasend, "dummybadge trainspot", $xapikey);
        $fileid = uploadjsondata($host, $badgedatasend, "testbadge-trainspot", $xapikey);
        //echo "fileid in connect: " . $fileid;
        $attachments[] = $fileid;

        if ($fileid) {
            //sendMessage($host, $xapikey, $walletid, $subject, $body, $cc, $attachments);

            $msgresult = send_rl_attributes($walletid, $tsbadgedata, $host, $xapikey);
            $msgresult = json_decode($msgresult);
            if (isset($msgresult->error)) {
                throw new coding_exception(get_string('msg_send_error', 'mod_ilddigitalcert'));
            }

        } else {
            echo "Fehler beim Hochladen der Datei.\n";
        }

        echo '<p>' . get_string('send_files_to_wallet_success', 'mod_tsbadge') . '</p>';
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
    $templateid = get_user_preferences('mod_tsbadge_template_id', 'error', $USER->id);

    if ($templateid != 'error') { // TODO check if template is not expired!!!
        handleRelationshipProcess($host, $xapikey, $templateid);
        //echo "<script>location.reload();</script>";

    } else {
        //set user preference
        require_once "dummydata.php";
        global $relationshipData;
        $templateid = createRelationshipTemplate($host, $xapikey, $peerId, $relationshipData);

        set_user_preference('mod_tsbadge_template_id', $templateid, $userId);
        echo "<script>location.reload();</script>";
    }
}
echo $OUTPUT->footer();
