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
		$id = (int)$id;

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
	 * Supplies the user object (if loaded, else bool false).
	 *
	 * @return bool|object|WP_User
	 */
	public function get_user_object() {
		if ($this->loaded) return $this->user;
		return false;
	}


	/**
	 * Supplies the user ID (if the user object was loaded, else bool false).
	 *
	 * @return bool|int
	 */
	public function get_user_id() {
		if ($this->loaded) return $this->user->ID;
		return false;
	}


    /**
     * Returns true if the loaded user account relates to a student user.
     *
     * @return bool
     */
    public function is_student_user() {
        if (!$this->loaded) return false;
        return in_array(Teachblog_Student_User::ROLE, $this->user->roles);
    }


	/**
	 * Loads the requested blog post *if* the current user is assigned to the blog (or one of the blogs) to which it
	 * belongs and if of course the post ID is valid. Otherwise, returns bool false.
	 *
	 * @param $post_id
	 * @return bool|object
	 */
	public function load_post($post_id) {
		$post = get_post($post_id);
		if (!is_a($post, 'WP_Post')) return false;

		$post_belongs_to = wp_get_post_terms($post->ID, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY);
		$user_belongs_to = $this->get_assigned_blog_list();

		foreach ($post_belongs_to as $post_blog)
			foreach ($user_belongs_to as $user_blog_id => $user_blog)
				if ($post_blog->term_id == $user_blog_id) return $post;

		return false;
	}


	/**
	 * Returns a list of all posts in all blogs that the current user participates in.
	 *
	 * @return array
	 */
	public function get_complete_post_list() {
		$posts = array();
		$blog_term_ids = array_values(array_flip($this->get_assigned_blog_list()));

		$query = new WP_Query(array(
			'post_type' => Teachblog_Student_Content::TEACHBLOG_POST,
			'tax_query' => array(array(
				'taxonomy' => Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY,
				'field' => 'id',
				'terms' => $blog_term_ids
			)),
			'post_status' => apply_filters('teachblog_listable_post_states', array(
				'publish', 'pending', 'draft', 'future', 'private'
			))
		));

		while ($query->have_posts()) {
			$query->the_post();
			$posts[] = $GLOBALS['post'];
		}
		wp_reset_postdata(); // Cleanup

		return $posts;
	}


	/**
	 * Indicates if the current user has a student blog.
	 *
	 * @return bool
	 */
	public function has_blog() {
		if (!$this->loaded) return false;

		$blog_ids = $this->get_blog_ids();
		return (is_array($blog_ids) and !empty($blog_ids)) ? true : false;
	}


	/**
	 * Returns an array structured in key:value form where the key is the student blog ID and value is the the student
	 * blog title. The returned array may be empty!
	 *
	 * @return array
	 */
	public function get_assigned_blog_list() {
		if (!$this->loaded) return false;

		$blogs = array();
		$blog_ids = $this->get_blog_ids();
		if (!is_array($blog_ids) or empty($blog_ids)) return array();

		foreach ($blog_ids as $term_id) {
			$student_blog = get_term($term_id, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY);
			if (!is_object($student_blog) or !isset($student_blog->term_id)) continue;
			$blogs[(int) $student_blog->term_id] = $student_blog->name;
		}

		return $blogs;
	}


	/**
	 * Returns the taxonomy IDs for the student blog, if the user is assigned to one/more than one, as an array of IDs.
	 * The array may be empty if the user is not assigned to any blogs. Should the user object not have loaded (ie, it
	 * is a non existent user) then bool false will be returned instead.
	 *
	 * @return bool|array
	 */
	public function get_blog_ids() {
		if (!$this->loaded) return false;
		return (array) get_user_meta($this->user->ID, 'teachblog_assigned_blogs_' . get_current_blog_id());
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
		$local_assignments = 'teachblog_assigned_blogs_' . get_current_blog_id();

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
		$users = (array)self::get_assigned_users($blog_term_id);

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
		return add_user_meta($this->user->ID, 'teachblog_assigned_blogs_' . get_current_blog_id(), $blog_id);
	}


	/**
	 * Unassigns the user from the specified blog.
	 *
	 * @param $blog_id
	 * @return bool
	 */
	public function unassign_from($blog_id) {
		if (!$this->loaded or !$this->is_valid_blog($blog_id)) return false;

		return delete_user_meta($this->user->ID, 'teachblog_assigned_blogs_' . get_current_blog_id(), $blog_id);
	}


	/**
	 * Unassigns the blogger from all and any blogs to which they are currently assigned.
	 */
	public function unassign_from_all() {
		if (!$this->loaded) return false;
		return delete_user_meta($this->user->ID, 'teachblog_assigned_blogs_' . get_current_blog_id());
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


	/**
	 * Returns the "highest" post_status available for new posts created by this user. This will normally be Pending
	 * Review ('pending') but may be Publish ('publish') for trusted student users.
	 *
	 * In the event no user object has loaded the returned string will be empty.
	 *
	 * @return string
	 */
	public function default_submit_status() {
		if (!$this->loaded) return '';
		else return apply_filters('teachblog_default_submit_status', 'pending', $this->user);
	}


    /**
     * Creates a new student user and returns the Teachblog_Blogger object for same.
     *
     * @param $username
     * @param $password
     * @param string $email = '' (optional)
     * @return Teachblog_Blogger
     */
    public static function create_blogger($username, $password, $email = '') {
        $result = wp_insert_user(array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'role' => Teachblog_Student_User::ROLE
        ));

        return new self($result);
    }
}