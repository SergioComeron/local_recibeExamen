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
 * TODO describe file listado
 *
 * @package    local_recibeexamen
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('recibeexamen_queue_table.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$url = new moodle_url('/local/recibeexamen/listado.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('list', 'local_recibeexamen'));
$PAGE->set_heading(get_string('list', 'local_recibeexamen'));

echo $OUTPUT->header();

// Crear instancia de la tabla.
$table = new mod_recibeexamen_queue_table('recibeexamen_queue_table');
$table->define_baseurl($PAGE->url);
$table->setup(); // <-- ¡Primero hay que llamar a setup!

global $DB;

// Total de registros.
$total = $DB->count_records('local_recibeexamen_queue');

// Parámetros de paginación.
$page = optional_param('page', 0, PARAM_INT);

// Comprobar ordenación.
$sort = $table->get_sql_sort();
$sqlorder = $sort ? "ORDER BY $sort" : "ORDER BY id DESC";

// Obtener los registros.
$sql = "SELECT id, userid, status, filename, data, timecreated
        FROM {local_recibeexamen_queue}
        $sqlorder";

$records = $DB->get_records_sql($sql, null, $page * 10, 10);

// Configurar paginación y mostrar tabla.
$table->pagesize(10, $total);
// table->setup() ya se ha llamado arriba, así que no lo repitas aquí.

foreach ($records as $record) {
    $data = json_decode($record->data, true);

    $userlink = '-';
    if ($record->userid) {
        $userurl = new moodle_url('/user/view.php', ['id' => $record->userid]);
        $userlink = html_writer::link($userurl, $data['idusuldap'] ?? '(sin nombre)');
    }

    $resendurl = new moodle_url('/local/recibeexamen/resend.php', ['id' => $record->id]);
    $acciones = html_writer::link($resendurl, '🔁 Reenviar', ['class' => 'btn btn-secondary']);

    $table->add_data([
        $record->id,
        $userlink,
        $data['exacodnum'] ?? '-',
        $data['assnomid1'] ?? '-',
        $data['planomid1'] ?? '-',
        $record->status ?? '-',
        $record->filename ?? '-',
        $data['fechainicio'] ?? '-',
        $data['fechafin'] ?? '-',
        userdate($record->timecreated) ?? '-',
        $acciones,
    ]);
}

$table->finish_output();

echo $OUTPUT->footer();
