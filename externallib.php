<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/assign/lib.php");
require_once("$CFG->dirroot/course/lib.php");

class local_recibeexamen_external extends external_api {

    public static function receive_exam_parameters() {
        return new external_function_parameters(
            [
                'idusuldap' => new external_value(PARAM_RAW, 'Nombre de usuario del estudiante'),
                'asscodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'vaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'gaccodnum' => new external_value(PARAM_INT, 'ID del curso'),
                'anyanyaca' => new external_value(PARAM_RAW, 'Datos del examen')
            ]
        );
    }

    public static function receive_exam($idusuldap, $asscodnum, $vaccodnum, $gaccodnum, $anyanyaca) {
        global $DB, $USER, $CFG;
    
        $params = self::validate_parameters(self::receive_exam_parameters(), [
            'idusuldap' => $idusuldap,
            'asscodnum' => $asscodnum,
            'vaccodnum' => $vaccodnum,
            'gaccodnum' => $gaccodnum,
            'anyanyaca' => $anyanyaca
        ]);
    
        if (!$user = $DB->get_record('user', ['username' => $params['idusuldap']])) {
            throw new moodle_exception('errorusernotfound', 'local_recibeexamen');
        }
    
        $courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] .
                             '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];
    
        if (!$course = $DB->get_record('course', ['shortname' => $courseshortname])) {
            throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
        }
    
        // 🔹 Paso 1: Buscar o crear la tarea en el curso.
        $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => 'Examen final2 ' . $gaccodnum]);
        if (!$assign) {
            $assign_data = new stdClass();
            $assign_data->course = $course->id;
            $assign_data->name = 'Examen final2 ' . $gaccodnum;
            $assign_data->intro = 'Sube aquí tu examen.';
            $assign_data->introformat = FORMAT_HTML;
            $assign_data->duedate = time() + (7 * 24 * 60 * 60);
            $assign_data->allowsubmissionsfromdate = time();
            $assign_data->grade = 100;
            $assign_data->submissiondrafts = 0;
            $assign_data->requiresubmissionstatement = 0;
            $assign_data->teamsubmission = 0;
            $assign_data->timecreated = time();
            $assign_data->timemodified = time();
    
            $assign_data->id = $DB->insert_record('assign', $assign_data);
            $assign = $assign_data;
    
            // 🔹 Paso 2: Crear el módulo del curso.
            $module = $DB->get_record('modules', ['name' => 'assign']);
            if (!$module) {
                throw new moodle_exception('errorinvalidmodule', 'local_recibeexamen');
            }
    
            $cm = new stdClass();
            $cm->course = $course->id;
            $cm->module = $module->id;
            $cm->instance = $assign->id;
            $cm->section = 1;
            $cm->visible = 1;
            $cm->visibleoncoursepage = 1;
            $cm->added = time();
            $cm->completion = 0;
            $cm->groupmode = 0;
    
            $cmid = $DB->insert_record('course_modules', $cm);
            $DB->set_field('course_modules', 'idnumber', $assign->id, ['id' => $cmid]);
    
            // 🔹 Paso 3: Asociar el módulo con la sección del curso.
            $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
            if (!$section) {
                $section = new stdClass();
                $section->course = $course->id;
                $section->section = 1;
                $section->sequence = $cmid;
                $section->visible = 1;
                $DB->insert_record('course_sections', $section);
            } else {
                $section->sequence = trim($section->sequence . ',' . $cmid, ',');
                $DB->update_record('course_sections', $section);
            }
    
            // 🔹 Paso 4: Purga la caché del curso.
            rebuild_course_cache($course->id);
        }
    
        // 🔹 Paso 5: Crear o actualizar la entrega del usuario.
        $submission = $DB->get_record('assign_submission', [
            'assignment' => $assign->id,
            'userid'     => $user->id
        ]);
        if (!$submission) {
            $submission = new stdClass();
            $submission->assignment = $assign->id;
            $submission->userid = $user->id;
            $submission->timecreated = time();
            $submission->timemodified = time();
            $submission->status = 'submitted';
            $submission->attemptnumber = 0;
            $submission->groupid = 0;
    
            $submission->id = $DB->insert_record('assign_submission', $submission);
        } else {
            $submission->timemodified = time();
            $DB->update_record('assign_submission', $submission);
        }
    
        // 🔹 Paso 6: Subir el archivo al área de la entrega.
        $coursemodule = $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $assign->id]);
        $cmid = $coursemodule->id;
    
        $stored_file = null;
        if (!empty($_FILES['pdfdata']) && $_FILES['pdfdata']['error'] === UPLOAD_ERR_OK) {
            $uploadedfile = $_FILES['pdfdata'];
            $fs = get_file_storage();
            $context = context_module::instance($cmid);
            $filename = 'examen_' . time() . '.pdf';
    
            if (!file_exists($uploadedfile['tmp_name'])) {
                throw new moodle_exception('Error: El archivo temporal no existe.');
            }
    
            $file_record = [
                'contextid'   => $context->id,
                'component'   => 'assignsubmission_file',
                'filearea'    => 'submission_files',
                'itemid'      => $submission->id,
                'filepath'    => '/',
                'filename'    => $filename,
                'timecreated' => time(),
                'timemodified'=> time()
            ];
    
            $stored_file = $fs->create_file_from_pathname($file_record, $uploadedfile['tmp_name']);
        }
    
        // 🔹 Paso 7: Disparar el evento de archivo evaluable y actualizar el registro en assignsubmission_file.
        if (!empty($stored_file)) {
            // Construir un array con el archivo subido (para un único archivo).
            $files = [];
            $files[$stored_file->get_pathnamehash()] = $filename;
    
            // Preparar los parámetros para el evento.
            $eventparams = [
                'context'  => $context,
                'courseid' => $course->id,
                'objectid' => $submission->id,
                'other'    => [
                    'content'        => '',
                    'pathnamehashes' => array_keys($files)
                ],
                'userid'   => $USER->id
            ];
    
            // Se usa el evento assessable_uploaded definido en assignsubmission_file.
            $event = \assignsubmission_file\event\assessable_uploaded::create($eventparams);
            $event->set_legacy_files($files);
            $event->trigger();
    
            // Contar el número de archivos en el área de la entrega.
            $numfiles = count($fs->get_area_files(
                $context->id,
                'assignsubmission_file',
                'submission_files',
                $submission->id,
                'sortorder ASC',
                false
            ));
    
            // Actualizar (o insertar) el registro en assignsubmission_file.
            $filesubmission = $DB->get_record('assignsubmission_file', [
                'submission' => $submission->id,
                'assignment' => $assign->id
            ]);
            if ($filesubmission) {
                $filesubmission->numfiles = $numfiles;
                $DB->update_record('assignsubmission_file', $filesubmission);
            } else {
                $filesubmission = new stdClass();
                $filesubmission->submission = $submission->id;
                $filesubmission->assignment = $assign->id;
                $filesubmission->numfiles = $numfiles;
                $DB->insert_record('assignsubmission_file', $filesubmission);
            }
        }
    
        // 🔹 Paso 8: Purga la caché para que se actualice la interfaz.
        purge_caches();
    
        return [
            'status'       => 'success',
            'assignid'     => $assign->id,
            'submissionid' => $submission->id,
            'cmid'         => $cmid
        ];
    }
    
    
    

    public static function receive_exam_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'Estado de la operación'),
                'assignid' => new external_value(PARAM_INT, 'ID de la tarea en Moodle'),
                'submissionid' => new external_value(PARAM_INT, 'ID de la entrega en Moodle'),
                'cmid' => new external_value(PARAM_INT, 'ID del módulo del curso')
            ]
        );
    }
}
