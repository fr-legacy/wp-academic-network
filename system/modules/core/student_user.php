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
		$this->prevent_admin_access();
		$this->optionally_allow_file_upload();
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
	 * Defines the student user role and its default capabilities. They are essentially in line with the defaults for an
     * Author-role user.
	 */
	protected function define_role() {
		$capabilities = apply_filters('teachblog_student_user_capabilities', array(
			'delete_posts',
            'delete_published_posts',
			'edit_posts',
            'edit_published_posts',
            'publish_posts',
			'read',
            'upload_files'
		));

		add_role(self::ROLE, _x('Student User', 'user-type', 'teachblog'), $capabilities);
	}


	/**
	 * Ordinarily student users will be of the subscriber role and won't have the upload_files capability. This method
	 * facilitates dynamically adding it for applicable users and provides a filter - teachblog_grant_file_upload_cap -
	 * that makes it easy to deny this privilege.
	 */
	protected function optionally_allow_file_upload() {
		$blogger = Teachblog_Blogger::current_user();

		if (!$blogger->is_student_user()) return;
		if (!apply_filters('teachblog_grant_file_upload_cap', true, $blogger)) return;

		add_filter('user_has_cap', array($this, 'add_upload_files_cap'), 10, 2);
	}


	public function add_upload_files_cap($cap_list, $cap) {
		if (isset($cap_list[Teachblog_Student_User::ROLE]) && in_array('upload_files', $cap))
			if (!in_array('upload_files', $cap_list, true)) $cap_list['upload_files'] = 'upload_files';

		return $cap_list;
	}


	/**
	 * Returns a list of student user accounts as an array of objects (in alphabetical order by login field).
	 *
	 * @param bool $current_blog_only
	 * @return array
	 */
	public static function list_users($current_blog_only = false) {
		$params = array('role' => self::ROLE, 'orderby' => 'login', 'order' => 'ASC');
		if ($current_blog_only) $params['blog_id'] = get_current_blog_id();
		return (array) get_users($params);
	}


	/**
	 * If a student user attempt to access the admin pages (dashboard etc) then they will be redirected to the public
	 * site.
	 */
	protected function prevent_admin_access() {
		// Do not interfere with users other than student users
		$user = wp_get_current_user();
		if (!is_a($user, 'WP_User') or !in_array(self::ROLE, $user->roles)) return;

		$prevent_access = ($this->local_setting('allow_student_admin_access') !== true);
		$prevent_access = apply_filters('teachblog_prevent_student_admin_access', $prevent_access);

		// We check that DOING_AJAX is undefined as many front end operations work through the admin-ajax controller,
		// so we don't want to interrupt their flow
		if ($prevent_access and is_admin() and !defined('DOING_AJAX')) {
			wp_redirect(apply_filters('teachblog_redirect_students_from_admin', home_url()));
			exit();
		}
	}
}