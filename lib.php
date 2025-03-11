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
 * Plugin functions for the local_recibeexamen plugin.
 *
 * @package   local_recibeexamen
 * @copyright 2025, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');

class repository_pluginname extends repository {
	/**
	 * Get file listing.
	 *
	 * This is a mandatory method for any repository.
	 *
	 * See repository::get_listing() for details.
	 *
	 * @param string $encodedpath
	 * @param string $page
	 * @return array the list of files, including meta information
	 */
	public function get_listing($encodedpath = '', $page = '') {
		// This methods
		return array('list' => []);
	}

	/**
	 * Is this repository used to browse moodle files?
	 *
	 * @return boolean
	 */
	public function has_moodle_files() {
		return true;
	}

	/**
	 * Tells how the file can be picked from this repository.
	 *
	 * @return int
	 */
	public function supported_returntypes() {
		return FILE_INTERNAL | FILE_REFERENCE;
	}

	/**
	 * Which return type should be selected by default.
	 *
	 * @return int
	 */
	public function default_returntype() {
		return FILE_INTERNAL;
	}


	/**
	 * Optional method for searching files in the repository.
	 *
	 * @param string $search
	 * @param int $page
	 * @return array the list of found files.
	 */
	public function search($search, $page = 0) {
		$ret = [];
		$ret['nologin'] = true;
		// The found files list.
		$ret['list'] = [];
		return $ret;
	}
}
