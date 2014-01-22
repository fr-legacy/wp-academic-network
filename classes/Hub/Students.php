<?php
namespace WPAN\Hub;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Roster,
	WPAN\Users;


class Students
{
	/**
	 * @var AdminTable
	 */
	protected $table;

	/**
	 * @var Roster
	 */
	protected $roster;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * Records any errors, notices etc that need to be relayed to the user.
	 *
	 * @var array
	 */
	protected $notices = array();


	/**
	 * Sets up the Students object.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
		$this->roster = new Roster( Users::STUDENT );
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
		$this->roster_uploads();
		$this->action_requests();
		add_filter( 'wpan_roster_pagination', array( $this, 'pagination' ) );
	}

	/**
	 * Returns the Teacher admin view.
	 *
	 * @return string
	 */
	public function get_page() {
		return $this->menu() . $this->page();
	}

	/**
	 * Returns the markup for the subtab menu.
	 *
	 * @return string
	 */
	protected function menu() {
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub&tab=students' );
		$subtabs = array(
			'current' => __( 'Current roster', 'wpan' ),
			'update' => __( 'Update/import', 'wpan' ),
			'process' => __( 'Realtime processor', 'wpan' )
		);
		return WordPress::sub_menu( $subtabs, $base_url );
	}

	/**
	 * Returns the page itself.
	 *
	 * @return string
	 */
	protected function page() {
		$subtab = isset( $_GET['subtab'] ) ? $_GET['subtab'] : '';

		switch ( $subtab ) {
			case 'process': return $this->process_view(); break;
			case 'update': return $this->updates_view(); break;
			default: return $this->roster_view(); break;
		}
	}

	/**
	 * Returns the roster updates UI.
	 *
	 * @return string
	 */
	protected function updates_view() {
		return View::admin( 'hub/student-roster/updates', array(
			'work_in_progress' => $this->roster->pending_changes(),
			'job_details' => $this->roster->get_job_details()
		) );
	}

	/**
	 * Returns the current roster view.
	 *
	 * @return string
	 */
	protected function roster_view() {
		$this->setup_table();
		$this->populate_table();

		$view = $this->table->as_string();
		$view .= '<input type="hidden" name="check" value="' . wp_create_nonce( 'student_actions' . get_current_user_id() ) . '" />';

		return $view;
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function setup_table() {
		$bulk_actions = array(
			'unselected' => __( 'Bulk actions', 'wpan' ),
			'validate' => __( 'Validate', 'wpan' ),
			'purge' => __( 'Purge completely', 'wpan' )
		);

		// Set up basic structure
		$this->table = AdminTable::build( 'student_roster' )->use_checkbox( true )
			->set_bulk_actions( $bulk_actions )
			->add_column( 'user', __( 'User', 'wpan' ) )
			->add_column( 'validated', __( 'Validated', 'wpan' ) )
			->add_column( 'blog', __( 'Primary blog', 'wpan' ) );

		// Pagination
		list( $per_page ) = $this->pagination();
		$num_students = $this->roster->how_many_users();
		$pages = (int) ceil( $num_students / $per_page );
		if ( 1 > $pages ) $pages = 1;

		$this->table->set_total_pages( $pages )->auto_set_page();
	}

	/**
	 * Populates the table with teachers data.
	 */
	protected function populate_table() {
		if ( ! $this->roster->has_users() ) return;

		foreach ( $this->roster->get_users() as $student )
			$this->table->add_row( $this->form_student_row( $student ) );
	}

	/**
	 * Used to ensure the roster object knows which page of results has been requested and
	 * how many results should be returned per page.
	 *
	 * @return array
	 */
	public function pagination() {
		$per_page = apply_filters( 'wpan_roster_students_per_page', 12 );
		return array( $per_page, $this->table->get_page_num() );
	}

	/**
	 * Returns a data row representing the provided teacher user object.
	 *
	 * @param WP_User $teacher
	 * @return array
	 */
	protected function form_student_row( WP_User $student ) {
		return array(
			'row_id' => $student->ID,
			'user' => View::admin( 'hub/student-roster/user-details', array( 'student' => $student, 'users' => $this->users ) ),
			'validated' => View::admin( 'hub/student-roster/last-validated', array( 'days' => $this->users->num_days_since_validated( $student->ID ) ) ),
			'primary_blog' => View::admin( 'hub/student-roster/blog-details', array( 'blog_id' => $this->network->get_primary_blog( $student->ID ) ) )
		);
	}

	/**
	 * Listens out for roster uploads.
	 */
	protected function roster_uploads() {
		if ( ! isset( $_FILES['student_roster'] ) ) return;
		if ( ! isset( $_POST['origin'] ) ) return;

		if ( ! wp_verify_nonce( $_POST['check'], get_current_user_id() . $_POST['origin'] . 'Updated roster' ) ) {
			$warning = __( 'A student roster update was uploaded to the server but was rejected due to security concerns.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		if ( UPLOAD_ERR_OK !== $_FILES['student_roster']['error'] ) {
			$warning = __( 'A student roster update was uploaded but a system error occured and it cannot be processed.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		$records = $this->preprocess_upload();

		if ( ! is_array( $records ) || empty( $records ) ) {
			$warning = __( 'The roster update was received but appears to have been empty (or the contents could not be understood).', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		$this->roster->update_roster( $records );

		// If the user wants the changes to be processed instantly, move them to the realtime processor
		if ( isset( $_POST['process_instantly'] ) ) {
			$_GET['subtab'] = 'process';
			$_GET['do'] = 'now';
		}
	}

	/**
	 * If a roster update file was successfully uploaded, lets aim to convert it from CSV to an array
	 * structure.
	 *
	 * @return mixed null | array
	 */
	protected function preprocess_upload() {
		$csv = file_get_contents( $_FILES['student_roster']['tmp_name'] );

		if ( false === $csv ) {
			$warning = __( 'The roster update was received but could not be loaded into memory and parsed.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return null;
		}

		// Normalize line endings (support *nix, MacOS and Windows formats in the source file)
		$csv = str_replace( "\n\n", "\n", str_replace( "\r", "\n", $csv ) );

		// Break into an array representing each individual line
		$records = explode( "\n", $csv );
		unset( $csv );

		if ( false === $records || empty( $records ) ) {
			$warning = __( 'The roster update was received but appears to have been empty (or the contents could not be understood).', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return null;
		}

		// The first line should be column headers: use to build an index:property map
		$headers = array_shift( $records );
		$map = array_flip( str_getcsv( str_replace( ' ', '', $headers ) ) );

		// Convert each record to an array structure based on the index:property map
		foreach ( $records as &$record ) {
			$source = (array) str_getcsv( $record );
			$source = array_map( 'trim', $source );
			$record = $map;

			foreach ( $record as &$value ) {
				$index = (int) $value;
				if ( isset( $source[$index] ) ) $value = $source[$index];
				else $value = '';
			}
		}

		return $records;
	}

	/**
	 * Generates the realtime processor view (useful to review the status of work in progress and to
	 * force processing of roster updates without waiting for scheduled tasks to fire).
	 */
	protected function process_view() {
		return View::admin( 'hub/roster-processor', array(
			'work_in_progress' => $this->roster->pending_changes(),
			'job_details' => $this->roster->get_job_details(),
			'type' => Users::STUDENT
		) );
	}

	/**
	 * Listens for and handles bulk/single item actions.
	 */
	protected function action_requests() {
		// Security check
		if ( ! isset( $_REQUEST['check'] ) ) return;
		if ( ! wp_verify_nonce( $_REQUEST['check'], 'student_actions' . get_current_user_id() ) ) return;

		// Sanity checks
		if ( isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action'];
		if ( isset( $_REQUEST['action_request_2'] ) ) $action = $_REQUEST['action_2'];
		if ( ! isset( $action ) ) return;

		switch ( $action ) {
			case 'purge': $this->purge_requests(); break;
			case 'validate': $this->validate_requests(); break;
		}
	}

	/**
	 * Processes student purge requests.
	 */
	protected function purge_requests() {
		if ( ! current_user_can( 'wpan_delete_user' ) || ! current_user_can( 'wpan_delete_site' ) ) {
			Log::warning( sprintf( __( 'Attempt made to purge student account by unauthorized user %d.', 'wpan' ), get_current_user_id() ) );
			return;
		}

		// Do we have items to action?
		if ( ! isset($_REQUEST['item']) ) {
			Log::warning( __( 'Unable to start purge operation, no items specified.', 'wpan' ) );
			return;
		}

		// Purge!
		foreach ( (array) $_REQUEST['item'] as $user )
			$this->roster->purge( $user );
	}

	/**
	 * Processes student validation requests.
	 */
	protected function validate_requests() {
		if ( ! current_user_can( 'wpan_manage_users' ) ) {
			Log::warning( sprintf( __( 'Attempt made to (re-)validate student account by unauthorized user %d.', 'wpan' ), get_current_user_id() ) );
			return;
		}

		// Do we have items to action?
		if ( ! isset($_REQUEST['item']) ) {
			Log::warning( __( 'Unable to do validation, no items specified.', 'wpan' ) );
			return;
		}

		// Validate!
		foreach ( (array) $_REQUEST['item'] as $user )
			$this->users->validate_user( $user );
	}
}