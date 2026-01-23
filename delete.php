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
 * Elimina la entrega del examen del buzón de tareas.
 *
 * @package    local_recibeexamen
 * @copyright  2025 Sergio Comerón <sergio.comeron@udima.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

require_login();
require_capability('local/recibeexamen:viewqueue', context_system::instance());

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$page = optional_param('page', 0, PARAM_INT);

$url = new moodle_url('/local/recibeexamen/delete.php', ['id' => $id, 'page' => $page]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('deletesubmission', 'local_recibeexamen'));
$PAGE->set_heading(get_string('deletesubmission', 'local_recibeexamen'));

// Obtener el registro de la cola.
if (!$entry = $DB->get_record('local_recibeexamen_queue', ['id' => $id])) {
    throw new moodle_exception('errorqueuenotfound', 'local_recibeexamen');
}

// Solo se puede eliminar si está procesado.
if ($entry->status !== 'done') {
    throw new moodle_exception('errornotprocessed', 'local_recibeexamen');
}

$params = json_decode($entry->data, true);

// Obtener usuario.
$user = $DB->get_record('user', ['username' => $params['idusuldap']]);
if (!$user) {
    throw new moodle_exception('errorusernotfound', 'local_recibeexamen');
}

// Construir shortname del curso.
$courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] .
                   '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];

// Obtener el curso.
$course = $DB->get_record('course', ['shortname' => $courseshortname]);
if (!$course) {
    throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
}

// Buscar cursos en los que este curso está metaenlazado (igual que en process_exam_task).
$sql = "SELECT e.id, e.courseid, c.fullname
        FROM {enrol} e
        JOIN {course} c ON e.courseid = c.id
        WHERE e.enrol = 'meta' AND e.customint1 = :courseid";
$metacourses = $DB->get_records_sql($sql, ['courseid' => $course->id]);
if ($metacourses) {
    $metacourse = reset($metacourses);
    if ($metacourse) {
        $course = $DB->get_record('course', ['id' => $metacourse->courseid]);
        if (!$course) {
            throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
        }
    }
}

// Construir nombre de la tarea.
$assignname = 'Examen final ' . $params['tcocodalf'] . '-' . $params['anyanyaca'];
$assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname]);
if (!$assign) {
    throw new moodle_exception('errorassignnotfound', 'local_recibeexamen');
}

// Obtener la submission del usuario (puede que ya no exista si se eliminó desde otro registro).
$submission = $DB->get_record('assign_submission', [
    'assignment' => $assign->id,
    'userid' => $user->id,
]);

// Obtener el course module.
$module = $DB->get_record('modules', ['name' => 'assign']);
$cm = $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $assign->id]);
if (!$cm) {
    throw new moodle_exception('errormissingcoursemodule', 'local_recibeexamen');
}

echo $OUTPUT->header();

if ($confirm && confirm_sesskey()) {
    // Solo eliminar si existe la submission.
    if ($submission) {
        // Eliminar archivos de la submission.
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id, 'assignsubmission_file', 'submission_files', $submission->id);

        // Eliminar registro de assignsubmission_file.
        $DB->delete_records('assignsubmission_file', ['submission' => $submission->id, 'assignment' => $assign->id]);

        // Eliminar la submission.
        $DB->delete_records('assign_submission', ['id' => $submission->id]);
    }

    // Actualizar el estado en la cola a 'deleted'.
    $entry->status = 'deleted';
    $entry->timemodified = time();
    $DB->update_record('local_recibeexamen_queue', $entry);

    echo $OUTPUT->notification(get_string('submissiondeleted', 'local_recibeexamen'), 'notifysuccess');
    echo $OUTPUT->continue_button(new moodle_url('/local/recibeexamen/listado.php', ['page' => $page]));
} else {
    // Mostrar confirmación.
    $confirmurl = new moodle_url('/local/recibeexamen/delete.php', [
        'id' => $id,
        'confirm' => 1,
        'sesskey' => sesskey(),
        'page' => $page,
    ]);
    $cancelurl = new moodle_url('/local/recibeexamen/listado.php', ['page' => $page]);

    if ($submission) {
        $message = get_string('confirmdeletesubmission', 'local_recibeexamen', [
            'user' => fullname($user),
            'course' => $course->fullname,
            'assign' => $assignname,
        ]);
    } else {
        $message = get_string('confirmdeletenosubmission', 'local_recibeexamen', [
            'user' => fullname($user),
        ]);
    }

    echo $OUTPUT->confirm($message, $confirmurl, $cancelurl);
}

echo $OUTPUT->footer();
