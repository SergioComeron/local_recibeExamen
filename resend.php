<?php
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$id = required_param('id', PARAM_INT);
$url = new moodle_url('/local/recibeexamen/resend.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Reenvío de justificante');
$PAGE->set_heading('Reenvío de justificante');

echo $OUTPUT->header();

if (!$entry = $DB->get_record('local_recibeexamen_queue', ['id' => $id])) {
    throw new moodle_exception('Registro no encontrado');
}

$task = new \local_recibeexamen\task\resend_task();
$task->set_custom_data(['queueid' => $id]);
\core\task\manager::queue_adhoc_task($task);

echo $OUTPUT->notification('La tarea de reenvío del justificante ha sido lanzada correctamente.', 'notifysuccess');
echo $OUTPUT->continue_button(new moodle_url('/local/recibeexamen/listado.php'));

echo $OUTPUT->footer();
