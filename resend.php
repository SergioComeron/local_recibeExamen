<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Lanza la tarea de reenvío de un justificante desde la interfaz de administración.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$id = required_param('id', PARAM_INT);
$url = new moodle_url('/local/recibeexamen/resend.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Reenvío de justificante');
$PAGE->set_heading('Reenvío de justificante');

echo $OUTPUT->header();

if (!$entry = $DB->get_record('local_recibeexamen_queue', ['id' => $id])) {
    throw new moodle_exception('Registro no encontrado');
}

$task = new \local_recibeexamen\task\resend_task();
$task->set_custom_data(['queueid' => $id]);
\core\task\manager::queue_adhoc_task($task);

echo $OUTPUT->notification('La tarea de reenvío del justificante ha sido lanzada correctamente.', 'notifysuccess');
echo $OUTPUT->continue_button(new moodle_url('/local/recibeexamen/listado.php'));

echo $OUTPUT->footer();
