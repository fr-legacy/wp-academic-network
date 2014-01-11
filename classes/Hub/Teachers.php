<?php
namespace WPAN\Hub;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Roster,
	WPAN\Users;


class Teachers
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
	 * Records any errors, notices etc that need to be relayed to the user.
	 *
	 * @var array
	 */
	protected $notices = array();


	/**
	 * Sets up the Teachers object.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->roster = new Roster( Users::TEACHER );
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
		$this->roster_uploads();
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
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub&tab=teachers' );
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
		return View::admin( 'hub/roster-updates-teacher', array(
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

		return $this->table->as_string();
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function setup_table() {
		$bulk_actions = array(
			'unselected' => __( 'Bulk actions', 'wpan' ),
			'remove' => __( 'Purge completely', 'wpan' )
		);

		$this->table = AdminTable::build( 'teacher_roster' )->use_checkbox( true )
			->set_bulk_actions( $bulk_actions )
			->add_column( 'user', __( 'User', 'wpan' ) )
			->add_column( 'blog', __( 'Primary blog', 'wpan' ) );
	}

	/**
	 * Populates the table with teachers data.
	 */
	protected function populate_table() {
		if ( ! $this->roster->has_users() ) return;

		foreach ( $this->roster->get_users() as $teacher )
			$this->table->add_row( $this->form_teacher_row( $teacher ) );
	}

	/**
	 * Used to ensure the roster object knows which page of results has been requested and
	 * how many results should be returned per page.
	 *
	 * @return array
	 */
	public function pagination() {
		$per_page = apply_filters( 'wpan_roster_teachers_per_page', 20 );
		return array( $per_page, $this->table->get_page_num() );
	}

	/**
	 * Returns a data row representing the provided teacher user object.
	 *
	 * @param WP_User $teacher
	 * @return array
	 */
	protected function form_teacher_row( WP_User $teacher ) {
		return array(
			'row_id' => $teacher->ID,
			'user' => $teacher->user_login,
			'primary_blog' => $this->network->get_primary_blog( $teacher->ID )
		);
	}

	/**
	 * Listens out for roster uploads.
	 */
	protected function roster_uploads() {
		if ( ! isset( $_FILES['teacher_roster'] ) ) return;
		if ( ! isset( $_POST['origin'] ) ) return;

		if ( ! wp_verify_nonce( $_POST['check'], get_current_user_id() . $_POST['origin'] . 'Updated roster' ) ) {
			$warning = __( 'A teacher roster update was uploaded to the server but was rejected due to security concerns.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		if ( UPLOAD_ERR_OK !== $_FILES['teacher_roster']['error'] ) {
			$warning = __( 'A teacher roster update was uploaded but a system error occured and it cannot be processed.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		$records = $this->preprocess_upload();

		if ( ! is_array( $records ) || empty( $records ) ) {
			$warning = __( 'The roster update was received but appears to have been empty (or the contents could not be understood.', 'wpan' );
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
		$csv = file_get_contents( $_FILES['teacher_roster']['tmp_name'] );

		if ( false === $csv ) {
			$warning = __( 'The roster update was received but could not be loaded into memory and parsed.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		// Normalize line endings (support *nix, MacOS and Windows formats in the source file)
		$csv = str_replace( "\n\n", "\n", str_replace( "\r", "\n", $csv ) );

		// Break into an array representing each individual line
		$records = explode( "\n", $csv );
		unset( $csv );

		if ( false === $records || empty( $records ) ) {
			$warning = __( 'The roster update was received but appears to have been empty (or the contents could not be understood.', 'wpan' );
			$this->notices[] = $warning;
			Log::warning( $warning );
			return;
		}

		// The first line should be column headers: use to build an index:property map
		$headers = array_shift( $records );
		$map = array_flip( str_getcsv( str_replace( ' ', '', $headers ) ) );

		// Convert each record to an array structure based on the index:property map
		foreach ( $records as &$record ) {
			$source = (array) str_getcsv( $record );
			$source = array_map( 'trim', $source );
			$record = $map;

			foreach ( $record as $key => &$value ) {
				$index = (int) $value;
				$value = $source[$index];
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
			'job_details' => $this->roster->get_job_details()
		) );
	}
}