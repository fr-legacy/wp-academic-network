<?php
namespace WPAN\Requests;
use WPAN\WordPress;


/**
 * Supports new teacher site & account requests.
 */
class Teacher {
	const TYPE = 'teacher';

	public $request_received = false;

	/**
	 * @var array
	 */
	protected $fields = array();


	protected $errors = array();


	public function __construct() {
		add_action( 'wpan_service_request_received', array( $this, 'listen_for_new_requests' ) );
		add_action( 'wpan_request_state_updated', array( $this, 'listen_for_status_updates' ), 10, 4 );
		add_action( 'wpan_fulfill_request', array( $this, 'fulfill' ) );
	}


	public function listen_for_new_requests() {
		if ( wp_verify_nonce( $_POST['wpan_service_request'], 'wpan_new_teacher_request') )
			$this->process_new( $_POST );
	}
	
	
	public function process_new( array $fields ) {
		$this->request_received = true;
		$this->fields = $fields;		
		$this->get_submission_data();
		$this->check_for_errors();
		$this->formulate_request();
	}


	/**
	 * Looks through the request submission and carries out basic sanitization checks.
	 */
	protected function get_submission_data() {
		$this->fields = wp_parse_args(
			apply_filters( 'wpan_new_teacher_submission', array(
				'email' => isset( $this->fields['email'] ) ? filter_var( $this->fields['email'], FILTER_SANITIZE_EMAIL ) : '',
				'username' => isset( $this->fields['username'] ) ? sanitize_user( filter_var( $this->fields['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) ) : '',
				'password' => isset( $this->fields['password'] ) ? filter_var( $this->fields['password'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) : '',
			) ),
			array( 'email' => '', 'username' => '', 'password' => '' )
		);
	}


	/**
	 * Validates the submitted data. If non-standard fields are added by a plugin then the
	 * wpan_teacher_request_submission_error_check filter hook can be used to validate those.
	 */
	protected function check_for_errors() {
		if ( ! filter_var( $this->fields['email'], FILTER_VALIDATE_EMAIL ) ) $this->errors['invalid_email'] =
			__( 'Invalid email address provided.', 'wpan' );

		if ( username_exists( $this->fields['username'] ) ) $this->errors['username_in_use'] =
			__( 'Username already in use.', 'wpan' );

		if ( email_exists( $this->fields['email'] ) ) $this->errors['email_in_use'] =
			__( 'Email address already in use.', 'wpan' );

		if ( empty( $this->fields['password'] ) ) $this->errors['empty_password'] =
			__( 'Empty passwords are not acceptable.', 'wpan' );

		$this->errors = (array) apply_filters( 'wpan_teacher_request_submission_error_check', $this->errors, $this->fields );
	}


	/**
	 * Returns the array of errors, which may be an empty array.
	 *
	 * @return array
	 */
	public function get_errors() {
		return (array) $this->errors;
	}

	/**
	 * If the request passed validation, form the actual request object.
	 */
	protected function formulate_request() {
		if ( ! empty( $this->errors ) ) return; // Something doesn't validate
		Manager::object()->create_request( self::TYPE, $this->fields );
	}

	/**
	 * Utility function that retrieves counts for the number of different requests currently in the
	 * system.
	 *
	 * @todo update to calculate fulfilled and failed requests
	 * @return array
	 */
	public static function request_counts() {
		$manager = Manager::object();

		return array(
			'all' => $manager->count_requests( self::TYPE ),
			'approved' => $manager->count_requests( self::TYPE, $manager::STATUS_APPROVED ),
			'on_hold' => $manager->count_requests( self::TYPE, $manager::STATUS_ON_HOLD ),
			'rejected' => $manager->count_requests( self::TYPE, $manager::STATUS_REJECTED ),
			'submitted' => $manager->count_requests( self::TYPE, $manager::STATUS_SUBMITTED ),
		);
	}

	/**
	 * Listens for teacher requests being approved in order to start the fulfillment process.
	 *
	 * @param $new_status
	 * @param $id
	 * @param $request
	 * @param $request_manager
	 */
	public function listen_for_status_updates( $new_status, $id, $request, $request_manager ) {
		if ( Manager::STATUS_APPROVED !== $new_status || self::TYPE !== $request->type ) return;
		if ( apply_filters( 'wpan_automatically_fulfill_teacher_requests', true ) ) $this->fulfill( $request );
	}



	/**
	 * Attempts to fulfill an approved request for a new site/user account.
	 *
	 * If the method succeeds it sets the request status to self::STATUS_FULFILLED and returns true, otherwise
	 * the request status will be set to self::STATUS_FAILED (if it was already a valid request of approved
	 * status) and boolean false will be returned.
	 *
	 * @param $request
	 * @return bool
	 */
	public function fulfill( $request ) {
		// Ensure we're working with a valid request
		if ( ! is_object( $request ) ) return false;
		if ( Manager::STATUS_APPROVED === $request->type ) return false;

		$user_id = $this->find_existing_or_generate_new_user( $request );
		$domain = apply_filters( 'wpan_fulfill_blog_request_domain', WordPress::get_new_site_domain() );
		$path = apply_filters( 'wpan_fulfill_blog_request_path', $this->path_from_username( $user_id ) );
		$title = apply_filters( 'wpan_fulfill_blog_request_title', __ ( 'Newly created blog!', 'wpan' ) );

		wpmu_create_blog( $domain, $path, $title, $user_id );
	}

	/**
	 * If an existing user (by email address) exists, return that user object. Otherwise, create a new
	 * user and assign them to that address.
	 *
	 * @param $request
	 */
	protected function find_existing_or_generate_new_user( $request ) {
		if ( email_exists( $request->email ) ) return get_user_by( 'email', $request->email );
		else return $this->create_new_user( $request );
	}

	/**
	 * Generates a new user based on the request parameters.
	 *
	 * @param $request
	 */
	protected function create_new_user( $request ) {
		$new_user_params = apply_filters( 'wpan_new_teacher_user_params', array(
			'user_login' => isset( $request->username ) ? $request->username : $request->email,
			'user_pass' => $request->password
		) );

		return WordPress::create_user( $new_user_params );
	}

	/**
	 * Returns a possible blog name based on the user's username.
	 *
	 * @param $user_id
	 * @return string
	 */
	protected function path_from_username( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) return uniqid();
		$path_name = strtolower( str_replace( ' ', '_', $user->display_name ) );
		return apply_filters( 'wpan_teacher_blog_path_name', $path_name );
	}
}