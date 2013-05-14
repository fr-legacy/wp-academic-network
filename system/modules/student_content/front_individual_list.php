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
 * Provides a suite of shortcodes to list all (student blog) posts belonging to the current user and to search for and
 * edit same.
 *
 * @todo search shortcode
 * @todo pagination
 */
class Teachblog_Front_Individual_List extends Teachblog_Base_Object {
	protected function setup() {
		add_shortcode('teachblog_blog_list', array($this, 'list_blogs'));
	}


	public function list_blogs() {
		if (!Teachblog_Blogger::current_user()->has_blog()) return $this->no_blogs_to_show();
	}


	protected function no_blogs_to_show() {
		$message = new Teachblog_Template('inform_no_blog', array(
			'signed_in' => (get_current_user_id() > 0)
		));

		return apply_filters('teachblog_editor_no_blog_available', $message);
	}
}