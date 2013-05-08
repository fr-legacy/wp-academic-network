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
	public $loaded = false;


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
	 * Returns the user object(s) assigned to a blog or else bool false if no user(s) have been assigned.
	 *
	 * This could be accomplished with a WP user query (and a user meta element) however for backwards compatibility
	 * with WP 3.4 a direct query is being used.
	 *
	 * @param $blog_term_id
	 * @return bool|WP_User
	 */
	public static function get_assigned_users($blog_term_id) {
		global $wpdb;
		$local_assignments = self::DOMAIN.'_assigned_blogs_'.get_current_blog_id();

		return $wpdb->get_col($wpdb->prepare(
			"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %d",
			$local_assignments, $blog_term_id));
	}


	/**
	 * Unassigns all users currently assigned to the specified blog.
	 *
	 * @param $blog_term_id
	 */
	public static function unassign_all($blog_term_id) {
		$users = (array) self::get_assigned_users($blog_term_id);

		foreach ($users as $user) {
			$user = Teachblog_Blogger::load($user);
			$user->unassign_from($blog_term_id);
		}
	}



	/**
	 * Assigns the current user (if loaded) to the specified blog (if it exists).
	 *
	 * This assumes that the user can be assigned to multiple blogs, however if optional parameter $make_exclusive is
	 * passed as true then all other assignments will be cleared.
	 *
	 * @param $blog_id
	 * @param bool $make_exclusive
	 * @return bool
	 */
	public function assign_to_blog($blog_id, $make_exclusive = false) {
		if (!$this->loaded or !$this->is_valid_blog($blog_id)) return false;

		if ($make_exclusive) $this->unassign_from_all();
		return add_user_meta($this->user->ID, self::DOMAIN.'_assigned_blogs_'.get_current_blog_id(), $blog_id);
	}


	/**
	 * Unassigns the user from the specified blog.
	 *
	 * @param $blog_id
	 * @return bool
	 */
	public function unassign_from($blog_id) {
		if (!$this->loaded or !$this->is_valid_blog($blog_id)) return false;

		return delete_user_meta($this->user->ID, self::DOMAIN.'_assigned_blogs_'.get_current_blog_id(), $blog_id);
	}


	/**
	 * Unassigns the blogger from all and any blogs to which they are currently assigned.
	 */
	public function unassign_from_all() {
		if (!$this->loaded) return false;
		return delete_user_meta($this->user->ID, self::DOMAIN.'_assigned_blogs_'.get_current_blog_id());
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