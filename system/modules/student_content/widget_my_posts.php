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
 * Widget listing posts by the currently authenticated user (if they do indeed have a blog/blog posts).
 */
class Teachblog_Widget_My_Posts extends WP_Widget {
	protected $admin;


	public function __construct() {
		$this->admin = Teachblog::core()->admin_environment;
		parent::__construct(false, __('My Student Blog Posts', 'teachblog'));
	}


	/**
	 * @param array $args
	 * @param array $instance
	 * @todo
	 */
	public function widget($args, $instance) { }


	/**
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 * @todo
	 */
	public function update($new_instance, $old_instance) { }


	public function form($instance) {
		$this->admin->view('student_content/widget_my_posts', array(
			'id' => $instance['id'],
			'title' => $instance['title']
		));
	}
}