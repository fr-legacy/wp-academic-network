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
	public function widget($args, $instance) {
		$instance = $this->instance_defaults(array_merge($instance, $args));
		echo $this->do_shortcode($instance);
	}


	/**
	 * @param array $settings
	 * @param array $prev_settings
	 * @return array|void
	 */
	public function update($settings, $prev_settings) {
		$settings['show'] = (int) $settings['show'];
		if (-1 > $settings['show']) $settings['show'] = -1;

		$settings['autohide'] = ('1' === $settings['autohide']) ? true : false;

		return $settings;
	}


	public function form($instance) {
		$instance = $this->instance_defaults($instance);

		$this->admin->view('student_content/widget_my_posts', array(
			'autohide' => $instance['autohide'],
			'show' => $instance['show'],
			'title' => $instance['title'],
			'widget' => $this
		));
	}


	/**
	 * Added the teachblog_my_posts_widget_post_type to facilitate special cases where for instance the student
	 * is also granted permission to create regular posts.
	 */
	public function do_shortcode($args) {
		// If the current user is not logged in, or has no blog, hide!
		$blogger = Teachblog_Blogger::current_user();
		if (!$blogger->loaded || !$blogger->has_blog()) return '';

		// Query for requested posts
		$query = new WP_Query(array(
			'author' => $blogger->get_user_id(),
			'posts_per_page' => (int) $args['show'],
			'post_type' => apply_filters('teachblog_my_posts_widget_post_type', Teachblog_Student_Content::TEACHBLOG_POST)
		));

		$vars = array_merge($args, array('query' => $query));
		$output = new Teachblog_Template('student_content/widgets/my-blog-posts', $vars);

		wp_reset_postdata(); // Cleanup
		return apply_filters('teachblog_widget_my_blog_posts', $output);
	}


	protected function instance_defaults($instance) {
		return wp_parse_args($instance, array(
			'after_widget' => '</div>',
			'before_widget' => '<div class="teachblog shortcode_widget my_posts">',
			'after_title' => '</h4>',
			'before_title' => '<h4>',
			'title' => __('My Posts', 'teachblog'),
			'show' => 5,
			'autohide' => true
		));
	}
}