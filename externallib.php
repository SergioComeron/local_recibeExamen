<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/assign/lib.php");
require_once("$CFG->dirroot/course/lib.php");
require_once($CFG->libdir . '/pdflib.php'); // Incluir la biblioteca TCPDF

class local_recibeexamen_external extends external_api {

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
                'exacodnum' => new external_value(PARAM_INT, 'ID del examen'),
            ]
        );
    }

    public static function receive_exam($idusuldap, $asscodnum, $vaccodnum, $gaccodnum, $anyanyaca, $tcocodalf, $planomid1,
    $assnomid1, $fechainicio, $fechafin, $sede, $exacodnum, $dniprs) {
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

        // Guardar archivo en carpeta temporal
        $filename = 'exam_' . $user->id . '_' . time() . '.pdf';
        $tempdir = make_temp_directory('recibeexamen');
        $temppath = $tempdir . '/' . $filename;

        if (!move_uploaded_file($_FILES['pdfdata']['tmp_name'], $temppath)) {
            throw new moodle_exception('uploadfailed', 'local_recibeexamen');
        }

        // Insertar en la cola de procesamiento
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->data = json_encode($params);
        $record->filename = $filename;
        $record->filepath = $temppath;
        $record->status = 'pending';
        $record->timecreated = time();
        $record->timemodified = time();
        $queueid = $DB->insert_record('local_recibeexamen_queue', $record);

        // Lanzar tarea adhoc
        $task = new \local_recibeexamen\task\process_exam_task();
        $task->set_custom_data(['queueid' => $queueid]);
        \core\task\manager::queue_adhoc_task($task);

        return [
            'status' => 'queued',
            'queueid' => $queueid,
        ];
    }

    public static function receive_exam_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Estado de la operación'),
                'queueid' => new external_value(PARAM_INT, 'ID de la peticion en cola'),
            ]
        );
    }
}
