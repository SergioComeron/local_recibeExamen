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

namespace local_recibeexamen;

defined('MOODLE_INTERNAL') || die();

/**
 * Almacenamiento de los PDF subidos en cola mediante el File API de Moodle.
 *
 * El PDF se guarda en el pool de archivos persistente (no en moodledata/temp,
 * que la limpieza de temporales puede borrar), asociado a la entrada de cola
 * mediante el itemid. La tarea adhoc lo recupera de forma fiable aunque se
 * ejecute mucho después o se reintente.
 *
 * @package   local_recibeexamen
 * @copyright 2026, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_files {

    /** @var string Componente propietario de los archivos. */
    const COMPONENT = 'local_recibeexamen';

    /** @var string Área de archivos de la cola. */
    const FILEAREA = 'examqueue';

    /**
     * Guarda un fichero del disco en el área de la cola para una entrada.
     *
     * @param int $queueid ID de la entrada en local_recibeexamen_queue.
     * @param string $pathname Ruta del fichero de origen (p. ej. la subida temporal).
     * @param string $filename Nombre con el que se almacena el fichero.
     * @return \stored_file
     */
    public static function store(int $queueid, string $pathname, string $filename): \stored_file {
        $fs = get_file_storage();
        return $fs->create_file_from_pathname(self::file_record($queueid, $filename), $pathname);
    }

    /**
     * Recupera el fichero almacenado para una entrada de cola, si existe.
     *
     * @param int $queueid ID de la entrada en local_recibeexamen_queue.
     * @return \stored_file|null
     */
    public static function get(int $queueid): ?\stored_file {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            \context_system::instance()->id,
            self::COMPONENT,
            self::FILEAREA,
            $queueid,
            'itemid, filepath, filename',
            false
        );
        $file = reset($files);
        return $file ?: null;
    }

    /**
     * Elimina los ficheros de la cola asociados a una entrada.
     *
     * @param int $queueid ID de la entrada en local_recibeexamen_queue.
     * @return void
     */
    public static function delete(int $queueid): void {
        $fs = get_file_storage();
        $fs->delete_area_files(
            \context_system::instance()->id,
            self::COMPONENT,
            self::FILEAREA,
            $queueid
        );
    }

    /**
     * Construye el file record del área de la cola.
     *
     * @param int $queueid ID de la entrada en local_recibeexamen_queue.
     * @param string $filename Nombre del fichero.
     * @return array
     */
    protected static function file_record(int $queueid, string $filename): array {
        return [
            'contextid' => \context_system::instance()->id,
            'component' => self::COMPONENT,
            'filearea'  => self::FILEAREA,
            'itemid'    => $queueid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
    }
}
