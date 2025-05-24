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
 * Version metadata for the local_recibeexamen plugin.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_recibeexamen', get_string('pluginname', 'local_recibeexamen'));

    $settings->add(new admin_setting_configcheckbox(
        'local_recibeexamen/enable_studentsend',
        get_string('enable_studentsend', 'local_recibeexamen'),
        get_string('enable_studentsend_desc', 'local_recibeexamen'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_recibeexamen/justificante_email',
        get_string('justificante_email', 'local_recibeexamen'),
        get_string('justificante_email_desc', 'local_recibeexamen'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}


