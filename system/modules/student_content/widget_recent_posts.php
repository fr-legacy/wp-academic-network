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
 * Widget listing recent posts from across all student blogs.
 */
class Teachblog_Widget_Recent_Posts extends Teachblog_Widget_My_Posts
{
	public function __construct() {
		parent::__construct(false, __('Recent Student Posts', 'teachblog'));
	}


	public function form($instance) {
		$instance = $this->instance_defaults($instance);

		$this->admin->view('student_content/widget_recent_blog_posts', array(
			'autohide' => $instance['autohide'],
			'show' => $instance['show'],
			'title' => $instance['title'],
			'widget' => $this
		));
	}


	/**
	 * Added the teachblog_my_posts_widget_post_type to facilitate special cases where for instance the student
	 * is also granted permission to create regular posts.
	 *
	 * If the author_id is provided but evaluates to 0 we will not display anything.
	 */
	public function do_shortcode($args) {
		// Query for requested posts
		$query = new WP_Query(array(
			'posts_per_page' => (int) $args['show'],
			'post_type' => apply_filters('teachblog_my_posts_widget_post_type', Teachblog_Student_Content::TEACHBLOG_POST)
		));

		// Auto-hide?
		if (0 === $query->post_count && $args['autohide']) return '';

		$vars = array_merge($args, array('query' => $query));
		$output = new Teachblog_Template('student_content/widgets/my-blog-posts', $vars);

		wp_reset_postdata(); // Cleanup
		return apply_filters('teachblog_widget_recent_blog_posts', $output);
	}
}