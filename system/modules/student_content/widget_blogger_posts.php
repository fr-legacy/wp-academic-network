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
 * Widget listing posts by a specified user. Builds on Teachblog_Widget_My_Posts.
 */
class Teachblog_Widget_Blogger_Posts extends Teachblog_Widget_My_Posts
{
	public function __construct() {
		parent::__construct(false, __('Individual Student Blog Posts', 'teachblog'));
	}


	/**
	 * @param array $settings
	 * @param array $prev_settings
	 * @return array|void
	 */
	public function update($settings, $prev_settings) {
		$settings = parent::update($settings, $prev_settings);

		// Author choice: sanity check
		$user = Teachblog_Blogger::load($settings['author_id']);
		if ($user->is_student_user()) $settings['author_id'] = (int) $settings['author_id'];

		return $settings;
	}


	public function form($instance) {
		$instance = $this->instance_defaults($instance);

		$this->admin->view('student_content/widget_blogger_posts', array(
			'autohide' => $instance['autohide'],
			'author_id' => $instance['author_id'],
			'bloggers' => $this->get_blogger_list(),
			'show' => $instance['show'],
			'title' => $instance['title'],
			'widget' => $this
		));
	}


	protected function get_blogger_list() {
		return Teachblog_Student_User::list_users(true);
	}


	protected function instance_defaults($instance) {
		return wp_parse_args($instance, array(
			'after_widget' => '</div>',
			'before_widget' => '<div class="teachblog shortcode_widget blogger_posts">',
			'after_title' => '</h4>',
			'before_title' => '<h4>',
			'title' => __('My Posts', 'teachblog'),
			'show' => 5,
			'autohide' => true,
			'author_id' => 0
		));
	}
}