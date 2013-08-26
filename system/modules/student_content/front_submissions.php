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

	/**
	 * The submission post status determined during processing.
	 *
	 * @var string
	 */
	protected $post_status = '';

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

		$this->setup_notices_after_redirect();
	}


	/**
	 * Checks for submission states as part of the URL query.
	 */
	protected function setup_notices_after_redirect() {
		if (!isset($_GET['state'])) return;

		switch ($_GET['state']) {
			case 'updated':
				$this->add_notice(__('Success! Your post (and any changes) have been saved.', 'teachblog'));
			break;
			case 'trashed':
				$this->add_warning(__('Your post has been marked for deletion.', 'teachblog'));
			break;
		}
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
		$comments = Teachblog_form::is_posted('allow_comments') ? 'open' : 'closed';

		if (empty($title)) $this->add_bad_warning(__('The title must not be empty!', 'teachblog'));
		if (empty($content)) $this->add_bad_warning(__('You must provide some content!', 'teachblog'));

		$postdata = array(
			'post_title' => $title,
			'post_content' => $content,
			'comment_status' => $comments,
			'post_status' => $this->determine_post_status(),
			'post_author' => $this->user->get_user_id(),
			'post_type' => Teachblog_Student_Content::TEACHBLOG_POST
		);

		$this->postdata = apply_filters('teachblog_submission_postdata', $postdata);
	}


	/**
	 * Creates or updates the blog post.
	 */
	protected function create_or_update() {
        // user->load_post() effectively confirms that they have permission to edit the specified post if set
        // (ie, the post ID can't be an arbitrary post ID)
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
		$this->add_notice(__('Success! Your post (and any changes) have been saved.', 'teachblog'));
		$this->redirect_on_success($post_id);
	}


	protected function assign_to_blogs($post_id) {
		foreach ($this->blogs as $blog_id) {
			wp_set_post_terms($post_id, $blog_id, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY,
				apply_filters('teachblog_maintain_existing_blog_relationships', true));
		}
	}


	/**
	 * Determines the post_status level to use for the current submission.
	 *
	 * @return string
	 */
	protected function determine_post_status() {
		$this->post_status = 'draft'; // If in doubt, save as a draft

		if (Teachblog_Form::is_posted('publish_options'))
			switch ($_POST['publish_options']) {
				case 'save_update': $this->post_status = $this->user->default_submit_status(); break;
				case 'save_draft': $this->post_status = 'draft'; break;
				case 'discard': $this->post_status = 'trash'; break;
			}

		// Allow this behaviour to be modified
		$this->post_status = apply_filters('teachblog_save_submission_post_status', $this->post_status);
		return $this->post_status;
	}


	/**
	 * Once a successful submission is made this method attempts to perform a redirect to the front editor page.
	 *
	 * It's assumed the request was made from a shortcode in a page/post and that that is where the user should be
	 * redirected to (which allows the newly created post ID to be appended in the query and primes the editor form with
	 * the newly created/changed content.
	 *
	 * In other words, in the normal course of events if the editor form is at example.com/editor and that form is used
	 * to make the submission, the user will be taken to example.com/editor?id=100&state=success
	 *
	 * @param $post_id
	 */
	protected function redirect_on_success($post_id) {
		// Origin request to redirect to a specific post?
		if (Teachblog_Form::is_posted('origin') and Teachblog_Form::is_posted('origin_hash'))
			$url = $this->get_original_post_url($post_id);

		$url = apply_filters('teachblog_default_editor_redirect', isset($url) ? $url : '');

		wp_redirect($url);
		exit();
	}


	protected function get_original_post_url($post_id) {
		if ($_POST['origin_hash'] !== hash('MD5', $_POST['origin'] . NONCE_KEY)) return '';

		$GLOBALS['post'] = get_post($_POST['origin']);
		return Teachblog_Form::post_url(array(
			'id' => (int) $post_id,
			'state' => ($this->post_status === 'trash') ? 'trashed' : 'updated'
		));
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