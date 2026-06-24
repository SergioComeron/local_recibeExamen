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
 * PHPUnit tests for local_recibeexamen\queue_files.
 *
 * @package   local_recibeexamen
 * @copyright 2026, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recibeexamen;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_recibeexamen\queue_files
 */
class queue_files_test extends \advanced_testcase {

    /**
     * Crea un PDF de prueba en disco y devuelve su ruta.
     *
     * @param string $content Contenido del fichero.
     * @return string Ruta absoluta del fichero creado.
     */
    private function make_source_file(string $content): string {
        $dir = make_request_directory();
        $path = $dir . '/source.pdf';
        file_put_contents($path, $content);
        return $path;
    }

    public function test_store_and_get(): void {
        $this->resetAfterTest();

        $queueid = 4242;
        $content = '%PDF-1.4 contenido de prueba';
        $source = $this->make_source_file($content);

        $stored = queue_files::store($queueid, $source, 'examen.pdf');

        $this->assertInstanceOf(\stored_file::class, $stored);
        $this->assertSame('examen.pdf', $stored->get_filename());
        $this->assertSame(queue_files::COMPONENT, $stored->get_component());
        $this->assertSame(queue_files::FILEAREA, $stored->get_filearea());
        $this->assertSame($queueid, (int) $stored->get_itemid());

        $fetched = queue_files::get($queueid);
        $this->assertInstanceOf(\stored_file::class, $fetched);
        $this->assertSame('examen.pdf', $fetched->get_filename());
        $this->assertSame($content, $fetched->get_content());
    }

    public function test_get_returns_null_when_empty(): void {
        $this->resetAfterTest();

        $this->assertNull(queue_files::get(999999));
    }

    public function test_delete_removes_file(): void {
        $this->resetAfterTest();

        $queueid = 777;
        $source = $this->make_source_file('contenido');
        queue_files::store($queueid, $source, 'borrar.pdf');

        $this->assertNotNull(queue_files::get($queueid));

        queue_files::delete($queueid);

        $this->assertNull(queue_files::get($queueid));
    }

    public function test_files_are_isolated_per_queue_entry(): void {
        $this->resetAfterTest();

        queue_files::store(1, $this->make_source_file('uno'), 'a.pdf');
        queue_files::store(2, $this->make_source_file('dos'), 'b.pdf');

        $this->assertSame('uno', queue_files::get(1)->get_content());
        $this->assertSame('dos', queue_files::get(2)->get_content());

        queue_files::delete(1);

        $this->assertNull(queue_files::get(1));
        $this->assertNotNull(queue_files::get(2));
    }
}
