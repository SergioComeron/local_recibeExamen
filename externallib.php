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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Clase que implementa la función local_recibeexamen_receive_exam.
 */
class local_recibeexamen_external extends external_api {

    /**
     * Define los parámetros que recibe la función.
     *
     * @return external_function_parameters
     */
    public static function receive_exam_parameters() {
        return new external_function_parameters(
            [
                'idusuldap' => new external_value(PARAM_RAW, 'Nombre de usuario del estudiante'),
                'asscodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'vaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'gaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'anyanyaca' => new external_value(PARAM_RAW, 'Datos del examen'),
                'pdfdata' => new external_value(PARAM_FILE, 'Archivo PDF', VALUE_OPTIONAL)
            ]
        );
    }

/**
 * Recibe el examen y lo registra en Moodle.
 *
 * @param string $idusuldap
 * @param int $asscodnum
 * @param int $vaccodnum
 * @param int $gaccodnum
 * @param string $anyanyaca
 * @return array
 * @throws moodle_exception
 */
public static function receive_exam($idusuldap, $asscodnum, $vaccodnum, $gaccodnum, $anyanyaca) {
    global $DB;

    // Validar parámetros.
    $params = self::validate_parameters(self::receive_exam_parameters(),
        [
            'idusuldap' => $idusuldap,
            'asscodnum' => $asscodnum,
            'vaccodnum' => $vaccodnum,
            'gaccodnum' => $gaccodnum,
            'anyanyaca' => $anyanyaca
        ]);

    // Verificar que el usuario exista.
    if (!$user = $DB->get_record('user', ['username' => $params['idusuldap']])) {
        throw new moodle_exception('errorusernotfound', 'local_recibeexamen');
    }

    // Construir el nombre corto del curso.
    $courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] .
        '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];

    // Verificar que el curso exista.
    if (!$course = $DB->get_record('course', ['shortname' => $courseshortname])) {
        throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
    }

    // Guardar el archivo PDF si se proporciona.
    $filename = null;
    if (!empty($_FILES['pdfdata']) && $_FILES['pdfdata']['error'] === UPLOAD_ERR_OK) {
        $uploadedfile = $_FILES['pdfdata'];
        $fs = get_file_storage();
        $context = context_course::instance($course->id);
        $filename = 'examen_' . time() . '.pdf';

        $file_record = [
            'contextid' => $context->id,
            'component' => 'local_recibeexamen',
            'filearea' => 'examfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'timecreated' => time(),
            'timemodified' => time()
        ];

        // Guardar el archivo en Moodle.
        $stored_file = $fs->create_file_from_pathname($file_record, $uploadedfile['tmp_name']);

        // Guardamos el nombre real del archivo
        $filename = $stored_file->get_filename();
    }

    // Lógica de registro en la base de datos.
    $record = new stdClass();
    $record->userid = $user->id;
    $record->courseid = $course->id;
    $record->examdata = 'prueba inicial';
    $record->pdfname = $filename;
    $record->timecreated = time();
    $record->timemodified = time();

    $insertid = $DB->insert_record('recibeexamen_exams', $record);

    return ['status' => 'success', 'examid' => $insertid];
}


    /**
     * Define la estructura de los datos de retorno.
     *
     * @return external_single_structure
     */
    public static function receive_exam_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Estado de la operación'),
                'examid' => new external_value(PARAM_INT, 'ID del registro del examen'),
            ]
        );
    }
}
