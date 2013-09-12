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
 * Widget listing all student blogs.
 */
class Teachblog_Widget_Student_Blogs extends WP_Widget
{
	protected $admin;


	public function __construct($id_base = false, $name = false) {
		$this->admin = Teachblog::core()->admin_environment;
		add_shortcode(strtolower(__CLASS__), array($this, 'do_shortcode'));
		$name = (false !== $name) ? $name : __('Student Blog List', 'teachblog');
		parent::__construct($id_base, $name);
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
		$settings['hide_empties'] = ('1' === $settings['hide_empties']) ? true : false;
		$settings['autohide'] = ('1' === $settings['autohide']) ? true : false;

		return $settings;
	}


	public function form($instance) {
		$instance = $this->instance_defaults($instance);

		$this->admin->view('student_content/widget_blogs', array(
			'autohide' => $instance['autohide'],
			'hide_empties' => $instance['hide_empties'],
			'title' => $instance['title'],
			'widget' => $this
		));
	}


	/**
	 * Added the teachblog_my_posts_widget_post_type filter to facilitate special cases where for instance the student
	 * is also granted permission to create regular posts.
	 *
	 * If the author_id is provided but evaluates to 0 we will not display anything.
	 */
	public function do_shortcode($args) {
		// Query for student blogs
		$blogs = get_terms(Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY, array(
			'hide_empty' => $args['hide_empties'] ? true : false
		));

		// Hide if errors / autohide if empty
		if (is_wp_error($blogs)) return '';
		if (empty($blogs) && $args['autohide']) return '';

		$vars = array_merge($args, array('blogs' => $blogs));
		$output = new Teachblog_Template('student_content/widgets/list-blogs', $vars);

		return apply_filters('teachblog_widget_blog_list', $output);
	}


	protected function instance_defaults($instance) {
		return wp_parse_args($instance, array(
			'after_widget' => '</div>',
			'before_widget' => '<div class="teachblog shortcode_widget student_blogs">',
			'after_title' => '</h4>',
			'before_title' => '<h4>',
			'title' => __('My Posts', 'teachblog'),
			'hide_empties' => true,
			'autohide' => true
		));
	}
}