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
 * Version metadata for the local_recibeExamen plugin.
 *
 * @package   local_recibeExamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_recibeExamen_external extends external_api {

    /**
     * Define los parámetros que recibe la función.
     *
     * @return external_function_parameters
     */
    public static function receive_exam_parameters() {
        return new external_function_parameters(
            array(
                'idusuldap' => new external_value(PARAM_RAW, 'Nombre de usuario del estudiante'),
                'asscodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'vaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'gaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'anyanyaca' => new external_value(PARAM_RAW, 'Datos del examen')
            )
        );
    }

    /**
     * Recibe el examen y lo registra en Moodle.
     *
     * @param string $username
     * @param int $courseid
     * @param string $examdata
     * @return array
     * @throws moodle_exception
     */
    public static function receive_exam($idusuldap, $asscodnum, $vaccodnum, $gaccodnum, $anyanyaca) {
        global $DB;

        // Validar parámetros.
        $params = self::validate_parameters(self::receive_exam_parameters(),
            array('idusuldap' => $idusuldap, 'asscodnum' => $asscodnum, 'vaccodnum' => $vaccodnum, 'gaccodnum' => $gaccodnum, 'anyanyaca' => $anyanyaca));

        // Verificar que el usuario exista.
        if (!$user = $DB->get_record('user', array('username' => $params['username']))) {
            throw new moodle_exception('errorusernotfound', 'local_recibeExamen');
        }

        // Construir el nombre corto del curso.
        $courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] . '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];

        // Verificar que el curso exista.
        if (!$course = $DB->get_record('course', array('shortname' => $courseshortname))) {
            throw new moodle_exception('errorcoursenotfound', 'local_recibeExamen');
        }

        // Aquí podrías implementar la lógica para registrar el examen.
        // Por ejemplo, insertar un registro en una tabla personalizada:
        $record = new stdClass();
        $record->userid      = $user->id;
        $record->courseid    = $course->id;
        $record->examdata    = $params['examdata'];
        $record->timecreated = time();
        $record->timemodified= time();

        // Asegúrate de haber creado previamente la tabla local_docuwarews_exams en tu plugin.
        $insertid = $DB->insert_record('local_recibeExamen_exams', $record);

        return array('status' => 'success', 'examid' => $insertid);
    }

    /**
     * Define la estructura de los datos de retorno.
     *
     * @return external_single_structure
     */
    public static function receive_exam_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Estado de la operación'),
                'examid' => new external_value(PARAM_INT, 'ID del registro del examen')
            )
        );
    }
}