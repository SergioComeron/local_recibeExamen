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
 * Version metadata for the local_recibeExamen plugin.
 *
 * @package   local_recibeExamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_recibeExamen_receive_exam' => array(
         'classname'   => 'local_recibeExamen_external',
         'methodname'  => 'receive_exam',
         'classpath'   => 'local/recibeExamen/externallib.php',
         'description' => 'Recibe un examen y lo registra en el curso indicado',
         'type'        => 'write',
         'ajax'        => true,
    ),
);

$services = array(
    'DocuWare Web Service' => array(
         'functions' => array ('local_recibeExamen_receive_exam'),
         'restrictedusers' => 1,
         'enabled'=>1,
    )
);