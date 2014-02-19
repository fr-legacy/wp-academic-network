<?php
namespace WPAN\Hubs\Network;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Roster,
	WPAN\Users;


class Observers
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
		$this->roster = new Roster( Users::OBSERVER );
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
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
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub&tab=observers' );
		$subtabs = array(
			'list' => __( 'Active observers/parents', 'wpan' ),
			'edit' => __( 'Edit observer', 'wpan' )
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
			case 'edit': return $this->editor_view(); break;
			default: return $this->list_view(); break;
		}
	}

	/**
	 * Returns the current roster view.
	 *
	 * @return string
	 */
	protected function list_view() {
		$this->list_table();
		$this->list_data();

		$view = $this->table->as_string();
		$view .= '<input type="hidden" name="check" value="' . wp_create_nonce( 'student_actions' . get_current_user_id() ) . '" />';

		return $view;
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function list_table() {
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
	 * Populates the table with observer data.
	 */
	protected function list_data() {
		if ( ! $this->roster->has_users() ) return;

		foreach ( $this->roster->get_users() as $observer )
			$this->table->add_row( $this->form_row( $observer ) );
	}

	/**
	 * Used to ensure the roster object knows which page of results has been requested and
	 * how many results should be returned per page.
	 *
	 * @return array
	 */
	public function pagination() {
		$per_page = apply_filters( 'wpan_roster_observers_per_page', 12 );
		return array( $per_page, $this->table->get_page_num() );
	}

	/**
	 * Returns a data row representing the provided observer user object.
	 *
	 * @param WP_User $observer
	 * @return array
	 */
	protected function form_row( WP_User $observer ) {
		return array(
			'row_id' => $observer->ID,
			'user' => View::admin( 'hub/student-roster/user-details', array( 'student' => $observer, 'users' => $this->users ) ),
			'primary_blog' => View::admin( 'hub/student-roster/blog-details', array( 'blog_id' => $this->network->get_primary_blog( $observer->ID ) ) )
		);
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
}