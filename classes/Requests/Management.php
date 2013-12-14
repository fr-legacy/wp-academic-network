<?php
namespace WPAN\Requests;
use WPAN\Gadgets;
use WPAN\Log;
use WPAN\Network;


class Management {
	/**
	 * Post type used to store requests.
	 */
	const POST_TYPE = 'wpan_request';

	/**
	 * Used to indicate an interest in all types of request.
	 */
	const TYPE_ALL = 'all_request_types';

	/**
	 * Possible states for request posts.
	 */
	const STATUS_SUBMITTED = 'wpan_submitted';
	const STATUS_ON_HOLD = 'wpan_on_hold';
	const STATUS_APPROVED = 'wpan_approved';
	const STATUS_REJECTED = 'wpan_rejected';

	/**
	 * Used to indicate an interest in all request states.
	 */
	const STATUS_ALL = 'any_status';

	/**
	 * Used to contains any request that might currently be being processed.
	 */
	protected $request = false;


	/**
	 * Container for the Requests Management object.
	 *
	 * @var Management
	 */
	protected static $object;


	/**
	 * @return Management
	 */
	public static function object() {
		if ( isset( self::$object ) ) return self::$object;
		self::$object = new self();
		return self::$object;
	}


	protected function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}


	public function init() {
		register_post_type( self::POST_TYPE, array( 'public' => false ) );
		$this->listen_for_requests();
	}


	public function widgets_init() {
		Gadgets\New_Teacher_Request::register();
	}


	/**
	 * Monitors the $_POST superglobal for signs of new student/teacher requests.
	 */
	public function listen_for_requests() {
		if ( isset( $_POST['teacher_request'] ) )
			if ( wp_verify_nonce( $_POST['teacher_request'], 'wpan_new_teacher_request') )
				$this->request = new Teacher();

		if ( isset( $_POST['student_request'] ) )
			if ( wp_verify_nonce( $_POST['student_request'], 'wpan_new_student_request') )
				$this->request = new Student();

		if ( false !== $this->request ) $this->request->process_new( $_POST );
	}


	public function is_processing_request() {
		return ( false !== $this->request );
	}

	public function get_in_progress_request() {
		return $this->request;
	}

	public function create_request( $type, array $data ) {
		// We want to record the request on the hub site
		switch_to_blog( Network::get_hub_id() );

		// Origin information and password protection
		$data = $this->add_origin_info( $data );
		$data = $this->hash_password( $data );
		$data = $this->ensure_submitting_user_recorded( $data );

		// Set up the request (initial status is always pending)
		wp_insert_post( array(
			'post_title' => 'Request: ' . $type,
			'post_name' => $type . '_' . date( 'ymd:Hi' ) . '_' . uniqid(),
			'post_content' => json_encode( $data ),
			'post_mime_type' => 'text/json',
			'post_status' => self::STATUS_SUBMITTED,
			'post_type' => self::POST_TYPE
		) );

		// Log and clean up
		Log::action( sprintf( __( 'Type "%s" request created.', 'wpan' ), $type ) );
		restore_current_blog();
	}

	/**
	 * Returns the total number of requests currently in the database.
	 *
	 * If no parameters are specified all requests are counted. Otherwise, the count can be
	 * limited to specific types and states.
	 *
	 * @param string $type
	 * @param string $status
	 * @return int
	 */
	public function count_requests( $type = self::TYPE_ALL, $status = self::STATUS_ALL ) {
		global $wpdb;
		$query = $wpdb->prepare( " SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s ", self::POST_TYPE );

		// Select a specific request type?
		if ( self::TYPE_ALL !== $type )
			$query .= $wpdb->prepare( ' AND post_title = %s ', 'Request: ' . $type );

		// Select requests with a specific status?
		if ( self::STATUS_ALL !== $status )
			$query .= $wpdb->prepare( ' AND post_status = %s ', $status );

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Returns a list of requests.
	 *
	 * If the type is specified then only requests of that type will be retrieved. If the
	 * $limit param is set to a positive non-zero value then that is the maximum number of
	 * requests that will be retrieved: used in concert with the $page parameter to paginate
	 * through results.
	 *
	 * @param string $type
	 * @param string $status
	 * @param $limit
	 * @param int $page
	 */
	public function get_requests( $type = self::TYPE_ALL, $status = self::STATUS_ALL, $limit = -1, $page = 1 ) {
		global $wpdb;
		$query = $wpdb->prepare( " SELECT * FROM $wpdb->posts WHERE post_type = %s ", self::POST_TYPE );

		// Select a specific request type?
		if ( self::TYPE_ALL !== $type )
			$query .= $wpdb->prepare( ' AND post_title = %s ', 'Request: ' . $type );

		// Select requests with a specific status?
		if ( self::STATUS_ALL !== $status )
			$query .= $wpdb->prepare( ' AND post_status = %s ', $status );

		// Paginate?
		if ( 0 < $limit ) {
			$offset = ( $page - 1 ) * $limit;
			$query .= $wpdb->prepare( ' LIMIT %d,%d ', $offset, $limit );
		}

		// Run the query!
		$requests = $wpdb->get_results( $query );



	}


	/**
	 * Adds fields to help document the source of a request (IP address, user agent).
	 *
	 * @param array $data
	 * @return array
	 */
	protected function add_origin_info( array $data ) {
		$data['_origin_info'] = array(
			'ip_addr' => isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : _x( 'Unknown', 'ip address', 'wpan' ),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : _x( 'Unknown', 'user agent', 'wpan' ),
			'referrer' => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : _x( 'Unknown', 'http referrer', 'wpan' ),
		);

		return $data;
	}

	/**
	 * If a password field is present in $data it is hashed with WP's normal functions.
	 *
	 * @param array $data
	 * @return array
	 */
	protected function hash_password( array $data ) {
		if ( isset( $data['password'] ) ) $data['password'] = wp_hash_password( $data['password'] );
		return $data;
	}

	/**
	 * Creates/updates the post_author field within $data, ensuring it reflects the actual user
	 * ID of the submitting user (or 0).
	 *
	 * @param array $data
	 * @return array
	 */
	protected function ensure_submitting_user_recorded( array $data ) {
		$data['post_author'] = wp_get_current_user()->ID;
		return $data;
	}


	public function get_request( $id ) {
		$post = get_post( $id );
		if ( null === $post || self::POST_TYPE !== $post->post_type ) return false;

		$title_components = explode( '_', $post->post_title );
		if ( ! is_array( $title_components ) || empty( $title_components ) ) return false;

		return (object) array(
			'type' => isset( $title_components[0] ) ? $title_components[0] : 'Unknown',
			'data' => @unserialize( base64_decode( $post->post_content ) )
		);
	}
}