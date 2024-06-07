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
 * Library of interface functions and constants.
 *
 * @package     mod_tsbadge
 * @copyright   2024 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function tsbadge_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}


function tsbadge_add_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('tsbadge', $moduleinstance);

    return $id;
}


function tsbadge_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('tsbadge', $moduleinstance);
}

function tsbadge_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('tsbadge', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('tsbadge', array('id' => $id));

    return true;
}


function tsbadge_cm_info_dynamic(cm_info $cm) {
    global $USER, $CFG, $DB;
    // Try to get $cm->get_user_visible() wich might throw errors in moodle versions prior to 3.9.10.
    // In case of errors default $uservisible to true.

    try {
        $uservisible = $cm->get_user_visible();
    } catch (\Exception $e) {
        $uservisible = true;
    }

    // User can access the activity.
    if ($uservisible) {
        if ($cm->available && !empty($cm->availability)) {
            $courseid = $cm->get_course()->id;
            $coursecontext = context_course::instance($courseid);
            if (!is_enrolled($coursecontext, $USER)) {
                return;
            }
            // Get enrolmentid.
            $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
                    WHERE ue.enrolid = e.id
                    and e.courseid = :courseid
                    and ue.userid = :userid ';
            $params = array('courseid' => $courseid, 'userid' => $USER->id);
            if ($enrolment = $DB->get_records_sql($sql, $params)) {
                // Certificate will not be issued, if user is enrolled with more than 1 enrolments.
                if (count($enrolment) > 1) {
                    \core\notification::error(get_string('to_many_enrolments', 'mod_ilddigitalcert'));
                    return;
                }
            }
            // Issue certificate.
            require_once($CFG->dirroot . '/mod/tsbadge/locallib.php');
            //TODO: Badge vergeben
            //$metacertificate = \mod_ilddigitalcert\bcert\certificate::new($cm, $USER);
            issue_tsbadge($metacertificate, $cm);
        }
    }
}
