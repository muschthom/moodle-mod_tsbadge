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
global $DB, $CFG, $USER;


$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'tsbadge');
$instance = $DB->get_record('tsbadge', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

$PAGE->set_url('/mod/tsbadge/view.php', array('id' => $cm->id));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
$xapikey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;
$connectoraddress = $DB->get_record('config', ['name' => 'mod_tsbadge_connector_address'])->value;
$courseid = $cm->course;

$connectorhealth = checkConnectorHealth($host);


if(!$connectorhealth){
    echo '<p>' . html_writer::link(
        new moodle_url('/course/view.php?id=' . $courseid),
        get_string('previous')
    ) . '</p>';
    echo $OUTPUT->footer();

    die(); 
}

echo $OUTPUT->heading(get_string('tsbadgedatasend', 'mod_tsbadge'));


echo ' 
<details>
    <summary>Badge Data Details</summary>
    ' . $instance->tsbadgedata . '
</details>
';


// AttributeId des Attributes "DisplayName" wird übergeben
$attributeId = createConnectorAttribute($host, $xapikey, $connectoraddress);
 
//hole connector-attribute daten
$contentData = get_content_data($attributeId);

//hole json-daten des badges
$tsbadgedata = $instance->tsbadgedata;
$tsattributeTitle = $instance->tsattributename;

//hole walletid, wenn vorhanden
$walletid = get_user_preferences('mod_tsbadge_wallet_id', 'error', $USER->id);
if ($walletid !== 'error') {
    //echo "Wallet ID vorhanden.<br/>";
} else {
    //echo "Wallet ID nicht gefunden.<br/>";
}


//hole relationship id, wenn vorhanden
$relationshipid = get_user_preferences('mod_tsbadge_relationship_id', 'error', $USER->id);
if ($relationshipid !== 'error') {
    //echo "relationshipid vorhanden.<br/>";
} else {
    //echo "Relationship-Id nicht gefunden.<br/>";
}



if ($walletid != 'error' and $relationshipid != 'error') {
    //echo "Wallet-ID vorhanden, RelationshipID vorhanden"; 
    //$url = new moodle_url('/block/walletsend/connect.php?badgeid=' . $badgeId);
    $url = new moodle_url('/mod/tsbadge/view.php', array('id' => $cm->id));

    $mform = new \mod_tsbadge\output\form\walletsendconfirm_form($url);
    if ($fromform = $mform->get_data()) {
        
        $relresult = getRelationship($host, $relationshipid, $xapikey);
        //echo "relresult = "; 
        //var_dump($relresult); 
        //send badge data as relationship attribute
        //$facetteTitle = "Trainspot Testbadge Facette: Methoden, Medien und Lernmaterialien, Level 2"; 
        $badgedatasend = json_decode($tsbadgedata, true);
        
        $msgresult = send_rl_attributes($walletid, $connectoraddress, $tsbadgedata, $tsattributeTitle, $host, $xapikey);
        //echo "msresult = " . $msgresult; 
        //$msgresult = json_decode($msgresult);
        if (isset($msgresult->error)) {
            throw new coding_exception(get_string('msg_send_error', 'mod_ilddigitalcert'));
        }

        echo "<br/>"; 
        echo '<p><b>' . get_string('send_files_to_wallet_success', 'mod_tsbadge') . '</b></p>';
        echo '<p>' . html_writer::link(
            new moodle_url('/course/view.php?id=' . $courseid),
            get_string('previous')
        ) . '</p>';
        
    } else if ($mform->is_cancelled()) {
        redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
    } else {
        // Anzeigen des Formulars
        $mform->display();
    }
} else {
    //keine walletid oder keine relationship-id

    //hole relationship-template-id, notwendig, um relationship herzustellen
    //wenn relationship-template-id vorhanden, dann status pending..
    $templateid = get_user_preferences('mod_tsbadge_template_id', 'error', $USER->id);

    if ($templateid != 'error') { // TODO check if template is not expired!!!
        //echo "templateid vorhanden"; 
        handleRelationshipProcess($host, $xapikey, $templateid);
        //echo "<script>location.reload();</script>"; 
    } else {
        //echo "nichts vorhanden, TemplateID wird erstellt"; 
        //set user preference
        //require_once "dummydata.php";
        $validatedItems = get_validatedItems($connectoraddress, $attributeId);
        $relationshipData = get_relationshipData($validatedItems);

        //erstelle relationship template id
        $templateid = createRelationshipTemplate($host, $xapikey, $relationshipData);

        //schreibe relationship template id in db
        set_user_preference('mod_tsbadge_template_id', $templateid, $USER->id);
        echo "safe mod_tsbadge_template_id in db<br/>"; 
        
        //seite neu laden, um nächsten prozessschritt zu starten
        echo "<script>location.reload();</script>";
    }
}

//delete wallet-connection
echo '<p>' . html_writer::link(
    new moodle_url('/mod/tsbadge/delete_connection.php'),
    get_string('delete_connection', 'mod_tsbadge')
) . '</p>';

echo $OUTPUT->footer();
