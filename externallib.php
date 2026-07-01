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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/assign/lib.php");
require_once("$CFG->dirroot/course/lib.php");
require_once($CFG->libdir . '/pdflib.php'); // Incluir la biblioteca TCPDF

/**
 * Servicios web externos del plugin local_recibeexamen.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_recibeexamen_external extends external_api {
    /**
     * Define los parámetros de entrada del webservice receive_exam.
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
                'anyanyaca' => new external_value(PARAM_RAW, 'Curso académico'),
                'tcocodalf' => new external_value(PARAM_RAW, 'Convocatoria'),
                'planomid1' => new external_value(PARAM_RAW, 'Plan de estudios'),
                'assnomid1' => new external_value(PARAM_RAW, 'Nombre de la asignatura'),
                'fechainicio' => new external_value(PARAM_RAW, 'Fecha de inicio del examen en formato ISO 8601 con zona horaria'),
                'fechafin' => new external_value(PARAM_RAW, 'Fecha de fin del examen en formato ISO 8601 con zona horaria'),
                'sede' => new external_value(PARAM_RAW, 'Sede del examen'),
                'exacodnum' => new external_value(PARAM_INT, 'ID del examen'),
                'dniprs' => new external_value(PARAM_RAW, 'DNI del estudiante'),
            ]
        );
    }

    /**
     * Recibe un examen escaneado, lo encola y lanza la tarea de procesamiento.
     *
     * @param string $idusuldap Nombre de usuario del estudiante.
     * @param int $asscodnum Código de asignatura.
     * @param int $vaccodnum Código de vinculación académica.
     * @param int $gaccodnum Código de grupo académico.
     * @param string $anyanyaca Curso académico.
     * @param string $tcocodalf Convocatoria.
     * @param string $planomid1 Plan de estudios.
     * @param string $assnomid1 Nombre de la asignatura.
     * @param string $fechainicio Fecha de inicio del examen (ISO 8601).
     * @param string $fechafin Fecha de fin del examen (ISO 8601).
     * @param string $sede Sede del examen.
     * @param int $exacodnum Código del examen.
     * @param string $dniprs DNI del estudiante.
     * @return array Estado de la operación y id en cola.
     */
    public static function receive_exam(
        $idusuldap,
        $asscodnum,
        $vaccodnum,
        $gaccodnum,
        $anyanyaca,
        $tcocodalf,
        $planomid1,
        $assnomid1,
        $fechainicio,
        $fechafin,
        $sede,
        $exacodnum,
        $dniprs
    ) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::receive_exam_parameters(), [
            'idusuldap' => $idusuldap,
            'asscodnum' => $asscodnum,
            'vaccodnum' => $vaccodnum,
            'gaccodnum' => $gaccodnum,
            'anyanyaca' => $anyanyaca,
            'tcocodalf' => $tcocodalf,
            'planomid1' => $planomid1,
            'assnomid1' => $assnomid1,
            'fechainicio' => $fechainicio,
            'fechafin' => $fechafin,
            'sede' => $sede,
            'exacodnum' => $exacodnum,
            'dniprs' => $dniprs,
        ]);

        if (!$user = $DB->get_record('user', ['username' => $params['idusuldap']])) {
            throw new moodle_exception('errorusernotfound', 'local_recibeexamen');
        }

        // Validar archivo PDF
        if (empty($_FILES['pdfdata']) || $_FILES['pdfdata']['error'] !== UPLOAD_ERR_OK) {
            throw new moodle_exception('nofileuploaded', 'local_recibeexamen');
        }

        if ($_FILES['pdfdata']['size'] > 104857600) { // 100 MB
            throw new moodle_exception('filetoobig', 'local_recibeexamen');
        }

        if (!is_uploaded_file($_FILES['pdfdata']['tmp_name'])) {
            throw new moodle_exception('uploadfailed', 'local_recibeexamen');
        }

        $filename = 'exam_' . $user->id . '_' . time() . '.pdf';

        // Insertar en la cola de procesamiento. El PDF se almacena en el File API
        // (área 'examqueue', itemid = queueid) en vez de moodledata/temp, para que
        // la tarea adhoc lo recupere de forma fiable aunque se ejecute más tarde y
        // sin riesgo de que la limpieza de temporales lo borre.
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->data = json_encode($params);
        $record->filename = $filename;
        $record->filepath = ''; // Legado: ya no se usa una ruta de disco.
        $record->status = 'pending';
        $record->timecreated = time();
        $record->timemodified = time();
        $queueid = $DB->insert_record('local_recibeexamen_queue', $record);

        // Almacenar el PDF subido en el área de archivos de la cola.
        try {
            \local_recibeexamen\queue_files::store($queueid, $_FILES['pdfdata']['tmp_name'], $filename);
        } catch (\Exception $e) {
            // Si falla el guardado, no dejar un registro huérfano en la cola.
            $DB->delete_records('local_recibeexamen_queue', ['id' => $queueid]);
            throw new moodle_exception('uploadfailed', 'local_recibeexamen');
        }

        // Lanzar tarea adhoc
        $task = new \local_recibeexamen\task\process_exam_task();
        $task->set_custom_data(['queueid' => $queueid]);
        \core\task\manager::queue_adhoc_task($task);

        return [
            'status' => 'queued',
            'queueid' => $queueid,
        ];
    }

    /**
     * Define la estructura de retorno del webservice receive_exam.
     *
     * @return external_single_structure
     */
    public static function receive_exam_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Estado de la operación'),
                'queueid' => new external_value(PARAM_INT, 'ID de la peticion en cola'),
            ]
        );
    }
}
