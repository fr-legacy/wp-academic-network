<?php
/*
	Teachblog - provides a teacher led collaborative blogging environment
	Copyright (C) 2013 Barry Hughes

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http: *www.gnu.org/licenses/>.
*/

/**
 * Representation of modules - the building blocks of Teachblog which can be turned on and off
 * as required.
 */
class Teachblog_Modules extends Teachblog_Base_Object {
	/**
	 * Contains the list of active modules, each element should be an array containing
	 * three elements: title, description, slug.
	 *
	 * @var array
	 */
	protected $modules = array();


	protected function setup() {
		$this->register_module(
			__('Student Content', 'teachblog'),
			__('Provides tools to let students submit their own content and interact with others', 'teachblog'),
			'student_content'
		);

		do_action('teachblog_register_modules', $this);
	}


	public function register_module($name, $description, $option) {
		$this->modules[] = array($name, $description, $option);
	}


	/**
	 * Checks if the specified module has been enabled.
	 *
	 * @param $slug
	 * @return bool
	 */
	public function is_enabled($slug) {
		return $this->local_setting($slug . '_enabled');
	}


	public function enable($slug) {
		$this->local_setting($slug . '_enabled', true);
	}


	public function disable($slug) {
		$this->local_setting($slug . '_enabled', false);
	}


	public function get_modules() {
		return $this->modules;
	}


	public function get_module_slugs() {
		$array = array();
		foreach ($this->modules as $module) $array[] = $module[2];
		return $array;
	}
}