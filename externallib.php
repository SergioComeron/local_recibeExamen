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
        global $DB, $USER, $CFG;
        $cmid = 0;
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
    
        $courseshortname = $params['anyanyaca'] . '_' . $params['asscodnum'] .
                             '_' . $params['vaccodnum'] . '_' . $params['gaccodnum'];
    
        if (!$course = $DB->get_record('course', ['shortname' => $courseshortname])) {
            throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
        }


        // Buscar cursos en los que este curso está metaenlazado.
        $sql = "SELECT e.id, e.courseid, c.fullname
                FROM {enrol} e
                JOIN {course} c ON e.courseid = c.id
                WHERE e.enrol = 'meta' AND e.customint1 = :courseid";

        $meta_courses = $DB->get_records_sql($sql, ['courseid' => $course->id]);
        /// --->>> HAY QUE MIRAR SI PUEDE HABER MAS DE UN METAENLAZADO <---- PODRÍA DAR PROBLEMAS.
        if ($meta_courses) {
            $meta_course = reset($meta_courses);
            if ($meta_course) {
                $course = $DB->get_record('course', ['id' => $meta_course->courseid]);
                if (!$course) {
                    throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
                }
            } else {
                throw new moodle_exception('errorcoursenotfound', 'local_recibeexamen');
            }
        }

        $assignname = 'Examen final '.' '. $tcocodalf .'-' . $anyanyaca;
        $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname]);
        $module = $DB->get_record('modules', ['name' => 'assign']);

        if (!$assign) {
            if (!$module) {
                throw new moodle_exception('errorinvalidmodule', 'local_recibeexamen');
            }

            // Buscar la última sección del curso
            $last_section = $DB->get_record_sql('SELECT MAX(section) AS section FROM {course_sections} WHERE course = ?', [$course->id]);
            $new_section_number = $last_section->section + 1;

            // Crear una nueva sección
            $section = new stdClass();
            $section->course = $course->id;
            $section->section = $new_section_number;
            $section->sequence = '';
            $section->visible = 1;
            $section->id = $DB->insert_record('course_sections', $section);

            // Crear el módulo de curso para la tarea
            $cm = new stdClass();
            $cm->course = $course->id;
            $cm->module = $module->id;
            $cm->instance = 0;
            $cm->section = $new_section_number;
            $cm->visible = 0;
            $cm->visibleoncoursepage = 1;
            $cm->added = time();
            $cm->completion = 0;
            $cm->groupmode = 0;
            $cm->lang = '';

            $cmid = $DB->insert_record('course_modules', $cm);

            // Actualizar la secuencia de la nueva sección
            $section->sequence = $cmid;
            $DB->update_record('course_sections', $section);

            // Crear la tarea
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
        } else {
            $assignid = $assign->id;
            $cmid = $DB->get_field('course_modules', 'id', ['module' => $module->id, 'instance' => $assignid]);
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
            $filename = 'ex_' . $course->id . '-u-' .  $user->id . 't' . time() . '.pdf';
    
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

        // Crear el PDF
        $fs = get_file_storage();
        $pdf = new pdf();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('UDIMA');
        $pdf->SetTitle('Justificante de asistencia');
        $pdf->SetSubject('Justificante');
        $pdf->SetMargins(20, 30, 20); // Margen superior más amplio para el logo
        $pdf->AddPage();

        // Insertar logotipo (ajusta tamaño y posición si quieres)
        $logopath = $CFG->dirroot . '/local/recibeexamen/pix/udima_logo.png';
        if (file_exists($logopath)) {
            $pdf->Image($logopath, 15, 10, 30);
        }

        // Contenido HTML del justificante
        $fecha = userdate(time(), '%d de %B de %Y');
        $html = '
        <style>
            .title { font-size: 16pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
            .text { font-size: 12pt; text-align: justify; }
            .info { font-size: 11pt; }
            .footer { font-size: 9pt; text-align: center; margin-top: 50px; }
        </style>

        <div class="title">JUSTIFICANTE DE ASISTENCIA A EXAMEN</div>

        <div class="text">
            Collado Villalba, a ' . $fecha . '<br><br>

            D/Dª <strong>' . fullname($user) . '</strong> con Número de Documento de Identificación: <strong>' . $dniprs  . '</strong>,
            matriculado/a en esta Universidad en estudios universitarios conducentes a una titulación oficial, ha asistido a
            la realización del examen convocado por la Universidad a Distancia de Madrid, en la fecha, hora y sede que figura
            a continuación, expidiéndose a petición del interesado el presente certificado a los efectos oportunos.
        </div><br>

        <div class="info">
            <strong>Información relativa al examen:</strong><br><br>
            <strong>Código examen:</strong> ' . $exacodnum . '<br>
            <strong>Titulación:</strong> '. $planomid1 .'<br>
            <strong>Asignatura:</strong> ' . $assnomid1 . '<br>
            <strong>Fecha y hora de inicio:</strong> ' . $fechainicio . '<br>
            <strong>Fecha y hora de finalización:</strong> ' . $fechafin . '<br>
            <strong>Sede:</strong> ' . $sede . '<br>
        </div><br><br>

        <div class="text">Firma y sello</div><br><br>
        ';

        // Escribir el HTML
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Añadir firma (opcional)
        $firmapath = $CFG->dirroot . '/local/recibeexamen/pix/firma.png';
        if (file_exists($firmapath)) {
            $pdf->Image($firmapath, 20, $pdf->GetY(), 50);
        }

        // Pie de página
        $pdf->Ln(40);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 10, "Carretera de La Coruña, km 38,500 (vía de servicio, n.º 15) • 28400 Collado Villalba (Madrid) • 902 02 00 03\nwww.udima.es • informa@udima.es", 0, 'C');

        // Guardar PDF en temporal
        $filename = "justificante_{$user->username}.pdf";
        $tempdir = make_temp_directory('local_recibeexamen');
        $pdfpath = $tempdir . '/' . $filename;
        $pdf->Output($pdfpath, 'F');

        // Enviar correo
        $subject = "Justificante - {$user->username}";

        // Preparar mensajes para texto plano y HTML.
        $message_plain = "Estimado/a {$user->firstname},\n\nAdjunto le remitimos el justificante de asistencia al examen.\n\nSaludos cordiales.";
        $message_html = nl2br($message_plain);

        // Enviar correo con adjunto
        $emailresult = email_to_user(
            $user,
            core_user::get_support_user(),
            $subject,
            $message_plain,
            $message_html,
            $pdfpath,
            $filename
        );

        if (!$emailresult) {
            throw new moodle_exception('errorcannotemail', 'local_recibeexamen');
        }

        // Eliminar temporal
        @unlink($pdfpath);


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

//Quizá no merece la pena porque la gente de examenes tiene el dni.
function obtener_numdocumento_ldap(string $uid): ?string {
    $host = "ldap://172.21.4.20:389";
    $base_dn = "cn=users,dc=udima,dc=es";
    $filtro = "(uid=$uid)";
    $atributos = ["numdocumento"];

    $ldapconn = ldap_connect($host);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

    if (!$ldapconn) {
        debugging("No se pudo conectar al servidor LDAP", DEBUG_DEVELOPER);
        return null;
    }

    // No se necesita autenticación, bind anónimo
    if (!ldap_bind($ldapconn)) {
        debugging("Fallo en el bind LDAP", DEBUG_DEVELOPER);
        return null;
    }

    $resultado = ldap_search($ldapconn, $base_dn, $filtro, $atributos);
    if (!$resultado) {
        debugging("Error en la búsqueda LDAP", DEBUG_DEVELOPER);
        return null;
    }

    $entradas = ldap_get_entries($ldapconn, $resultado);
    ldap_unbind($ldapconn);

    if ($entradas["count"] > 0 && isset($entradas[0]["numdocumento"][0])) {
        return $entradas[0]["numdocumento"][0];
    }

    return null; // No encontrado
}
