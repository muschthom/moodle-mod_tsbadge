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

/*
$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'tsbadge');
$instance = $DB->get_record('tsbadge', ['id' => $cm->instance], '*', MUST_EXIST);
*/
require_login($course, true, $cm);
//$modulecontext = context_module::instance($cm->id);

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

$PAGE->set_url('/mod/tsbadge/delete_connection.php');
$PAGE->set_heading(format_string($course->fullname));
//$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

function delete_user_preference($preference) {
    global $DB, $USER;
    
    $conditions = array('userid' => $USER->id, 'name' => $preference);

    if ($DB->record_exists('user_preferences', $conditions)) {
        $DB->delete_records('user_preferences', $conditions);
        echo "User preference deleted successfully.";
    } else {
        echo "No matching user preference found.";
    }
}
delete_user_preference("mod_tsbadge_template_id"); 
delete_user_preference("mod_tsbadge_relationship_id"); 
delete_user_preference("mod_tsbadge_wallet_id"); 


echo "<h1>Alle Wallet-Verbindungsdaten gelöscht</h1>"; 
echo '<p>' . html_writer::link(
    new moodle_url('/my/courses.php'),
    get_string('previous')
) . '</p>';

echo $OUTPUT->footer();