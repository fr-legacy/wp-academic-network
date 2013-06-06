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

    /**
     * @var Teachblog_Blog_Request_Form
     */
    public $blog_request_form;

    /**
     * @var Teachblog_Blog_Request_Submissions
     */
    public $blog_request_submissions;


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
     */
    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'label' => _x('Account Requests', 'blog-requests', 'teachblog'),
            'labels' => array(
                'singular_name' => _x('Account Request', 'blog-requests-singular', 'teachblog'),
                'not_found' => __('There are no new account/blog requests waiting to be processed.', 'teachblog')),
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => Teachblog_Student_Content::TEACHBLOG_MENU_SLUG
        ));
    }


    public function new_request(Teachblog_Blog_Request_Docket $request) {
        wp_insert_post(array(
            'post_title' => $this->request_log_title($request),
            'post_content' => serialize($request),
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
}