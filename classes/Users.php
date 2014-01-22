<?php
namespace WPAN;

use DateTime,
	Exception,
	WPAN\Helpers\Log,
	WP_User_Query;


class Users
{
	/**
	 * Academic Network Teacher user role title.
	 */
	const TEACHER = 'wpan_teacher';

	/**
	 * Academic Network Student user role title.
	 */
	const STUDENT = 'wpan_student';

	/**
	 * Meta key used for Unique Academic Identifiers.
	 */
	const UAID = 'wpan_uaid';

	/**
	 * Meta key used to reference user params (arbitrary additional data).
	 */
	const ADDITIONAL_DATA = 'wpan_user_additional_data';

	/**
	 * Student validation date/time is a piece of meta data used to determine the last time
	 * the student record was updated. Useful during housekeeping to determine which student
	 * user accounts might be dormant.
	 */
	const VALIDATION = 'wpan_user_validated';

	/**
	 * Indicates if user roles should be wiped and rebuilt (to clear any changes made
	 * to the role definitions by other plugins or upon plugin updates, etc).
	 *
	 * @var bool
	 */
	protected $rebuild_roles = false;

	/**
	 * Role caps for Academic Administrators (in addition they will inherit normal
	 * administrator caps).
	 *
	 * @var array
	 */
	protected $admin_caps = array(
		'wpan_access_hub_tools' => true,
		'wpan_create_site' => true,
		'wpan_delete_site' => true,
		'wpan_manage_users' => true,
		'wpan_create_user' => true,
		'wpan_delete_user' => true,
		'wpan_handle_requests' => true
	);

	/**
	 * Students by default fulfil an editor-like role, with the added power
	 * of being able to change and adjust their theme (and export data).
	 *
	 * @var array
	 */
	protected $student_caps = array(
		'switch_themes' => true,
		'edit_theme_options' => true,
		'export' => true
	);


	/**
	 * Sets up user management facilities.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_types' ) );
	}

	/**
	 * Registers our specific user roles.
	 */
	public function register_types() {
		$this->rebuild_roles = (bool) apply_filters( 'wpan_rebuild_user_roles', $this->rebuild_roles );
		$this->register_admin_role();
		$this->register_teacher_role();
		$this->register_student_role();
	}

	/**
	 * Registers the Academic Network Administrator role (who can monitor site creation and
	 * maintenance, etc).
	 */
	protected function register_admin_role() {
		$role = 'wpan_administrator';
		$display_name = __( 'Academic Network Administrator', 'wpan' );
		$caps = array_merge( $this->get_existing_capabilities( 'administrator' ), $this->admin_caps );

		if ( $this->rebuild_roles ) remove_role( $role );
		add_role( $role, $display_name, $caps );
	}

	/**
	 * Registers the Academic Network Teacher role.
	 */
	protected function register_teacher_role() {
		$display_name = __( 'Academic Network Teacher', 'wpan' );
		$caps = $this->get_existing_capabilities( 'administrator' );

		if ( $this->rebuild_roles ) remove_role( self::TEACHER );
		add_role( self::TEACHER, $display_name, $caps );
	}

	/**
	 * Registers the Academic Network Student role.
	 */
	protected function register_student_role() {
		$display_name = __( 'Academic Network Student', 'wpan' );
		$caps = array_merge( $this->get_existing_capabilities( 'editor' ), $this->student_caps );

		if ( $this->rebuild_roles ) remove_role( self::STUDENT );
		add_role( self::STUDENT, $display_name, $caps );
	}

	/**
	 * Returns the capabilities of an existing user role. Useful to merge one set into a new role.
	 *
	 * @param $existing_role
	 * @return array
	 */
	protected function get_existing_capabilities( $existing_role ) {
		$role = get_role( $existing_role );
		if ( ! is_a( $role, 'WP_Role' ) ) return array();
		return (array) $role->capabilities;
	}

	/**
	 * Generates a new teacher-role user account and optionally assigns to them a
	 * Unique Academic Identifier (UAID).
	 *
	 * @param $username
	 * @param $password
	 * @param $email
	 * @param $uaid
	 * @return mixed int | bool
	 */
	public function create_teacher( $username, $password, $email, $uaid = null ) {
		// If a UAID has been provided, ensure it is not already in use
		if ( null !== $uaid && $this->uaid_exists( $uaid ) ) {
			Log::error( sprintf( __( 'Unable to create new teacher user $s, UAID %s is already in use.', 'wpan' ), $username, $uaid ) );
			return false;
		}

		// Attempt to create a new user account for the teacher
		$user_id = wpmu_create_user( $username, $password, $email );

		if ( false === $user_id ) {
			Log::error( sprintf( __( 'Failed to create new teacher. User %s could not be created.', 'wpan' ), $username ) );
			return false;
		}

		// Give them their network-wide designation as a teacher
		$this->set_academic_role( $user_id, Users::TEACHER );

		// Optionally assign UAID
		if ( null !== $uaid ) $this->set_uaid( $user_id, $uaid );
		return (int) $user_id;
	}

	/**
	 * Generates a new student-role user account and assigns to them a Unique Academic
	 * Identifier (not optional).
	 *
	 * @param $username
	 * @param $password
	 * @param $uaid
	 * @return mixed bool | int
	 */
	public function create_student( $username, $password, $uaid ) {
		// Ensure UAID is not already in use
		if ( null !== $uaid && $this->uaid_exists( $uaid ) ) {
			Log::error( sprintf( __( 'Unable to create new student user $s, UAID %s is already in use.', 'wpan' ), $username, $uaid ) );
			return false;
		}

		// Students are not expected to have an email address, though WordPress requires it
		$email = $this->email_placeholder( $username, $uaid );
		$email = apply_filters( 'wpan_student_email_placeholder', $email, $username, $uaid );

		// Attempt to create a new user account for the teacher
		$user_id = wpmu_create_user( $username, $password, $email );

		if ( false === $user_id ) {
			Log::error( sprintf( __( 'Failed to create new student. User %s could not be created.', 'wpan' ), $username ) );
			return false;
		}

		// Give them their network-wide designation as a teacher
		$this->set_academic_role( $user_id, Users::STUDENT );

		// Optionally assign a UAID
		if ( null !== $uaid ) $this->set_uaid( $user_id, $uaid );
		return (int) $user_id;
	}

	/**
	 * Deletes the specified user account.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function delete_user( $user_id ) {
		wpmu_delete_user( $user_id );
		return true;
	}

	/**
	 * Sets the Unique Academic Identifier (UAID) which may be a unique provincial code for students or
	 * an employee number etc for teachers.
	 *
	 * @param $user_id
	 * @param $uaid
	 * @return bool
	 */
	public function set_uaid( $user_id, $uaid ) {
		// Find out if any users already have this identifier assigned to them
		$current_user = $this->who_is( $uaid );

		// UAID already belongs to this user
		if ( $user_id == $current_user) return true;

		// UAID is already assigned to a different user
		if ( false !== $current_user) {
			Log::warning( __( 'Could not assign UAID %s to user %d, already assigned to user %d.', 'wpan' ), $uaid, $user_id, $current_user );
			return false;
		}

		return update_user_meta( $user_id, self::UAID, $uaid );
	}

	/**
	 * Finds the user (if one exists) related to the specified Unique Academic Identifier (UAID)
	 * and returns their (int) ID or else (bool) false if no users currently use that UAID.
	 *
	 * This can effectively be used to determine if a UAID has already been assigned to a user
	 * or not.
	 *
	 * @param $uaid
	 * @return mixed bool | int
	 */
	public function who_is( $uaid ) {
		$query = new WP_User_Query( array(
			'blog_id' => 0,
			'meta_key' => self::UAID,
			'meta_value' => $uaid
		) );

		if ( empty( $query->results ) ) return false;

		// Obtain the first user ID (there should be only one) and return
		foreach ( $query->results as $user ) return absint( $user->ID );
		return false;
	}

	/**
	 * Determines if the Unique Academic Identifier is already in use.
	 *
	 * @param $uaid
	 * @return bool
	 */
	public function uaid_exists( $uaid ) {
		return ( false !== $this->who_is( $uaid ) );
	}

	/**
	 * Returns a faux email address that can be used during registration of student accounts
	 * (since students do not necessarily require an email address but WP does).
	 *
	 * @param $username
	 * @param $uaid
	 * @return string
	 */
	public function email_placeholder( $username, $uaid = null ) {
		if ( is_string( $uaid ) ) $username .= ".$uaid";

		do {
			$address = sanitize_user( $username ) . '.' . uniqid() . '@wpan.student.user';
		} while ( email_exists( $address ) );

		return $address;
	}

	/**
	 * Marks the academic role of the user (ordinarily either a teacher or a student). Unlike a
	 * conventional user role on a WordPress network, this does not vary by blog.
	 *
	 * @param $user_id
	 * @param $role
	 * @return bool
	 */
	public function set_academic_role( $user_id, $role ) {
		if ( false === get_user_by( 'id', $user_id ) ) {
			Log::warning( sprintf( __( 'Attempt made to set academic role of non existent user #%s', 'wpan' ), $user_id ) );
			return false;
		}

		return update_user_meta( $user_id, 'wpan_academic_role', $role );
	}

	/**
	 * Returns the network-wide academic role of the user, if it can be determined, or else
	 * returns boolean false.
	 *
	 * @param $user_id
	 * @return mixed string | bool false
	 */
	public function get_academic_role( $user_id ) {
		if ( false === get_user_by( 'id', $user_id ) ) {
			Log::warning( sprintf( __( 'Attempt made to retrieve academic role of non existent user #%s', 'wpan' ), $user_id ) );
			return false;
		}

		$role = get_user_meta( $user_id, 'wpan_academic_role', true );
		return ( empty($role) ) ? false : $role;
	}

	/**
	 * Returns the number of user accounts with the specified academic role.
	 *
	 * @param $role
	 * @return int
	 */
	public function count_academic_role( $role ) {
		global $wpdb;

		$query = "SELECT COUNT( DISTINCT user_id ) FROM wp_usermeta "
			. "WHERE meta_key = 'wpan_academic_role' AND meta_value = '%s';";

		$count = $wpdb->get_var( $wpdb->prepare( $query, $role ) );
		return absint( $count );
	}

	/**
	 * Returns a list (as an array) of students, which may be empty.
	 *
	 * Optional params facilitate pagination and ordering as per the get_where() method.
	 *
	 * @param $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 * @param int $blog
	 * @return array
	 */
	public function get_students( $limit = -1, $offset = 0, $order_by = 'login', $order = 'ASC', $blog = 0 ) {
		return $this->get_where( self::STUDENT, $limit, $offset, $order_by, $order, $blog );
	}

	/**
	 * Returns a list (as an array) of teachers, which may be empty.
	 *
	 * Optional params facilitate pagination and ordering as per the get_where() method.
	 *
	 * @param $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 * @param int $blog
	 * @return array
	 */
	public function get_teachers( $limit = -1, $offset = 0, $order_by = 'login', $order = 'ASC', $blog = 0 ) {
		return $this->get_where( self::TEACHER, $limit, $offset, $order_by, $order, $blog );
	}

	/**
	 * Retrieves users by academic role.
	 *
	 * No limit on the number of results to be returned is assumed, but pagination and ordering is possible
	 * via the additional optional params.
	 *
	 * Assumes we want results from across the entire network, however this can be locked down to a specific blog.
	 *
	 * @param $academic_role
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 * @param int $blog
	 * @return array
	 */
	public function get_where( $academic_role, $limit = -1, $offset = 0, $order_by = 'login', $order = 'ASC', $blog = 0 ) {
		$args = array(
			'meta_key' => 'wpan_academic_role',
			'meta_value' => $academic_role,
			'number' => $limit,
			'offset' => $offset,
			'orderby' => $order_by,
			'order' => $order,
			'blog_id' => $blog
		);

		// Unlimited users?
		if ( -1 === $limit ) unset( $args['number'], $args['offset'] );

		$users = new WP_User_Query( $args );
		return (array) $users->get_results();
	}

	/**
	 * Confirms that the user is a real Student-roled user.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function is_student( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( false === $user ) return false;

		$role = $this->get_academic_role( $user_id );
		return ( self::STUDENT === $role );
	}

	/**
	 * Confirms that the user is a real Teacher-roled user.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function is_teacher( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( false === $user ) return false;

		$role = $this->get_academic_role( $user_id );
		return ( self::TEACHER === $role );
	}

	/**
	 * Updates the role of a specified user within the context of a specific blog.
	 *
	 * @param $blog_id
	 * @param $user_id
	 * @param $target_role
	 * @return bool
	 */
	public function change_blog_role( $blog_id, $user_id, $target_role ) {
		$switch = false;
		$update = false;

		if ( false !== switch_to_blog( $blog_id ) ) $switch = true;
		if ( false !== $switch) $update = wp_update_user( array(
			'ID' => $user_id,
			'role' => $target_role
		) );

		if ( $switch ) restore_current_blog();

		if ( false === $switch || is_wp_error( $update ) ) {
			Log::warning( sprintf( __( 'Could not update role for user %d on blog %d.', 'wpan' ), $user_id, $blog_id ) );
			return false;
		}

		return true;
	}

	/**
	 * Updates various bits of academic data pertaining to the user. This is freeform and might
	 * contain a student's grade, time at a school, club attendance etc or comparable data for
	 * a teacher such as subjects taught, extra-curricular clubs managed etc.
	 *
	 * The additional data should be provided as an array of key:value pairs.
	 *
	 * Whenever this method is used a note is kept of the date and time at which the update took
	 * place (equivalent to calling validate_user() directly), this can be useful to determine
	 * which user accounts are active and which have been dormant at a later point in time for
	 * housekeeping purposes.
	 *
	 * @param $user_id
	 * @param array $data
	 * @return bool
	 */
	public function set_additional_data( $user_id, array $data = null ) {
		// Automatically remove various fields fields from $data
		foreach ( array( 'uaid', 'username', 'password', 'blogtitle', 'blogpath' ) as $unwanted )
			if ( isset( $data[$unwanted] ) ) unset( $data[$unwanted] );

		if ( false === get_user_by( 'id', $user_id ) ) {
			Log::error( sprintf( __( 'Attempt made to update user params for non user %d.', 'wpan' ), $user_id ) );
			return false;
		}

		// A bool false result may simply mean the data was unchanged
		if ( false === update_user_meta( $user_id, self::ADDITIONAL_DATA, $data ) ) {
			Log::notice( sprintf( __( 'Additional user data for user %d was not updated (there may not have been any changes).', 'wpan' ), $user_id ) );
		}

		return $this->validate_user( $user_id );
	}

	/**
	 * Confirms the user account is still alive and valid.
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function validate_user( $user_id ) {
		if ( false === get_user_by( 'id', $user_id ) ) {
			Log::error( sprintf( __( 'Attempt made to validate user %d failed: user not found.', 'wpan' ), $user_id ) );
			return false;
		}

		return update_user_meta( $user_id, self::VALIDATION, date('Y-m-d H:i:s') );
	}

	/**
	 * Returns additonal data (as an array) for this user. May be empty.
	 *
	 * @param $user_id
	 * @return array
	 */
	public function get_additional_data( $user_id ) {
		if ( false === get_user_by( 'id', $user_id ) ) {
			Log::error( sprintf( __( 'Attempt made to retrieve additional data for non user %d.', 'wpan' ), $user_id ) );
			return false;
		}

		return (array) get_user_meta( $user_id, self::ADDITIONAL_DATA, true );
	}

	/**
	 * Returns the number of days since the user account was validated or returns false if that could
	 * not be determined.
	 *
	 * In the unusual circumstances of a user record being validated in the future (perhaps due to an
	 * update having taken place but server date/time settings being awry) this will return 0.
	 *
	 * @param $user_id
	 * @return mixed int | bool
	 */
	public function num_days_since_validated( $user_id ) {
		$date = $this->validation_datestamp( $user_id );
		if ( ! is_a( $date, 'DateTime' ) ) return false;

		// Calculate the number of seconds between the validation date and now
		$then = $date->getTimestamp();
		$now = time();
		$diff = $now - $then;

		// It should be positive or zero: but issue a warning and return 0
		if ( 0 > $diff ) {
			Log::warning( sprintf( __( 'Record for user %d seems to have been validated in the future.', 'wpan' ), $user_id ) );
			return 0;
		}

		$days = $diff / 86400; // 86,400 seconds in a day
		return absint( $days );
	}

	/**
	 * @param $user_id
	 * @return mixed DateTime | false
	 */
	public function validation_datestamp( $user_id ) {
		if ( false === $this->is_student( $user_id ) ) {
			Log::error( sprintf( __( 'Attempt made to find record validatation date for student %d failed: not a student.', 'wpan' ), $user_id ) );
			return false;
		}

		$validation_stamp = get_user_meta( $user_id, self::VALIDATION, true );

		if ( empty( $validation_stamp ) ) {
			Log::warning( sprintf( __( 'Record for user %d has never been validated.', 'wpan' ), $user_id ) );
			return false;
		}

		try {
			$datetime = new DateTime( $validation_stamp );
		}
		catch ( Exception $e ) {
			Log::error( sprintf( __( 'Validation data for user %d is invalid and may have been corrupted.', 'wpan' ), $user_id ) );
			return false;
		}

		return $datetime;
	}
}