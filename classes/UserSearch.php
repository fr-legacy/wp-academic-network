<?php
namespace WPAN;

use DateTime,
	Exception,
	WPAN\Helpers\Log,
	WP_User_Query;


class UserSearch
{
	/**
	 * Storage of search results.
	 *
	 * @var array
	 */
	protected $users = array();

	/**
	 * Container for the query while being constructed.
	 *
	 * @var string
	 */
	protected $query = '';

	protected $academic_role = '';
	protected $limit    = -1;
	protected $offset   = 0;
	protected $order_by = '';
	protected $order    = 'ASC';
	protected $blog     = 0;
	protected $search   = '';


	/**
	 * Provides a more powerful user search for academic users than WP_User_Query can
	 * facilitate.
	 *
	 * The constructor borrows the method signature of Users::get_where() for easier,
	 * interchangeable use with that method.
	 *
	 * @param  string  $academic_role
	 * @param  int     $limit
	 * @param  int     $offset
	 * @param  string  $order_by
	 * @param  string  $order
	 * @param  int     $blog
	 * @param  string  $search
	 */
	public function __construct( $academic_role, $limit = -1, $offset = 0, $order_by = 'login', $order = 'ASC', $blog = 0, $search = '' ) {
		$this->academic_role = $academic_role;
		$this->limit         = $limit;
		$this->offset        = $offset;
		$this->order_by      = $order_by;
		$this->order         = $order;
		$this->blog          = $blog;
		$this->search        = $search;

		$this->prepare();
		$this->execute();
	}

	protected function prepare() {
		$this->query = $this->setup()
		             . $this->blog_join()
		             . $this->conditions()
		             . $this->ordering()
		             . $this->pagination();
	}

	protected function setup() {
		global $wpdb;
		$academic_role = $wpdb->prepare( '%s', $this->academic_role );

		return "
			SELECT DISTINCT( users.ID )
			FROM   $wpdb->users AS users
			JOIN   $wpdb->usermeta AS uaid_record ON (
			           uaid_record.user_id = users.ID  AND
			           uaid_record.meta_key = 'wpan_uaid'
		           )
			JOIN   $wpdb->usermeta AS user_type ON (
			           user_type.user_id = users.ID              AND
			           user_type.meta_key = 'wpan_academic_role' AND
			           user_type.meta_value = $academic_role
		           )
       ";
	}

	protected function blog_join() {
		global $wpdb;

		// Not a blog specific search?
		if ( ! $this->blog  ) return '';

		$blog_prefix   = $wpdb->get_blog_prefix( $this->blog );

		return "
			JOIN   $wpdb->usermeta AS user_blog ON (
		           user_blog.user_id  = users.ID              AND
		           user_blog.meta_key = '{$blog_prefix}capabilities'
		       )
		";
	}

	protected function conditions() {
		global $wpdb;

		$term = trim( strtolower( $this->search ) );   // Normalize
		$term = str_replace( '*', '%', $term );        // Facilitate use of "*" as a wildcard within the search term
		$term = '%' . $term . '%';                     // Always assume the term is a fragment
		$term = str_replace( '%%', '%', $term );       // Remove unnecessary doubles

		$search = join( ' OR ', array(
			$wpdb->prepare( 'user_login LIKE %s',   $term ),
			$wpdb->prepare( 'user_email LIKE %s',   $term ),
			$wpdb->prepare( 'display_name LIKE %s', $term ),
			$wpdb->prepare( 'uaid_record.meta_value LIKE %s', $term )
		) );

		if ( $this->blog )
			$search = " ( $search ) AND user_blog.meta_value <> NULL ";

		return " WHERE $search ";
	}

	protected function ordering() {
		$order = ( 'ASC' === $this->order ) ? 'ASC' : 'DESC';
		return " ORDER BY $this->order_by $order ";
	}

	protected function pagination() {
		$offset = (int) $this->offset;
		$limit  = (int) $this->limit;
		return " LIMIT $offset, $limit ";
	}

	protected function execute() {
		global $wpdb;

		// Build a list of user IDs matching the criteria
		$user_ids = $wpdb->get_col( $this->query );

		// Transform into an array of WP_User objects
		foreach ( $user_ids as $id ) {
			$user_object = get_user_by( 'id', $id );
			if ( $user_object ) $this->users[] = $user_object;
		}
	}

	/**
	 * @return int
	 */
	public function count_results() {
		return count( $this->users );
	}

	/**
	 * @return array
	 */
	public function get_results() {
		return $this->users;
	}
}