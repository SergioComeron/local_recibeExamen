userdate($entry->timecreated)

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
 * TODO describe file listado
 *
 * @package    local_recibeexamen
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('recibeexamen_queue_table.php');
require_login();

$url = new moodle_url('/local/recibeexamen/listado.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading(get_string('list', 'local_recibeexamen'));
require_once($CFG->libdir . '/tablelib.php');

$table = new recibeexamen_queue_table('recibeexamen_queue_table');
$table->define_baseurl($PAGE->url);

$total = $DB->count_records('local_recibeexamen_queue');
$table->pagesize(20, $total);

$table->setup();
$sort = $table->get_sql_sort();
if (empty($sort)) {
    $sort = "id DESC";
}

$fields = 'id, userid, data, filename, filepath, status, timecreated, timemodified';
$entries = $DB->get_records_sql("SELECT $fields FROM {local_recibeexamen_queue} ORDER BY $sort", null, $table->get_page_start(), $table->get_page_size());

foreach ($entries as $entry) {
    $row = [
        $entry->id,
        $entry->userid,
        $entry->courseid,
        $entry->status,
    ];
    $table->add_data($row);
}

ob_start();
$table = new recibeexamen_queue_table('recibeexamen_queue_table');

echo $OUTPUT->header();
echo $OUTPUT->footer();


