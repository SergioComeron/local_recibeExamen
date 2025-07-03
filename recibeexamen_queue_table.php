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

/**
 * Tabla para listar registros de local_recibeexamen_queue.
 */
class mod_recibeexamen_queue_table extends flexible_table {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define columnas de la tabla.
        $this->define_columns([
            'id',
            'idusuldap',
            'exacodnum',
            'assnomid1',
            'planomid1',
            'status',
            'filename',
            'fechainicio',
            'fechafin',
            'timecreated',
            'acciones',
        ]);

        // Define cabeceras.
        $this->define_headers([
            'ID',
            'Usuario',
            'Código Examen',
            'Asignatura',
            'Plan',
            'Estado',
            'Archivo',
            'Fecha Inicio',
            'Fecha Fin',
            'Fecha de creación',
            'Acciones',
        ]);

        $this->sortable(true, 'id', SORT_DESC);
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable generalbox');
        $this->pageable(true);
    }

    public function col_acciones($row) {
       $url = new moodle_url('/local/recibeexamen/resend.php', ['id' => $row->id]);
        return html_writer::link($url, '🔁 Reenviar', ['class' => 'btn btn-secondary']);
    }


    /**
     * Convierte cada fila en columnas visibles.
     */
    // public function col_idusuldap($row) {
    //     return $this->get_data_field($row, 'idusuldap');
    // }

    // public function col_exacodnum($row) {
    //     return $this->get_data_field($row, 'exacodnum');
    // }

    // public function col_assnomid1($row) {
    //     return $this->get_data_field($row, 'assnomid1');
    // }

    // public function col_planomid1($row) {
    //     return $this->get_data_field($row, 'planomid1');
    // }

    // public function col_fechainicio($row) {
    //     $value = $this->get_data_field($row, 'fechainicio');
    //     return $value ? $value : '-';
    // }

    // public function col_fechafin($row) {
    //     $value = $this->get_data_field($row, 'fechafin');
    //     return $value ? $value : '-';
    // }

    // public function col_filename($row) {
    //     return property_exists($row, 'filename') ? $row->filename : '-';
    // }

    // public function col_timecreated($row) {
    //     return 'fecha'.userdate($row->timecreated);
    // }

    /**
     * Extrae un campo del JSON `data`.
     */
    // protected function get_data_field($row, $fieldname) {
    //     if (!property_exists($row, 'data')) {
    //         return '-';
    //     }

    //     $data = json_decode($row->data, true);
    //     return isset($data[$fieldname]) ? s($data[$fieldname]) : '-';
    // }
}
