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
 * TODO describe file recibeexamen_queue_table
 *
 * @package    local_recibeexamen
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir.'/adminlib.php');

/**
 * Extend the standard table class for jitsi.
 */

class recibeexamen_queue_table extends flexible_table {
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns(['id', 'userid', 'courseid', 'status', 'timecreated']);
        $this->define_headers([
            get_string('id', 'local_recibeexamen'),
            get_string('userid', 'local_recibeexamen'),
            get_string('courseid', 'local_recibeexamen'),
            get_string('status', 'local_recibeexamen'),
            get_string('timecreated', 'local_recibeexamen')
        ]);

        $this->sortable(true, 'id', SORT_DESC);
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable generalbox');
        $this->pageable(true);
    }
}
