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
 * Tracks requests for new blogs (and associated user accounts).
 */
class Teachblog_Blog_Requests extends Teachblog_Base_Object {
    const POST_TYPE = 'teachblog_blog_req';

    protected $actions = array(
        'teachblog_launch' => 'admin_actions'
    );

    /**
     * @var Teachblog_Blog_Request_Form
     */
    public $blog_request_form;

    /**
     * @var Teachblog_Blog_Request_Submissions
     */
    public $blog_request_submissions;

    /**
     * @var Teachblog_Blog_Request_Docket
     */
    protected $docket;

    protected $approval_errors = array();


    protected function setup() {
        $this->register_post_type();
        $this->blog_request_form = new Teachblog_Blog_Request_Form;
        $this->blog_request_submissions = new Teachblog_Blog_Request_Submissions;
    }


    /**
     * Registers a post type to store blog/account requests.
     *
     * The post type is not intended to be exposed publicly, it's really just a vehicle to store and process requests
     * from students for a blog and/or account and leverages some of the UI goodness WordPress generates admin-side.
     *
     * The publicly_queryable and exclude_from_search properties are explicitly set (even though they both effectively
     * "inherit" from public) simply to afford an extra safeguard if the post type is modified after it is initially
     * registered here, in which case those specific properties would both need to be overriden, not just the public
     * property.
     *
     * We're also flouting convention with the use of the promote_users capability (instead of create_users) as A) we
     * want Teachblog to be a light-as-possible-skin over WordPress B) an administrator on a MU install will not have
     * the create_users capability by default and C) promote_users isn't necessarily a terrible fit even if it's a
     * creative interpretation.
     */
    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
			'label' => _x('New Blog Requests', 'blog-requests', 'teachblog'),
			'labels' => array(
				'singular_name' => _x('New Blog Request', 'blog-requests-singular', 'teachblog'),
				'not_found' => __('There are no new blog requests waiting to be processed.', 'teachblog'),
				'edit_item' => _x('Blog Request', 'blog-request-edit-text', 'teachblog')),
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => Teachblog_Student_Content::TEACHBLOG_MENU_SLUG,
			'capabilities' => array('promote_users'),
			'map_meta_cap' => true,
			'register_meta_box_cb' => array($this, 'setup_meta_boxes')
        ));

		remove_post_type_support(self::POST_TYPE, 'title');
		remove_post_type_support(self::POST_TYPE, 'editor');
    }


    public function setup_meta_boxes() {
        add_meta_box('acct_request_summary',
            _x('Request Details', 'account-requests', 'teachblog'),
            array($this, 'summary_meta_box'),
            self::POST_TYPE,
            'normal'
        );

        add_meta_box('acct_request_actions',
            _x('Actions', 'account-requests', 'teachblog'),
            array($this, 'actions_meta_box'),
            self::POST_TYPE,
            'side'
        );

        remove_meta_box('submitdiv', self::POST_TYPE, 'side');
    }


    public function summary_meta_box($post) {
        $this->load_docket_object($post);
        $this->admin->view('blog_requests/summary_meta_box', array(
            'blog_title' => $this->docket_or_sticky_val('blog_title'),
            'blog_description' => $this->docket_or_sticky_val('blog_description'),
            'account_requested' => (bool) $this->docket_or_sticky_val('account_requested'),
            'account_username' => $this->docket_or_sticky_val('account_username'),
            'user_summary' => $this->existing_user_summary()
        ));
    }


    protected function existing_user_summary() {
        $user = get_user_by('id', (int) $this->docket->submitting_user);
        return $user ? $user->user_login.' (#'.$user->ID.')' : __('Error &mdash; Unknown User', 'teachblog');
    }

    public function actions_meta_box($post) {
        $this->admin->view('blog_requests/actions_meta_box', array(
            'request_id' => $post->ID
        ));
    }


    protected function docket_or_sticky_val($field) {
        if (Teachblog_Form::is_posted($field)) return $_POST[$field];
        elseif (isset($this->docket->$field)) return $this->docket->$field;
        return '';
    }


    /**
     * Loads the request docket and populates $this->docket (returning bool true if so or bool false if the operation
     * failed).
     *
     * The docket class definition will be preloaded before unserializing if necessary. The unserialize process uses
     * error suppression to avoid errors where the data to be unserialized has been corrupted etc.
     *
     * @param $post
     * @return bool
     */
    protected function load_docket_object($post) {
        if (is_a($this->docket, 'Teachblog_Blog_Request_Docket')) return true;
        if (!is_object($post)) return false;
        if (!class_exists('Teachblog_Blog_Request_Docket')) Teachblog::class_loader('Teachblog_Blog_Request_Docket');
        return (bool) ($this->docket = @unserialize(base64_decode($post->post_content)));
    }


    public function new_request(Teachblog_Blog_Request_Docket $request) {
        wp_insert_post(array(
            'post_title' => $this->request_log_title($request),
            'post_content' => base64_encode(serialize($request)),
            'post_type' => self::POST_TYPE,
            'post_status' => 'pending' // partly a reflection of the true state of things, but also to avoid 'draft' showing in the admin UI
        ));
    }


    /**
     * Forms a post title to help provide a meaningful identity to each request, so admins/teachers don't have to
     * open up each pending request before dealing with them.
     *
     * @param Teachblog_Blog_Request_Docket $request
     * @return string
     */
    protected function request_log_title(Teachblog_Blog_Request_Docket $request) {
        $blog_name = trim($request->blog_title);
        $blog_name = empty($blog_name) ? __('Untitled Blog', 'teachblog') : $blog_name;

        if ($request->account_requested) {
            $user_name = trim($request->account_username);
            $user_name = empty($user_name) ?  __('Anonymous', 'teachblog') : $user_name;
        }
        elseif ($request->submitting_user > 0) {
            $user = get_user_by('id', $request->submitting_user);
            $user_name = $user->user_login;
        }
        if (empty($user_name)) $user_name = __('Anonymous', 'teachblog');

        $initial_chunk = Teachblog_Strings::truncate("$blog_name &ndash; $user_name", 65);
        $date = date(get_option('date_format', 'Y-m-d'));
        return esc_html("$initial_chunk ($date)");
    }


    /**
     * Listens for approval/trash actions when the request is reviewed and takes appropriate action from there.
     */
    public function admin_actions() {
        add_filter('post_updated_messages', array($this, 'add_notices'));

        if (!is_admin() or !Teachblog_Form::are_posted('teachblog_approval', 'request_id')) return;
        if (!wp_verify_nonce($_POST['teachblog_approval'], 'teachblog_account_request') or !current_user_can('manage_users')) return;

        if (Teachblog_Form::is_posted('trash-request')) $this->trash_request($_POST['request_id']);
        if (Teachblog_Form::is_posted('approve-request')) $this->approve_request($_POST['request_id']);
    }


    protected function trash_request($request_id) {
        wp_delete_post($request_id);
        wp_redirect(get_admin_url(null, 'edit.php?post_type='.Teachblog_Blog_Requests::POST_TYPE));
        exit();
    }


    /**
     * Approves the request (if everything checks out ... if there are problems they should be displayed in any case
     * when the screen reloads.
     *
     * @param $request_id
     */
    protected function approve_request($request_id) {
        if (!$this->load_docket_object(get_post($request_id))) return; // Exit if the docket couldn't be loaded

        // Make changes sticky and check for errors
        $this->persist_changes_during_approval($request_id);
        $this->do_approval_checks();
        if (!empty($this->approval_errors)) return;

        // Build the blog/user account and clear the request
        if ($this->process_request($request_id)) {
            wp_redirect(get_admin_url(null, 'edit.php?post_type='.Teachblog_Blog_Requests::POST_TYPE));
            exit();
        }
    }


    /**
     * The admin/teacher should be able to tweak the submitted request and have those changes persist even if they
     * are denied for some reason (such as the username already being in use).
     *
     * @param $request_id
     */
    protected function persist_changes_during_approval($request_id) {
        $this->docket->blog_title = sanitize_title($this->docket_or_sticky_val('blog_title'));
        $this->docket->blog_description = wp_kses_post($this->docket_or_sticky_val('blog_description'));

        if ($this->docket->account_requested)
            $this->docket->account_username = sanitize_user($this->docket_or_sticky_val('account_username'));

        wp_update_post(array(
            'ID' => $request_id,
            'post_content' => serialize($this->docket)
        ));
    }


    protected function do_approval_checks() {
        if (is_object($this->docket) and $this->docket->account_requested) {
            $username = trim($this->docket_or_sticky_val('account_username'));

            if (username_exists($username)) $this->approval_errors[] =
                __('requested username is already in use', 'teachblog');

            if ($username !== sanitize_user($username)) $this->approval_errors[] =
                sprintf(__('requested username contains special characters that should not be used in this context &ndash; try: %s', 'teachblog'),
                    sanitize_user($this->docket->account_username));

            if (empty($username)) $this->approval_errors[] =
                __('no username has been provided', 'teachblog');

            $password = trim($this->docket->account_password);
            if (empty($password)) $this->approval_errors[] =
                __('request was submitted with an empty password', 'teachblog');

            if (!empty($this->docket->account_email) and email_exists($this->docket->account_email)) $this->approval_errors[] =
                __('an email was submitted with this request but is already in use', 'teachblog');
        }

        $blog_title = trim($this->docket_or_sticky_val('blog_title'));

        if (empty($blog_title)) $this->approval_errors[] =
            __('The blog title is empty! Please make sure it has a meaningful title.', 'teachblog');
    }


    public function add_notices(array $messages) {
        // Do nothing if we are not dealing with a new blog request!
        global $post;
        if (!isset($post->post_type) or !$post->post_type === self::POST_TYPE) return;

        $this->load_docket_object($post);
        $this->do_approval_checks();

        $message = join(', ', $this->approval_errors);

        if (!empty($message)) {
            $messages[self::POST_TYPE][1000] = __('Please note the following problems: ', 'teachblog') . $message . '.';
            $_GET['message'] = 1000;
        }

        return $messages;
    }


    /**
     * Processes the request and builds the new blog (+user account if required) then deletes the request.
     *
     * @param $request_id
     * @return bool
     */
    protected function process_request($request_id) {
        if ($this->docket->account_requested) {
            $user = Teachblog_Blogger::create_blogger(
                $this->docket->account_username,
                $this->docket->account_password,
                $this->docket->account_email
            );
        }
        else {
            $user = Teachblog_Blogger::load($this->docket->submitting_user);
        }

        $blog_id = $this->system->student_content->create_blog($this->docket->blog_title, $this->docket->blog_description);
        if (false === $blog_id or false === $user) return false;

        $user->assign_to_blog($blog_id);
        wp_delete_post($request_id);
        return true;
    }
}