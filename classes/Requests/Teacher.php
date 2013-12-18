<?php
namespace WPAN\Requests;


/**
 * Supports new teacher site & account requests.
 */
class Teacher {
	const TYPE = 'teacher';

	/**
	 * @var array
	 */
	protected $fields = array();


	protected $errors = array();


	public function __construct() {
		
	}
	
	
	public function process_new( array $fields ) {
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
}