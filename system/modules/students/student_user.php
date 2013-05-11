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
 * A student user type, which can be used by other modules.
 */
class Teachblog_Student_User extends Teachblog_Base_Object {
	const ROLE = 'teachblog_student_user';


	public function setup() {
		if ($this->role_undefined()) $this->define_role();
	}


	/**
	 * Reports whether the student user role has been defined or not.
	 *
	 * @return bool
	 */
	protected function role_undefined() {
		return !is_a(get_role(self::ROLE), 'WP_Role');
	}


	/**
	 * Defines the student user role and its default capabilities.
	 */
	protected function define_role() {
		$capabilities = apply_filters('teachblog_student_user_capabilities', array(
			'delete_posts',
			'edit_posts',
			'read'
		));

		add_role(self::ROLE, _x('Student User', 'user-type', 'teachblog'), $capabilities);
	}


	/**
	 * Returns a list of student user accounts as an array of objects (in alphabetical order by login field).
	 *
	 * @return array
	 */
	public static function list_users() {
		return (array)get_users(array('role' => self::ROLE, 'orderby' => 'login', 'order' => 'ASC'));
	}
}