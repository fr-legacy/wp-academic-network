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
	 * @var WP_User
	 */
	protected $user;


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
		$student_blogs = $this->get_target_blog();
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
		if (!$this->user->loaded) return false;
		if (!$this->user->has_blog()) return false;

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
}