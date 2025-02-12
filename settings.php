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
 * Link to CSV user upload
 *
 * @package    block
 * @subpackage walletsend
 * @copyright   2023 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(
        new admin_setting_configtext(
            'mod_tsbadge_domain_url',
            get_string('domainurl', 'tsbadge'),
            get_string('domainurl_desc', 'tsbadge'),
            '',
            PARAM_URL // Verwende PARAM_URL, wenn du sicherstellen möchtest, dass die Eingabe eine gültige URL ist
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'mod_tsbadge_api_key',
            get_string('apikey', 'tsbadge'),
            get_string('apikey_desc', 'tsbadge'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'mod_tsbadge_connector_address',
            get_string('connectoraddress', 'tsbadge'),
            get_string('connectoraddress_desc', 'tsbadge'),
            '',
            PARAM_TEXT
        )
    );
}
