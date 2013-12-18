<?php
namespace WPAN\Requests;
use WPAN\Gadgets;
use WPAN\Log;
use WPAN\Network;
use DateTime;


class Manager {
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
	const STATUS_FULFILLED = 'wpan_fulfilled';
	const STATUS_FAILED = 'wpan_failed';

	/**
	 * Used to indicate an interest in all request states.
	 */
	const STATUS_ALL = 'any_status';

	/**
	 * Container for the Requests Management object.
	 *
	 * @var Manager
	 */
	protected static $object;

	/**
	 * Warnings, successful operation notices etc are recorded here in the format
	 * [ type, notice ] where type might be 'notice', 'warning', etc.
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Container for the teacher request handler.
	 *
	 * @var Teacher
	 */
	public $teacher;

	/**
	 * Container for the student request handler.
	 *
	 * @var Student
	 */
	public $student;


	/**
	 * @return Manager
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
		$this->student = new Student;
		$this->teacher = new Teacher;
		$this->listen_for_requests();
	}


	public function widgets_init() {
		Gadgets\New_Teacher_Request::register();
		Gadgets\New_Student_Request::register();
	}


	/**
	 * Monitors the $_POST superglobal for signs of new student/teacher requests.
	 */
	public function listen_for_requests() {
		if ( isset( $_POST['wpan_service_request'] ) ) do_action( 'wpan_service_request_received' );
	}

	public function create_request( $type, array $data ) {
		// We want to record the request on the hub site
		switch_to_blog( Network::get_hub_id() );

		// Origin information and password protection
		$data = $this->add_origin_info( $data );
		$data = $this->hash_password( $data );
		$data = $this->ensure_submitting_user_recorded( $data );
		$data = (array) apply_filters( 'wpan_new_request_data', $data );

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
	 * @param int $limit
	 * @param int $page
	 * @return mixed
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
		return $this->convert_to_request_objects( (array) $wpdb->get_results( $query ) );
	}

	/**
	 * Converts one or more post object results into corresponding request objects.
	 *
	 * @param $results
	 * @return mixed
	 */
	protected function convert_to_request_objects( $results ) {
		// Remember if we initially were passed a result or array of results (but let's treat as an array)
		$is_array = is_array( $results );
		$results = $is_array ? $results : array( $results );

		foreach ( $results as &$result ) {
			$post_data = (array) $result;
			$result = json_decode( $result->post_content );
			if ( ! is_object( $result ) ) $result = new \stdClass;

			// Add properties based on various standard WP post fields
			$result->type = $this->get_type_from_title( $post_data['post_title'] );
			$result->created = new DateTime( $post_data['post_date'] );
			$result->state = $post_data['post_status'];
			$result->id = $post_data['ID'];
			$result->guid = $post_data['post_name'];
		}

		// Return as an array or return a single result (if that was what we started with)
		if ( ! $is_array ) return array_shift( $results );
		return $results;
	}

	/**
	 * Extracts and returns the request type from the post title.
	 *
	 * Request posts have the type stored in the title, in the format "Request: type". This method
	 * simply parses out the type, or returns 'unknown' if it cannot.
	 *
	 * @param $title
	 * @return string
	 */
	protected function get_type_from_title( $title ) {
		static $key = 'Request: ';
		static $key_length = 9;

		if ( false === strpos( $title, $key ) || strlen( $title ) <= $key_length ) return 'unknown';
		else return substr( $title, $key_length );
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

	/**
	 * Returns a single request object, or false if it does not exist.
	 *
	 * @param $id
	 * @return bool|mixed
	 */
	public function get_request( $id ) {
		$post = get_post( $id );
		if ( null === $post || self::POST_TYPE !== $post->post_type ) return false;
		return $this->convert_to_request_objects( $post );
	}

	/**
	 * Attempts to change the status of the specified request. If it cannot, or the new state
	 * is invalid, it returns bool false.
	 *
	 * @param $id
	 * @param $new_status
	 * @return bool
	 */
	public function change_status( $id, $new_status ) {
		// Ensure this is a bona fide request ID
		if ( false === ( $request = $this->get_request( $id ) ) ) {
			Log::error( sprintf( __( 'Attempt made to change state to "%s" using invalid request ID "%d".', 'wpan' ), $new_status, $id ) );
			return false;
		}
		// Ensure the new status is valid
		if ( false === $this->is_valid_state( $new_status ) ) {
			Log::error( sprintf( __( 'Attempt made to change request %d to invalid status of "%s".', 'wpan' ), $id, $new_status ) );
			return false;
		}

		// Update!
		wp_update_post( array( 'ID' => $id, 'post_status' => $new_status ) );
		Log::action( sprintf( __( 'Request %d updated by user %d, status is now "%s".', 'wpan' ), $id, wp_get_current_user()->ID, $new_status ) );

		// Allow follow-on actions
		do_action( 'wpan_request_state_updated', $new_status, $id, $request, $this );
		return true;
	}

	/**
	 * Means of triggering fulfillment of requests, if for instance it isn't automatically set in motion following
	 * an approval event.
	 *
	 * @param $request_id
	 */
	public function trigger_fulfillment( $request_id ) {
		// Ensure this is a bona fide request ID
		if ( false === ( $request = $this->get_request( $request_id ) ) ) {
			Log::error( sprintf( __( 'Attempt made to fulfill request failed: invalid request ID "%d".', 'wpan' ), $request_id ) );
			return;
		}

		do_action( 'wpan_fulfill_request', $request );
	}

	/**
	 * Determines if a given string is a valid request status.
	 *
	 * @param $status
	 * @return bool
	 */
	protected function is_valid_state( $status ) {
		$states = array( self::STATUS_APPROVED, self::STATUS_ON_HOLD, self::STATUS_SUBMITTED, self::STATUS_REJECTED,
			self::STATUS_FAILED, self::STATUS_FULFILLED);
		$valid = in_array( $status, $states );
		return apply_filters( 'wpan_is_valid_request_state', $valid, $status );
	}
}