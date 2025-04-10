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

namespace local_recibeexamen;

defined('MOODLE_INTERNAL') || die();

/**
 * Class observer
 *
 * @package    local_recibeexamen
 * @copyright  2025 YOUR NAME
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

        $attemptid = $event->objectid;
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);

        $quizid = $attempt->quiz;
        $userid = $attempt->userid;

        // Consultamos si este cuestionario está marcado para enviar justificante
        $config = $DB->get_record('local_recibeexamen_quizzes', ['quizid' => $quizid]);

        if ($config && $config->sendreceipt) {
            // Llamamos al helper con namespace completo
            \local_recibeexamen\justificante_helper::generar_justificante_quiz($userid, $quizid, $attempt);
        }
    }
}
