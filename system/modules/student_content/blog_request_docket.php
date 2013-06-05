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
 * Representation of a request for a new blog and/or associated user account.
 */
class Teachblog_Blog_Request_Docket {
    /**
     * If a user account is requested (if not the submitting_user property should be a non-zero valid user ID).
     *
     * @var bool
     */
    public $account_requested = false;

    /**
     * Requested new account username (will be inspected only if account_requested === true).
     *
     * @var string
     */
    public $account_username = '';

    /**
     * Requested new account password (will be inspected only if account_requested === true)
     *
     * @var string
     */
    public $account_password = '';

    /**
     * Requested new account email address (will be inspected only if account_requested === true). This is completely
     * optional - student users are not normally expected/required to have email addresses for Teachblog purposes.
     *
     * @var string
     */
    public $account_email = '';

    /**
     * The name to be given to the blog.
     *
     * @var string
     */
    public $blog_title = '';

    /**
     * Optional description for the blog.
     *
     * @var string
     */
    public $blog_description = '';

    /**
     * User ID of the signed in user when the request was made (should be left at its default of 0 if made by an
     * unauthenticated user).
     *
     * @var int
     */
    public $submitting_user = 0;
}