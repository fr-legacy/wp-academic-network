<?php
namespace WPAN\Requests;


/**
 * Supports new student site & account requests.
 */
class Student extends General {
	const TYPE = 'teacher';

	/**
	 * @var array
	 */
	protected $fields = array();


	public function __construct() {
		
	}
	
	
	public function process_new( array $fields ) {
		$this->fields = $fields;		
		$this->get_submission_data();

	}


	protected function get_submission_data() {
		$this->fields = wp_parse_args(
			apply_filters( 'wpan_new_teacher_submission', array(
				'email' => isset( $this->fields['email'] ) ? filter_var( $this->fields['email'], FILTER_SANITIZE_EMAIL ) : '',
				'username' => isset( $this->fields['username'] ) ? filter_var( $this->fields['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) : '',
				'password' => isset( $this->fields['password'] ) ? filter_var( $this->fields['password'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) : '',
			) ),
			array( 'email' => '', 'username' => '', 'password' => '' )
		);
	}
}