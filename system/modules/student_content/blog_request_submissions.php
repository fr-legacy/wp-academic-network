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
 * Handles initial routing and processing of submissions from the front-end blog request form.
 */
class Teachblog_Blog_Request_Submissions extends Teachblog_Base_Object {
    /**
     * @var Teachblog_Blogger
     */
    protected $user;

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
     * Checks if a front end blog request has been made (and processes the submission
     * further if so).
     */
    protected function setup() {
        if (Teachblog_Form::is_posted('submit-blog-request') and $this->submission_sanity_checks())
            $this->start_processing();

        //$this->setup_notices_after_redirect();
    }


    /**
     * Performs a nonce check.
     *
     * @return bool
     */
    protected function submission_sanity_checks() {
        if (!Teachblog_Form::is_posted('teachblog_check') or !wp_verify_nonce($_POST['teachblog_check'], 'teachblog_blog_request'))
            return false;

        return true;
    }


    protected function start_processing() {
        if (Teachblog_Form::is_posted('username')) $this->handle_new_user_element();
    }


    /**
     * Handles requests for a new user to be created and associated with a new blog.
     *
     * If the request comes from an authenticated user it will be declined.
     */
    protected function handle_new_user_element() {
        if (Teachblog_Blogger::current_user()->is_student_user()) {
            $this->add_bad_warning(__('You are already logged in! You cannot create a new user account.', 'teachblog'));
            return false;
        }

        // Have a username and two passwords been submitted?
        if (!Teachblog_Form::are_posted('username', 'password_1', 'password_2')) {
            $this->add_bad_warning(__('A username and password are required to create a new user account.', 'teachblog'));
            return false;
        }

        // Is the username taken?
        if (username_exists($_POST['username'])) {
            $this->add_bad_warning(__('The username you have requested is already in use, please try something else.',' teachblog'));
            return false;
        }

        // Is password_1 empty?
        if (empty($_POST['password_1'])) {
            $this->add_bad_warning(__('Passwords cannot be empty. Remember that secure passwords keep data safe and prevent embarrassment!', 'teachblog'));
            return false;
        }

        // Check for password mismatches
        if ($_POST['password_1'] !== $_POST['password_2']) {
            $this->add_bad_warning(__('Your passwords must match! Remember that passwords are case sensitive.', 'teachblog'));
            return false;
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