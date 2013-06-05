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


    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'label' => _x('Account Requests', 'blog-requests', 'teachblog'),
            'labels' => array(
                'singular_name' => _x('Account Request', 'blog-requests-singular', 'teachblog'),
                'not_found' => __('There are no new account/blog requests waiting to be processed.', 'teachblog')),
            'show_ui' => true,
            'show_in_menu' => Teachblog_Student_Content::TEACHBLOG_MENU_SLUG
        ));
    }

}