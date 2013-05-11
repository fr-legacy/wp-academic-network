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
 * Allows posts to be created and edited from the front end of the website (so that students
 * need not login to the dashboard to participate).
 */
class Teachblog_Front_Editor extends Teachblog_Base_Object {
	protected $id;
	protected $post = false;

	/**
	 * @var Teachblog_Blogger
	 */
	protected $owner;

	protected $actions = array(
		'wp_enqueue_scripts' => 'enqueue_styles'
	);


	protected function setup() {
		add_shortcode('teachblog_editor', array($this, 'public_editor'));
	}


	public function enqueue_styles() {
		wp_enqueue_style('teachblog_front_editor', $this->system->url.'assets/front-editor.css');
	}


	public function public_editor() {
		$this->owner = Teachblog_Blogger::current_user();
		if ($this->owner->has_blog()) return $this->show_editor();
		else return $this->blog_not_setup();
	}


	protected function show_editor() {
		$this->load_current_post();
		list($origin, $origin_hash) = $this->get_container_details();

		$vars = array(
			'assignable_blogs' => Teachblog_Blogger::current_user()->get_assigned_blog_list(),
			'current_blog' => null,
			'title' => $this->get_post_title(),
			'content' => $this->get_post_content(),
			'status' => $this->post_status_array(),
			'notices' => $this->get_editor_notices(),
			'originating_post' => $origin,
			'originating_hash' => $origin_hash
		);

		if ($this->post) $vars['id'] = $this->post->ID;

		return new Teachblog_Template('editor', $vars);
	}


	protected function load_current_post() {
		if (isset($_REQUEST['id']))
			$this->post = $this->owner->load_post(absint($_REQUEST['id']));
	}


	protected function post_status_array() {
		if (!$this->post) $status = array('new', _x('New!', 'post-status', 'teachblog'));
		else switch ($this->post->post_status) {
			case 'publish': $status = array('live', _x('Published', 'post-status', 'teachblog')); break;
			case 'pending': $status = array('pending', _x('Awaiting Approval', 'post-status', 'teachblog')); break;
			case 'draft': $status = array('draft', _x('Draft', 'post-status', 'teachblog')); break;
		}
		return apply_filters('teachblog_front_editor_post_status', $status);
	}


	protected function get_post_title() {
		if (Teachblog_Form::is_posted('title')) return esc_attr($_POST['title']);
		if (is_object($this->post) and isset($this->post->post_title)) return $this->post->post_title;
		return apply_filters('teachblog_default_post_title', '');
	}


	protected function get_post_content() {
		if (Teachblog_Form::is_posted('teachblog-front-editor')) return esc_attr($_POST['teachblog-front-editor']);
		if (is_object($this->post) and isset($this->post->post_content)) return $this->post->post_content;
		return apply_filters('teachblog_default_post_content', '');
	}


	protected function blog_not_setup() {
		$message = new Teachblog_Template('inform_no_blog', array(
			'signed_in' => (get_current_user_id() > 0)
		));

		return apply_filters('teachblog_editor_no_blog_available', $message);
	}


	protected function get_editor_notices() {
		return (array) $this->system->student_content->front_submissions->notices;
	}


	protected function get_container_details() {
		global $post;

		$post_id = (is_object($post) and isset($post->ID)) ? (int) $post->ID : '';
		$hash = hash('MD5', $post_id . NONCE_KEY);

		return array($post_id, $hash);
	}
}