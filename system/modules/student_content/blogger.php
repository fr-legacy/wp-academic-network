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
 * Represents a (student blogger) user and facilitates checking permissions etc.
 */
class Teachblog_Blogger extends Teachblog_Base_Object {
	/**
	 * Contains reusable instances.
	 *
	 * @var array
	 */
	protected static $blogger_instances = array();

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Indicates if the user object successfully loaded.
	 *
	 * @var bool
	 */
	protected $loaded = false;


	/**
	 * Returns the blogger object for the current user (if the visitor has authenticated). Effectively an
	 * alias for Teachblog_Blogger::load() where no $id parameter is passed in.
	 *
	 * @return Teachblog_Blogger
	 */
	public static function current_user() {
		return self::load();
	}


	/**
	 * Returns the blogger object for the specified user (or current user if no ID is specified). Otherwise,
	 * returns boolean false.
	 *
	 * Using this method to load a blogger object is normally preferred as it prevents multiple objects from
	 * being created in relation to the same user account. Even so, it is still possible to create objects
	 * directly if preferred.
	 *
	 * @param null $id
	 * @return Teachblog_Blogger
	 */
	public static function load($id = null) {
		if ($id === null) $id = get_current_user_id();
		$id = (int) $id;

		if (isset(self::$blogger_instances[$id])) {
			return self::$blogger_instances[$id];
		}

		$blogger = new self($id);
		self::$blogger_instances[$id] = $blogger;
		return $blogger;
	}


	public function __construct($id = null) {
		parent::__construct();

		if ($id === null) $this->user = wp_get_current_user();
		else $this->user = get_user_by('id', absint($id));

		$this->loaded = (is_object($this->user) and isset($this->user->ID));
	}


	/**
	 * Indicates if the current user has a student blog.
	 *
	 * @return bool
	 */
	public function has_blog() {
		if (!$this->loaded) return false;
		return (is_int($this->get_blog_id())) ? true : false;
	}


	/**
	 * Returns the taxonomy ID for the student blog, if the user is assigned to one, or else boolean false.
	 *
	 * @return bool|int
	 */
	public function get_blog_id() {
		if (!$this->loaded) return false;
		if (!isset($this->user->teachblog_student_blog)) return false;

		$blog_id = (int) $this->user->teachblog_student_blog;
		if ($this-is_valid_blog($blog_id)) return $blog_id;
		return false;
	}


	/**
	 * Checks if $id corresponds to a valid blog term.
	 *
	 * @param $id
	 * @return bool
	 */
	protected function is_valid_blog($id) {
		$term = get_term_by('id', $id, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY);
		return (!$term) ? false : true;
	}
}