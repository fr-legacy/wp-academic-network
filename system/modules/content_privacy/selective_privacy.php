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
 * Implements a privacy model where student content is, wherever practicable
 * to do so, hidden from unauthenticated users.
 */
class Teachblog_Selective_Privacy extends Teachblog_Base_Object
{
	protected $authenticated = true;


	protected function setup() {
		if (is_admin() || defined('DOING_AJAX')) return;
		if (!is_user_logged_in()) {
			$this->authenticated = false;
			add_filter('teachblog_student_content_type', array($this, 'make_student_content_private'));
		}
	}


	public function make_student_content_private($properties) {
		if ($this->authenticated) return $properties;
		$properties['public'] = false;
		return $properties;
	}
}