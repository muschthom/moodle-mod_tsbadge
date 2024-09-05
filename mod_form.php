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
 * Activity creation/editing form for the mod_[modname] plugin.
 *
 * @package   mod_[modname]
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/tsbadge/lib.php');

class mod_tsbadge_mod_form extends moodleform_mod
{

    function definition()
    {
        global $CFG, $DB, $OUTPUT;

        $mform = &$this->_form;

        // Section header title according to language file.
        $mform->addElement('header', 'general', get_string('general', 'tsbadge'));

        // Add a text input for the name of the certificate.
        $mform->addElement('text', 'name', get_string('badgename', 'tsbadge'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'tsattributename', get_string('tsattributename', 'tsbadge'), ['size' => '64']);

        // Link to supprt info files 
        $fileurl = $CFG->wwwroot . '/mod/tsbadge/ressources/Badge-Aufschlag.json'; // Pfad zur Beispieldatei im Plugin-Verzeichnis
        $linktext = get_string('tsexamplebadge', 'mod_tsbadge'); // Sprachstring für den Linktext
        $mform->addElement('static', 'examplefilelink', '', '<a href="' . $fileurl . '" target="_blank">' . $linktext . '</a>');

        $fileurl = $CFG->wwwroot . '/mod/tsbadge/ressources/greta_badge_keys.json'; // Pfad zur Beispieldatei im Plugin-Verzeichnis
        $linktext = get_string('tsbadgekeys', 'mod_tsbadge'); // Sprachstring für den Linktext
        $mform->addElement('static', 'examplefilelink', '', '<a href="' . $fileurl . '" target="_blank">' . $linktext . '</a>');


        $mform->addElement(
            'textarea',
            'tsbadgedata',
            get_string('tsbadgedata', 'mod_tsbadge'),
            'wrap="virtual" rows="20" cols="50"'
        );

        // Standard Moodle course module elements (course, category, etc.).
        $this->standard_coursemodule_elements();

        // Standard Moodle form buttons.
        $this->add_action_buttons();
    }

    function validation($data, $files)
    {
        $errors = array();

        // Validate the 'name' field.
        if (empty($data['name'])) {
            $errors['name'] = get_string('errornoname', 'certificate');
        }

        return $errors;
    }

    function data_preprocessing(&$default_values)
    {
        // Set default values for the form fields.
        $default_values['name'] = 'Trainspot Badge';
    }

    function definition_after_data()
    {
        $mform = $this->_form;
        $data = $this->get_data();

        // Disable the 'name' field if 'usecode' is set to 1.
        if ($data && !empty($data->usecode)) {
            $mform->disabledIf('name', 'usecode', 'eq', 1);
        }
    }

    function preprocess_data($data)
    {
        // Modify the 'name' data before saving.
        $data->name = strtoupper($data->name);

        return $data;
    }
}
