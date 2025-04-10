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
 * Plugin functions for the local_recibeexamen plugin.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

 function local_recibeexamen_mod_quiz_mod_form($formwrapper, MoodleQuickForm $mform) {
     $mform->addElement('advcheckbox', 'sendreceipt', get_string('sendreceipt', 'local_recibeexamen'));
     $mform->addHelpButton('sendreceipt', 'sendreceipt', 'local_recibeexamen');
     $mform->setDefault('sendreceipt', 0);
 }

 function local_recibeexamen_mod_quiz_after_save($formwrapper, stdClass $data, stdClass $course) {
    global $DB;

    // Guardamos en una tabla propia si la casilla está marcada.
    $record = new stdClass();
    $record->quizid = $data->instance;
    $record->sendreceipt = !empty($data->sendreceipt) ? 1 : 0;

    // Actualizamos si ya existe, o insertamos.
    if ($existing = $DB->get_record('local_recibeexamen_quizzes', ['quizid' => $data->instance])) {
        $record->id = $existing->id;
        $DB->update_record('local_recibeexamen_quizzes', $record);
    } else {
        $DB->insert_record('local_recibeexamen_quizzes', $record);
    }
}
