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
                'anyanyaca' => new external_value(PARAM_RAW, 'Curso académico'),
                'tcocodalf' => new external_value(PARAM_RAW, 'Convocatoria')
            ]
        );
    }

    public static function receive_exam($idusuldap, $asscodnum, $vaccodnum, $gaccodnum, $anyanyaca, $tcocodalf) {
        global $DB, $USER, $CFG;
        $cmid = 0;
        $params = self::validate_parameters(self::receive_exam_parameters(), [
            'idusuldap' => $idusuldap,
            'asscodnum' => $asscodnum,
            'vaccodnum' => $vaccodnum,
            'gaccodnum' => $gaccodnum,
            'anyanyaca' => $anyanyaca, 
            'tcocodalf' => $tcocodalf
        ]);
    
        if (!$user = $DB->get_record('user', ['username' => $params['idusuldap']])) {
            throw new moodle_exception('errorusernotfound', 'local_recibeexamen');
        }
    
        $courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] .
                             '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];
    
        if (!$course = $DB->get_record('course', ['shortname' => $courseshortname])) {
            throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
        }
    
        $assignname = 'Examen final '.' '. $tcocodalf .'-' . $anyanyaca.'/'. $gaccodnum;
        $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname]);
        $module = $DB->get_record('modules', ['name' => 'assign']);

        if (!$assign) {
            if (!$module) {
                throw new moodle_exception('errorinvalidmodule', 'local_recibeexamen');
            }
            $cm = new stdClass();
            $cm->course = $course->id;
            $cm->module = $module->id;
            $cm->instance = 0;
            $cm->section = 1;
            $cm->visible = 1;
            $cm->visibleoncoursepage = 1;
            $cm->added = time();
            $cm->completion = 0;
            $cm->groupmode = 0;
            $cm->lang = '';
    
            $cmid = $DB->insert_record('course_modules', $cm);

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

            $assign_data = new stdClass();
            $assign_data->course = $course->id;
            $assign_data->name = $assignname;
            $assign_data->intro = '';
            $assign_data->introformat = FORMAT_HTML;
            $assign_data->alwaysshowdescription = 1;
            $assign_data->duedate = time() + (7 * 24 * 60 * 60);
            $assign_data->allowsubmissionsfromdate = time();
            $assign_data->grade = 100;
            $assign_data->submissiondrafts = 0;
            $assign_data->requiresubmissionstatement = 0;
            $assign_data->teamsubmission = 0;
            $assign_data->timecreated = time();
            $assign_data->timemodified = time();
            $assign_data->sendnotifications = 0;
            $assign_data->sendlatenotifications = 0;  // Agregado para evitar valor nulo
            $assign_data->completionsubmit = 1;
            $assign_data->gradingduedate = time() + (7 * 24 * 60 * 60);
            $assign_data->activity = '';
            $assign_data->activityformat = 1;
            $assign_data->coursemodule = $cmid;
            $assign_data->cutoffdate = 0;
            $assign_data->requireallteammemberssubmit = 0;
            $assign_data->teamsubmissiongroupingid = 0;
            $assign_data->blindmarking = 0;
            $assign_data->hidegrader = 0;
            $assign_data->revealidentities = 0;
            $assign_data->attemptreopenmethod = 'none';
            $assign_data->maxattempts = -1;
            $assign_data->markingworkflow = 0;
            $assign_data->markingallocation = 0;
            $assign_data->markinganonymous = 0;
            $assign_data->sendstudentnotifications = 1;
            $assign_data->preventsubmissionnotingroup = 0;
            $assign_data->submissionattachments = 0;
            $assign_data->assignsubmission_file_enabled = 1; // Habilitar la entrega de archivos
            $assign_data->assignsubmission_file_maxfiles = 1; // Número máximo de archivos permitidos
            $assign_data->assignsubmission_file_maxsizebytes = 10485760; // Tamaño máximo de archivo en bytes (10 MB)
            $assign_data->assignfeedback_editpdf_enabled = 1; // Habilitar el editor PDF para correcciones

            $assignid = assign_add_instance($assign_data);
            $DB->set_field('course_modules', 'instance', $assignid, ['id' => $cmid]);

            $assign = $DB->get_record('assign', ['id' => $assignid]);
            if ($assign) {
                echo "Tarea creada correctamente con ID: " . $assign->id;
            } else {
                echo "Error al crear la tarea.";
            }

            rebuild_course_cache($course->id);

        }
    
        $submission = $DB->get_record('assign_submission', [
            'assignment' => $assignid,
            'userid'     => $user->id
        ]);
        if (!$submission) {
            $submission = new stdClass();
            $submission->assignment = $assignid;
            $submission->userid = $user->id;
            $submission->timecreated = time();
            $submission->timemodified = time();
            $submission->status = 'submitted';
            $submission->attemptnumber = 0;
            $submission->groupid = 0;
            $submission->latest = 1;
    
            $submission->id = $DB->insert_record('assign_submission', $submission);
        } else {
            $submission->timemodified = time();
            $DB->update_record('assign_submission', $submission);
        }
    
        $coursemodule = $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $assignid]);
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
                'timemodified'=> time(),
                'userid'      => $user->id,
                'source'      => $filename,
                'author'      => $user->firstname . ' ' . $user->lastname,
                'license'     => 'unknown',
            ];
    
            $stored_file = $fs->create_file_from_pathname($file_record, $uploadedfile['tmp_name']);
        }

        if (!empty($stored_file)) {
            $files = [];
            $files[$stored_file->get_pathnamehash()] = $filename;
    
            $eventparams = [
                'context'  => $context,
                'courseid' => $course->id,
                'objectid' => $submission->id,
                'other'    => [
                    'content'        => '',
                    'pathnamehashes' => array_keys($files)
                ],
                'userid'   => $user->id
            ];
    
            $event = \assignsubmission_file\event\assessable_uploaded::create($eventparams);
            $event->set_legacy_files($files);
            $event->trigger();
    
            $numfiles = count($fs->get_area_files(
                $context->id,
                'assignsubmission_file',
                'submission_files',
                $submission->id,
                'sortorder ASC',
                false
            ));
    
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
                $filesubmission->assignment = $assignid;
                $filesubmission->userid = $user->id;
                $filesubmission->numfiles = $numfiles;
                $DB->insert_record('assignsubmission_file', $filesubmission);
            }
        }

        purge_caches();
    
        return [
            'status'       => 'success',
            'assignid'     => $assignid,
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
