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
 * @copyright  2025 Sergio Comerón <sergio.comeron@udima.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('recibeexamen_queue_table.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
require_capability('local/recibeexamen:viewqueue', context_system::instance());

// Obtener parámetro de búsqueda.
$searchuser = optional_param('searchuser', '', PARAM_TEXT);
// Obtener parámetro de búsqueda por código de examen.
$searchexam = optional_param('searchexam', '', PARAM_TEXT);

$url = new moodle_url('/local/recibeexamen/listado.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('list', 'local_recibeexamen'));
$PAGE->set_heading(get_string('list', 'local_recibeexamen'));

echo $OUTPUT->header();

// Agregar sección de estadísticas
echo $OUTPUT->heading(get_string('statistics', 'local_recibeexamen'), 3);

// Obtener estadísticas generales
$stats = [];
$stats['total'] = $DB->count_records('local_recibeexamen_queue');
$stats['pending'] = $DB->count_records('local_recibeexamen_queue', ['status' => '']); // O el valor por defecto que uses
$stats['processed'] = $DB->count_records('local_recibeexamen_queue', ['status' => 'done']);
$stats['error'] = $DB->count_records('local_recibeexamen_queue', ['status' => 'failed']);

// Estadísticas por fecha (últimos 7 días)
$weekago = time() - (7 * 24 * 60 * 60);
$stats['last_week'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ?', [$weekago]);

// Estadísticas por día de hoy
$today_start = strtotime('today');
$today_end = strtotime('tomorrow') - 1;
$stats['today'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND timecreated <= ?', [$today_start, $today_end]);
$stats['today_done'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND timecreated <= ? AND status = ?', [$today_start, $today_end, 'done']);
$stats['today_failed'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND timecreated <= ? AND status = ?', [$today_start, $today_end, 'failed']);
$stats['today_pending'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND timecreated <= ? AND (status = ? OR status IS NULL)', [$today_start, $today_end, '']);

// Estadísticas de la semana pasada también con desglose
$weekago = time() - (7 * 24 * 60 * 60);
$stats['last_week'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ?', [$weekago]);
$stats['last_week_done'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND status = ?', [$weekago, 'done']);
$stats['last_week_failed'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND status = ?', [$weekago, 'failed']);
$stats['last_week_pending'] = $DB->count_records_select('local_recibeexamen_queue', 'timecreated >= ? AND (status = ? OR status IS NULL)', [$weekago, '']);

// Obtener los exámenes más frecuentes
$frequent_exams_sql = "
    SELECT data::jsonb ->> 'exacodnum' as exam_code, COUNT(*) as count
    FROM {local_recibeexamen_queue} 
    WHERE data::jsonb ->> 'exacodnum' IS NOT NULL 
    GROUP BY data::jsonb ->> 'exacodnum' 
    ORDER BY count DESC 
    LIMIT 5
";
$frequent_exams = $DB->get_records_sql($frequent_exams_sql);

// Mostrar estadísticas en cards
echo '<div class="row mb-3">';

// Card 1: Estadísticas generales
echo '<div class="col-md-6 col-lg-3 mb-3">';
echo '<div class="card border-primary">';
echo '<div class="card-header bg-primary text-white"><strong>' . get_string('general_stats', 'local_recibeexamen') . '</strong></div>';
echo '<div class="card-body">';
echo '<p><strong>' . get_string('total_exams', 'local_recibeexamen') . ':</strong> ' . $stats['total'] . '</p>';
echo '<p><strong>' . get_string('pending_exams', 'local_recibeexamen') . ':</strong> <span class="badge badge-warning">' . $stats['pending'] . '</span></p>';
echo '<p><strong>' . get_string('processed_exams', 'local_recibeexamen') . ':</strong> <span class="badge badge-success">' . $stats['processed'] . '</span></p>';
echo '<p><strong>' . get_string('error_exams', 'local_recibeexamen') . ':</strong> <span class="badge badge-danger">' . $stats['error'] . '</span></p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Card 2: Estadísticas temporales - ACTUALIZADO
echo '<div class="col-md-6 col-lg-3 mb-3">';
echo '<div class="card border-info">';
echo '<div class="card-header bg-info text-white"><strong>' . get_string('time_stats', 'local_recibeexamen') . '</strong></div>';
echo '<div class="card-body">';
echo '<p><strong>' . get_string('today_exams', 'local_recibeexamen') . ':</strong> ' . $stats['today'] . '</p>';
echo '<div style="margin-left: 15px; font-size: 0.9em;">';
echo '<span class="badge badge-success">' . $stats['today_done'] . ' ' . get_string('completed', 'local_recibeexamen') . '</span> ';
echo '<span class="badge badge-warning">' . $stats['today_pending'] . ' ' . get_string('pending', 'local_recibeexamen') . '</span> ';
echo '<span class="badge badge-danger">' . $stats['today_failed'] . ' ' . get_string('failed', 'local_recibeexamen') . '</span>';
echo '</div>';
echo '<hr style="margin: 10px 0;">';
echo '<p><strong>' . get_string('last_week_exams', 'local_recibeexamen') . ':</strong> ' . $stats['last_week'] . '</p>';
echo '<div style="margin-left: 15px; font-size: 0.9em;">';
echo '<span class="badge badge-success">' . $stats['last_week_done'] . ' ' . get_string('completed', 'local_recibeexamen') . '</span> ';
echo '<span class="badge badge-warning">' . $stats['last_week_pending'] . ' ' . get_string('pending', 'local_recibeexamen') . '</span> ';
echo '<span class="badge badge-danger">' . $stats['last_week_failed'] . ' ' . get_string('failed', 'local_recibeexamen') . '</span>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Card 3: Exámenes más frecuentes
echo '<div class="col-md-12 col-lg-6 mb-3">';
echo '<div class="card border-success">';
echo '<div class="card-header bg-success text-white"><strong>' . get_string('frequent_exams', 'local_recibeexamen') . '</strong></div>';
echo '<div class="card-body">';
if ($frequent_exams) {
    echo '<ul class="list-unstyled">';
    foreach ($frequent_exams as $exam) {
        if (!empty($exam->exam_code)) {
            echo '<li><strong>' . s($exam->exam_code) . ':</strong> ' . $exam->count . ' ' . get_string('times', 'local_recibeexamen') . '</li>';
        }
    }
    echo '</ul>';
} else {
    echo '<p>' . get_string('no_data', 'local_recibeexamen') . '</p>';
}
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // Cierre del row

// Separador
echo '<hr>';

// Formulario de búsqueda
$mform = new MoodleQuickForm('searchform', 'get', $PAGE->url);
$mform->addElement('text', 'searchuser', get_string('searchuser', 'local_recibeexamen'));
$mform->setType('searchuser', PARAM_TEXT);
$mform->setDefault('searchuser', $searchuser);

$mform->addElement('text', 'searchexam', get_string('searchexam', 'local_recibeexamen'));
$mform->setType('searchexam', PARAM_TEXT);
$mform->setDefault('searchexam', $searchexam);

$mform->addElement('submit', 'submitbutton', get_string('search', 'local_recibeexamen'));
$mform->display();

// Crear instancia de la tabla.
$table = new mod_recibeexamen_queue_table('recibeexamen_queue_table');
$table->define_baseurl($PAGE->url);
$table->setup(); // <-- ¡Primero hay que llamar a setup!

global $DB;

// Inicializar parámetros
$where = [];
$params = [];
if (!empty($searchuser)) {
    $where[] = "data::jsonb ->> 'idusuldap' ILIKE :searchuser";
    $params['searchuser'] = '%' . $searchuser . '%';
}
if (!empty($searchexam)) {
    $where[] = "data::jsonb ->> 'exacodnum' ILIKE :searchexam";
    $params['searchexam'] = '%' . $searchexam . '%';
}
$whereclause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
if (!empty($whereclause)) {
    $total = $DB->count_records_sql("SELECT COUNT(*) FROM {local_recibeexamen_queue} $whereclause", $params);
} else {
    $total = $DB->count_records('local_recibeexamen_queue');
}

// Parámetros de paginación.
$page = optional_param('page', 0, PARAM_INT);

// Comprobar ordenación.
$sort = $table->get_sql_sort();
$sqlorder = $sort ? "ORDER BY $sort" : "ORDER BY id DESC";

// Reutilizar $whereclause y $params para la consulta
$sql = "SELECT id, userid, status, filename, data, timecreated
        FROM {local_recibeexamen_queue}
        $whereclause
        $sqlorder";

$records = $DB->get_records_sql($sql, $params, $page * 10, 10);

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

    // Verificar capacidad para reenviar justificantes.
    if (has_capability('local/recibeexamen:resendjustificantes', context_system::instance())) {
        $resendurl = new moodle_url('/local/recibeexamen/resend.php', ['id' => $record->id]);
        $acciones = html_writer::link($resendurl, 'Enviar', ['class' => 'btn btn-secondary btn-sm']);
    } else {
        $acciones = html_writer::tag('button', 'Enviar', [
            'class' => 'btn btn-secondary btn-sm',
            'disabled' => 'disabled',
            'title' => get_string('nopermissions', 'error')
        ]);
    }

    // Botón para mostrar datos JSON
    $data_formatted = json_encode($data, JSON_PRETTY_PRINT);
    $data_button = '<button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#dataModal' . $record->id . '">
        <i class="fa fa-eye"></i> Ver datos
    </button>';
    
    // Modal para mostrar los datos
    $modal = '
    <div class="modal fade" id="dataModal' . $record->id . '" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel' . $record->id . '">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataModalLabel' . $record->id . '">Datos JSON - ID: ' . $record->id . '</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <pre style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">' . 
                    htmlspecialchars($data_formatted) . '</pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>';

    // Combinar acciones
    $acciones_completas = $acciones . ' ' . $data_button . $modal;

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
        $acciones_completas,
    ]);
}

$table->finish_output();

echo $OUTPUT->footer();
