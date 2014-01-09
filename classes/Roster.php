<?php
namespace WPAN;

use WPAN\Helpers\Log;


class Roster
{
	/**
	 * Roster is ready - no updates are in progress.
	 */
	const READY = 100;

	/**
	 * A roster update has been provided and changes/further processing is still pending.
	 */
	const PENDING_CHANGES = 200;

	/**
	 * Number of roster updates/new entries to process in a single batch (default value, it can be overridden using
	 * a filter).
	 */
	const BATCH_SIZE = 20;

	/**
	 * One of the user roles as defined in the Users class.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The current status of the roster.
	 *
	 * @var int
	 */
	protected $status = self::READY;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * Total matching users.
	 *
	 * @var int
	 */
	protected $total_users;

	/**
	 * Container for roster update data.
	 *
	 * @var array
	 */
	protected $source = array();

	/**
	 * Holds information about the source data and how far advanced processing is, etc.
	 *
	 * @var array
	 */
	protected $source_meta = array();

	/**
	 * Flag used to indicate if the source data for an update was loaded.
	 *
	 * @var bool
	 */
	protected $source_loaded = false;



	/**
	 * Sets up the Roster object to work with the specified user role.
	 *
	 * @param $user_type
	 */
	public function __construct( $user_type ) {
		$this->setup( $user_type );
		$this->check_status();
	}

	/**
	 * Sets up access to dependencies etc.
	 *
	 * @param $user_type
	 */
	protected function setup( $user_type ) {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
		$this->type = $user_type;
	}

	/**
	 * Updates the roster status (to indicate if any changes are pending/being processed etc).
	 *
	 * @param $state
	 * @return bool
	 */
	protected function set_status( $state ) {
		switch( $state ) {
			case self::READY:
			case self::PENDING_CHANGES:
				return update_site_option( 'wpan_roster_' . $this->type . '_status', $state );
				break;
		}
		return false;
	}

	/**
	 * Checks the current status of the roster (it may either be ready - essentially just waiting
	 * for further actions - or have an update in progress).
	 */
	protected function check_status() {
		$state = get_site_option( 'wpan_roster_' . $this->type . '_status', self::READY );

		switch( $state ) {
			case self::READY:
			case self::PENDING_CHANGES:
				$this->status = $state;
			break;

			default:
				Log::warning( sprintf( __( 'Could not reliably determine state for roster %s.', 'wpan' ), $this->type ) );
			break;
		}
	}

	/**
	 * Returns true if roster updates are still in progress.
	 *
	 * @return bool
	 */
	public function pending_changes() {
		return ( self::PENDING_CHANGES === $this->status );
	}

	/**
	 * Returns the $source_meta array for the current update task.
	 *
	 * @return array
	 */
	public function update_job_details() {
		return $this->source_meta;
	}

	/**
	 * Sets the source roster data with which to create, update and validate current roster data. This data is
	 * expected to be an array of arrays, where each inner array is of the form:
	 *
	 *     [ 'uaid' => $unique_identifier, # required
	 *       'role' => $academic_role,     # required only for new user account creation
	 *       'email' => $email_addr,       # optional for students
	 *       'username' => $username,      # required only if a new user account is created (but not for updates)
	 *       'password' => $password,      # required only if a new user account is created (but not for updates)
	 *       'supervisor' => $teacher_uaid # can be used to provide a teacher supervisor for new student sites
	 *       'other' => $arbitrary,        # arbitrary fields for student params, etc
	 *       ... => ... ]
	 *
	 * @param array $data
	 */
	public function update_roster( array $data ) {
		if ( ! isset($this->source_meta['initiated']) ) $this->source_meta['initiated'] = date( 'Y-m-d H:i:s' );
		if ( ! isset($this->source_meta['updated']) ) $this->source_meta['updated'] = date( 'Y-m-d H:i:s' );

		update_site_option( 'wpan_' . $this->type . '_roster_update_data', $data );
		update_site_option( 'wpan_' . $this->type . '_roster_update_meta', $this->source_meta );
	}

	/**
	 * Processes roster updates.
	 *
	 * This is performed in batches. For example, assuming batches of 20 and a scheduled task running at 5 minute
	 * intervals, a rate of 100 new accounts (or account updates) per hour could be 'background' processed.
	 */
	public function process_update() {
		$this->load_source();
		$batch_size = apply_filters( 'wpan_roster_update_batch', self::BATCH_SIZE );
		$count = 0;

		while ( ++$count <= $batch_size ) {
			$record = array_shift( $this->source );
			$this->update_record( $record );
		}
	}

	/**
	 * Takes a roster update record and attempts to process it. It is expected to be an associative array
	 * as per the update_roster() documentation.
	 *
	 * @param $record
	 */
	protected function update_record( $record ) {
		if ( ! $this->is_valid_record( $record ) ) return;

	}

	/**
	 * Inspects a roster update record and makes sure it is valid and fit for use.
	 *
	 * @param $record
	 * @return bool
	 */
	protected function is_valid_record( $record ) {
		if ( ! is_array( $record ) ) {
			Log::error( __('Unusable roster update record. Record will be skipped.', 'wpan' ) );
			return false;
		}

		if ( ! isset( $record['uaid'] ) ) {
			Log::error( __('Unusable roster update record (UAID field is absent). Record will be skipped.', 'wpan' ) );
			return false;
		}

		return true;
	}

	/**
	 * Loads the source data if available.
	 */
	protected function load_source() {
		$this->source = (array) get_site_option( 'wpan_' . $this->type . '_roster_update_data', array() );
		$this->source_meta = (array) get_site_option( 'wpan_' . $this->type . '_roster_update_meta', array() );
		$this->source_loaded = true;
	}

	/**
	 * Indicates if users assigned to the teacher (academic, network-wide) role exist.
	 *
	 * @return bool
	 */
	public function has_users() {
		if ( ! isset($this->total_users) )
			$this->total_users = $this->users->count_academic_role( $this->type );

		return ( 0 === $this->total_users ) ? false : true;
	}

	/**
	 * Loads and returns an array of teachers matching the current request parameters.
	 *
	 * @return array
	 */
	public function get_users() {
		list( $limit, $page ) = apply_filters( 'wpan_roster_pagination', array( -1, 1 ) );
		$offset = ( $page * $limit ) - $limit;

		$order = apply_filters( 'wpan_roster_order', 'ASC' );
		$order_by = apply_filters( 'wpan_roster_order_by', 'login' );

		return $this->users->get_where( $this->type, $limit, $offset, $order, $order_by );
	}


}