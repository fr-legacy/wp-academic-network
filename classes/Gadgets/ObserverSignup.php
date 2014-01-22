<?php
namespace WPAN\Gadgets;

use WPAN\Core,
	WPAN\Helpers\BaseGadget,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress;


class ObserverSignup extends BaseGadget
{
	/**
	 * Flags if a submission is being processed.
	 *
	 * @var bool
	 */
	protected $processing = false;

	/**
	 * Contains any errors that may have been caught during processing.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Username submission (when validated).
	 *
	 * @var string
	 */
	protected $username = '';

	/**
	 * Email submission (when validated).
	 *
	 * @var string
	 */
	protected $email = '';


	/**
	 *Runs at the tailend of the parent class constructor: sets up listeners to check for submissions etc.
	 */
	protected function setup() {
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * Listens for form submissions.
	 */
	public function listen() {
		if ( ! $this->submission_check() ) return;
		if ( ! $this->validate() ) return;
		$this->do_submission();
	}

	/**
	 * Confirms if a submission was made with a valid nonce.
	 *
	 * @return bool
	 */
	protected function submission_check() {
		if ( ! isset( $_POST['origin'] ) ) return false;

		$expected_field = 'wpan_service_' . substr( hash( 'md5', $_POST['origin'] . NONCE_SALT ), 5, 15 );
		if ( ! isset( $_POST[$expected_field] ) ) return false;

		return wp_verify_nonce( $_POST[$expected_field], 'wpan_new_observer_request' . $_POST['origin'] );
	}

	/**
	 * Validates the submission. If there are any failures it will return boolean false, in addition to
	 * adding the failures to the errors array.
	 *
	 * @return bool
	 */
	protected function validate() {
		$this->processing = true;

		$fields = wp_parse_args( $_POST, array( 'username' => '', 'email' => '' ) );
		$fields = array_map( 'trim', $fields );

		if ( empty($fields['email'] ) )
			$this->errors['email'] = __( 'You did not supply an email address.', 'wpan' );

		elseif ( ! filter_var( $fields['email' ], FILTER_VALIDATE_EMAIL ) )
			$this->errors['email'] = __( 'The email address you supplied does not appear to be valid.', 'wpan' );

		$fields['username'] = sanitize_user( $fields['username'] );

		if ( empty( $fields['username'] ) )
			$this->errors['username'] = __( 'You did not provide a username (or it contained mostly illegal characters).', 'wpan' );

		elseif ( username_exists( $fields['username'] ) ) {
			// Try to offer up an alternative username possibility
			$alternative = WordPress::slug_incrementer( $fields['username'] );
			while ( username_exists( $alternative ) )
				$alternative = WordPress::slug_incrementer( $alternative );

			$this->errors['username'] = sprintf( __( 'The username you provided is not available, but "%s" is (or you can try something new).', 'wpan' ), $alternative );
			$_POST['username'] = $alternative; // Let the sticky form field pick up the alternative
		}

		$this->email = $fields['email'];
		$this->username = $fields['username'];

		return ( 0 === count( $this->errors ) );
	}

	/**
	 * Pass the submission up to the Users object for further processing.
	 */
	protected function do_submission() {
		if ( ! Core::object()->users()->new_observer_request( $this->username, $this->email ) )
			$this->errors['system'] = __( 'Your request could not be processed. Please try again later or contact us for help.', 'wpan' );
	}

	/**
	 * Friendly name for the gadget when used within the widgets admin screen.
	 *
	 * @return string
	 */
	protected function widget_name() {
		return _x( 'Academic Observer Accounts', 'wpan' );
	}

	/**
	 * Explains the role of the gadget within the widgets admin screen.
	 * @return string
	 */
	protected function widget_description() {
		return _x( 'Used to facilitate the user signup process for academic observer accounts (a useful step when giving parents access to student blogs, etc).', 'widget description', 'wpan' );
	}

	/**
	 * Shortcode handle.
	 *
	 * @return string
	 */
	protected function shortcode_name() {
		return _x( 'wpan_observer_signup', 'gadget shortcode', 'wpan' );
	}

	/**
	 * @return string
	 */
	protected function widget_base() {
		return 'wpan_observer_signup';
	}

	/**
	 * Displays the widget admin settings form.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		echo View::admin( 'gadgets/observer-signup', array( 'title' => $title ) );
	}

	/**
	 * Handles gadget settings updates.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		if ( ! wp_verify_nonce( $_POST['wpan_gadget_observer'], 'wpan_update_observer_gadget_settings' ) ) return;
		if ( isset($_POST['title'] ) ) return array( 'title' => $_POST['title' ] );
		else return array( 'title' => '' );
	}

	/**
	 * Renders the gadget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$params = array_merge( $args, $instance, array(
			'processing' => $this->processing,
			'errors' => $this->errors
		) );

		echo $this->public_view( 'observer-signup', $params );
	}
}