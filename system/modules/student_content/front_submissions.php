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
 * Handles initial routing and processing of submissions from the front editor.
 */
class Teachblog_Front_Submissions extends Teachblog_Base_Object {
	/**
	 * @var Teachblog_Blogger
	 */
	protected $user;

	/**
	 * Blogs (term IDs) to which submitted content should be posted.
	 *
	 * @var array
	 */
	protected $blogs = array();

	/**
	 * Fields representing the post (title, content etc).
	 *
	 * @var array
	 */
	protected $postdata = array();

	/**
	 * Contains confirmation, warning and error notices to present to the end user.
	 *
	 * @var array
	 */
	public $notices = array();

	const NOTICES = 'notices';
	const WARNINGS = 'warnings';
	const BAD_WARNINGS = 'bad_warnings'; // Updates/submissions will not be accepted if a bad warning is set


	/**
	 * Checks if a front end submission has been made, ensures basic security checks out then processes the submission
	 * further if so.
	 */
	protected function setup() {
		if (Teachblog_Form::is_posted('submit-teachblog-post') and $this->submission_sanity_checks())
			$this->start_processing();
	}


	/**
	 * Performs a nonce check and confirms the current user is assigned to a blog (by extension, we have an
	 * authenticated user making the request).
	 *
	 * @return bool
	 */
	protected function submission_sanity_checks() {
		if (!Teachblog_Form::is_posted('teachblog_check') or !wp_verify_nonce($_POST['teachblog_check'], 'teachblog_front_editor'))
			return false;

		if (!Teachblog_Blogger::current_user()->has_blog())
			return false;

		return true;
	}


	/**
	 * Initiates processing of the post submission.
	 */
	protected function start_processing() {
		$this->user = Teachblog_Blogger::current_user();
		$this->blogs = $this->get_target_blog(); // Blogs the post will be assigned to (can be more than one)

		// Build and check out the posted fields
		$this->build_postdata_array();
		if (isset($this->notices[self::BAD_WARNINGS])) return false;

		$this->create_or_update();
	}


	/**
	 * Returns the target blog(s) for the submission as an array of ints.
	 *
	 * If a particular blog is nominated then a check is made to ensure the user is assigned to same. If no blog is
	 * nominated then if the user is assigned to a solitary blog that will be assumed.
	 *
	 * In other cases, bool false is returned.
	 *
	 * @return array|bool
	 */
	protected function get_target_blog() {
		if (!$this->user->loaded or !$this->user->has_blog()) {
			$this->add_bad_warning(__('You do not have any active blogs &ndash; please seek help from a teacher or '
				.'from an administrator.', 'teachblog'));
			return false;
		}

		if (Teachblog_Form::is_posted('assign_to')) return (array) $this->confirm_nominated_blog();
		else return (array) $this->user_solitary_blog();
	}


	/**
	 * Checks the value of the posted assign_to variable (the nominated blog(s) for the submission). If the user is
	 * assigned to it/them then the blog (term) IDs are returned, else returns bool false.
	 *
	 * @return array|bool
	 */
	protected function confirm_nominated_blog() {
		$blogs = $this->user->get_blog_ids();
		if (!$blogs or empty($blogs)) return false;

		$possibilities = (array) $_POST['assign_to'];
		$actionable = array_intersect($blogs, $possibilities);

		return (empty($actionable)) ? false : $actionable;
	}


	/**
	 * Checks if the user is assigned to one blog and one blog only and returns that (term) ID, otherwise returns bool
	 * false.
	 *
	 * @return int|false
	 */
	protected function user_solitary_blog() {
		$blogs = $this->user->get_blog_ids();
		if (count($blogs) === 1) return array_pop($blogs);
		return false;
	}


	/**
	 * We build an array to use with wp_insert_post() or wp_update_post(). The post ID is determined elsewhere if
	 * appropriate.
	 *
	 * The title is passed through wp_strip_all_tags() as that is currently necessary, whereas the post content is
	 * automatically sanitized at the correct level by the relevant WP insert/update function.
	 */
	protected function build_postdata_array() {
		$title = Teachblog_Form::is_posted('title') ? wp_strip_all_tags(trim($_POST['title'])) : '';
		$content = Teachblog_form::is_posted('teachblog-front-editor') ? trim($_POST['teachblog-front-editor']) : '';

		if (empty($title)) $this->add_bad_warning(__('The title must not be empty!', 'teachblog'));
		if (empty($content)) $this->add_bad_warning(__('You must provide some content!', 'teachblog'));

		$postdata = array(
			'post_title' => $title,
			'post_content' => $content,
			'post_status' => $this->user->default_submit_status(),
			'post_author' => $this->user->get_user_id(),
			'post_type' => Teachblog_Student_Content::TEACHBLOG_POST
		);

		$this->postdata = apply_filters('teachblog_submission_postdata', $postdata);
	}


	/**
	 * Creates or updates the blog post.
	 */
	protected function create_or_update() {
		if (Teachblog_Form::is_posted('id') and $this->user->load_post(absint($_POST['id'])))
			$this->postdata['ID'] = $_POST['id'];

		$post_id = wp_insert_post($this->postdata);

		// Failed to create/update?
		if ($post_id == 0) {
			$this->add_bad_warning(__('The post could not be saved. Please try again or seek further advice from a '
				.'teacher or administrator.', 'teachblog'));
			return;
		}

		$this->assign_to_blogs($post_id);
		$this->add_notice(__('Your post was successfully submitted.', 'teachblog'));
	}


	protected function assign_to_blogs($post_id) {
		foreach ($this->blogs as $blog_id) {
			wp_set_post_terms($post_id, $blog_id, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY,
				apply_filters('teachblog_maintain_existing_blog_relationships', true));
		}
	}


	protected function add_notice($message) {
		$this->notices[self::NOTICES][] = $message;
	}


	protected function add_warning($message) {
		$this->notices[self::WARNINGS][] = $message;
	}


	protected function add_bad_warning($message) {
		$this->notices[self::BAD_WARNINGS][] = $message;
	}
}