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
 * Version metadata for the local_recibeexamen plugin.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_recibeexamen_receive_exam' => [
         'classname'   => 'local_recibeexamen_external',
         'methodname'  => 'receive_exam',
         'classpath'   => 'local/recibeexamen/externallib.php',
         'description' => 'Recibe un examen y lo registra en el curso indicado',
         'type'        => 'write',
         'ajax'        => true,
    ],
];

$services = [
    'Recibe Examen Web Service' => [
         'functions' => ['local_recibeexamen_receive_exam'],
         'restrictedusers' => 1,
         'enabled' => 1,
    ],
];
