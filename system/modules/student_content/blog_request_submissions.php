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
    protected $actions = array(
        'teachblog_student_content_init' => 'look_for_submissions',
    );

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

    public $success = false;

    const NOTICES = 'notices';
    const WARNINGS = 'warnings';
    const BAD_WARNINGS = 'bad_warnings'; // Updates/submissions will not be accepted if a bad warning is set


    /**
     * Checks if a front end blog request has been made (and processes the submission
     * further if so).
     */
    public function look_for_submissions() {
        if (Teachblog_Form::is_posted('submit-blog-request') and $this->submission_sanity_checks())
            $this->start_processing();

        $this->setup_notices_after_redirect();
    }


    /**
     * Checks for submission states as part of the URL query.
     */
    protected function setup_notices_after_redirect() {
        if (!isset($_GET['success'])) return;
        else $this->add_notice(__('Your request has been submitted!', 'teachblog'));
        $this->success = true;
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
        $this->handle_new_user_element();
        $this->check_for_essentials();
        if (!empty($this->notices[self::BAD_WARNINGS]) or !empty($this->notices[self::WARNINGS])) return false;

        $request = $this->create_request();
        $this->system->student_content->blog_requests->new_request($request);
        $this->add_notice(__('Your request has been submitted!', 'teachblog'));
        $this->success = true;
        $this->redirect_on_success();
    }


    /**
     * Handles requests for a new user to be created and associated with a new blog.
     *
     * If the request comes from an authenticated user it will be declined.
     */
    protected function handle_new_user_element() {
        $logged_in = Teachblog_Blogger::current_user()->get_user_id() !== false;

        // If the username/password are empty and the user is logged in then we'll assume they are requesting an additional blog
        if ($logged_in and Teachblog_Form::are_posted_and_empty('username', 'password_1', 'password_2')) {
            return;
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


    protected function check_for_essentials() {
        $title = Teachblog_Form::is_posted('blog_title') ? trim($_POST['blog_title']) : '';
        if (empty($title)) $this->add_warning(__('Please provide a blog title!', 'teachblog'));
    }

    /**
     * Builds a new account/blog request docket from the submitted post data.
     *
     * @return Teachblog_Blog_Request_Docket
     */
    protected function create_request() {
        $request = new Teachblog_Blog_Request_Docket;

        if (Teachblog_Form::are_posted('username', 'password_1')) {
            $request->account_requested = true;
            $request->account_username = $_POST['username'];
            $request->account_password = $_POST['password_1'];
            if (Teachblog_Form::is_posted('email')) $request->account_email = $_POST['email'];
        }

        if (Teachblog_Form::are_posted('blog_title', 'blog_description')) {
            $request->blog_title = $_POST['blog_title'];
            $request->blog_description = $_POST['blog_description'];
        }

        $current_user = Teachblog_Blogger::current_user()->get_user_id();
        $request->submitting_user = (is_int($current_user) and $current_user > 0) ? $current_user : 0;

        return $request;
    }


    /**
     * Once a successful submission is made this method attempts to perform a redirect back to the submission form page.
     *
     * It's assumed the request was made from a shortcode in a page/post and that that is where the user should be
     * redirected to - this partly mitigates the risk of multiple submissions of the same request by a student hitting
     * refresh/ctrl+R.
     */
    protected function redirect_on_success() {
        $url = $this->get_original_post_url(true);
        $url = apply_filters('teachblog_acct_request_redirect', isset($url) ? $url : '');

        wp_redirect($url);
        exit();
    }


    protected function get_original_post_url($success) {
        if ($_POST['origin_hash'] !== hash('MD5', $_POST['origin'] . NONCE_KEY)) return '';

        $GLOBALS['post'] = get_post($_POST['origin']);
        return Teachblog_Form::post_url($success ? array('success' => 1) : array());
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