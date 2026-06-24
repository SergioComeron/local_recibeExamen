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
 * Integration tests for local_recibeexamen\task\process_exam_task.
 *
 * @package   local_recibeexamen
 * @copyright 2026, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recibeexamen;

use local_recibeexamen\task\process_exam_task;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_recibeexamen\task\process_exam_task
 */
class process_exam_task_test extends \advanced_testcase {

    /** @var \stdClass Curso de prueba. */
    private $course;

    /** @var \stdClass Usuario de prueba. */
    private $user;

    /** @var array Parámetros del examen codificados en el campo data. */
    private $params;

    /**
     * Prepara curso, usuario y parámetros coherentes con el shortname que
     * construye la tarea: anyanyaca_asscodnum_vaccodnum_gaccodnum.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->redirectEmails();

        set_config('justificante_email', 'justificantes@example.com', 'local_recibeexamen');

        $this->params = [
            'idusuldap'   => 'usuldap01',
            'asscodnum'   => 100,
            'vaccodnum'   => 200,
            'gaccodnum'   => 300,
            'anyanyaca'   => '2026',
            'tcocodalf'   => 'A',
            'planomid1'   => 'Grado de prueba',
            'assnomid1'   => 'Asignatura de prueba',
            'fechainicio' => '2026-06-24T09:00:00+02:00',
            'fechafin'    => '2026-06-24T11:00:00+02:00',
            'sede'        => 'Madrid',
            'exacodnum'   => 555,
            'dniprs'      => '00000000T',
        ];

        $shortname = $this->params['anyanyaca'] . '_' . $this->params['asscodnum'] .
            '_' . $this->params['vaccodnum'] . '_' . $this->params['gaccodnum'];

        $this->course = $this->getDataGenerator()->create_course(['shortname' => $shortname]);
        $this->user = $this->getDataGenerator()->create_user(['username' => $this->params['idusuldap']]);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
    }

    /**
     * Inserta una entrada de cola con su PDF almacenado en el área examqueue.
     *
     * @param string $content Contenido del PDF de prueba.
     * @return int ID de la entrada de cola.
     */
    private function queue_entry(string $content): int {
        global $DB;

        $record = new \stdClass();
        $record->userid = $this->user->id;
        $record->data = json_encode($this->params);
        $record->filename = 'examen.pdf';
        $record->filepath = '';
        $record->status = 'pending';
        $record->timecreated = time();
        $record->timemodified = time();
        $queueid = $DB->insert_record('local_recibeexamen_queue', $record);

        $dir = make_request_directory();
        $source = $dir . '/examen.pdf';
        file_put_contents($source, $content);
        queue_files::store($queueid, $source, 'examen.pdf');

        return $queueid;
    }

    /**
     * Ejecuta la tarea adhoc para una entrada de cola, capturando la salida
     * que el código emite con echo al crear la tarea de Moodle.
     *
     * @param int $queueid ID de la entrada de cola.
     */
    private function run_task(int $queueid): void {
        $task = new process_exam_task();
        $task->set_custom_data(['queueid' => $queueid]);

        ob_start();
        $task->execute();
        ob_end_clean();
    }

    public function test_processes_queue_and_copies_pdf_to_submission(): void {
        global $DB;

        $content = '%PDF-1.4 contenido del examen';
        $queueid = $this->queue_entry($content);

        $this->run_task($queueid);

        // La entrada de cola queda marcada como procesada.
        $entry = $DB->get_record('local_recibeexamen_queue', ['id' => $queueid]);
        $this->assertSame('done', $entry->status);

        // Se ha creado la tarea (assign) con el nombre esperado.
        $assignname = 'Examen final ' . $this->params['tcocodalf'] . '-' . $this->params['anyanyaca'];
        $assign = $DB->get_record('assign', ['course' => $this->course->id, 'name' => $assignname]);
        $this->assertNotEmpty($assign);

        // Existe una entrega del usuario con el PDF copiado y el contenido correcto.
        $submission = $DB->get_record('assign_submission', [
            'assignment' => $assign->id,
            'userid'     => $this->user->id,
        ]);
        $this->assertNotEmpty($submission);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'assign']);
        $cm = $DB->get_record('course_modules', ['module' => $moduleid, 'instance' => $assign->id]);
        $context = \context_module::instance($cm->id);

        // course_modules.section apunta al id de una fila de course_sections de
        // este curso (no al número de sección), por lo que el rebuild de caché
        // no rompe la comprobación de integridad.
        $section = $DB->get_record('course_sections', ['id' => $cm->section]);
        $this->assertNotEmpty($section);
        $this->assertEquals($this->course->id, $section->course);

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'assignsubmission_file',
            'submission_files',
            $submission->id,
            'itemid, filepath, filename',
            false
        );
        $this->assertCount(1, $files);
        $submitted = reset($files);
        $this->assertSame($content, $submitted->get_content());

        // El PDF de la cola se ha eliminado del File API tras procesarlo.
        $this->assertNull(queue_files::get($queueid));
    }

    public function test_missing_user_marks_entry_failed(): void {
        global $DB;

        $this->params['idusuldap'] = 'noexiste_xyz';
        $queueid = $this->queue_entry('contenido');

        $task = new process_exam_task();
        $task->set_custom_data(['queueid' => $queueid]);

        try {
            ob_start();
            $task->execute();
            ob_end_clean();
            $this->fail('Se esperaba una moodle_exception por usuario inexistente.');
        } catch (\moodle_exception $e) {
            ob_end_clean();
            $this->assertStringContainsString('user', strtolower($e->getMessage()) . $e->errorcode);
            // La tarea registra el error con debugging() antes de relanzar.
            $this->assertDebuggingCalled();
        }

        $entry = $DB->get_record('local_recibeexamen_queue', ['id' => $queueid]);
        $this->assertSame('failed', $entry->status);
    }
}
