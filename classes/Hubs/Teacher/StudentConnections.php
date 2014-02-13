<?php
namespace WPAN\Hubs\Teacher;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Relationships,
	WPAN\Users;


class StudentConnections
{
	/**
	 * @var AdminTable
	 */
	protected $table;

	/**
	 * @var Relationships
	 */
	protected $relationships;

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
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
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
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_teacher_hub&tab=students' );
		$subtabs = array(
			'current' => __( 'Existing connections', 'wpan' ),
			'requested' => __( 'Requests', 'wpan' )
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
			case 'requested': return $this->incoming_requests(); break;
			default: return $this->current_connections(); break;
		}
	}

	/**
	 * Provide a view of all incoming link/unlink requests.
	 */
	protected function incoming_requests() {
		return 'table incoming';
	}

	/**
	 * Provide a view of all established connections to students/student blogs.
	 */
	protected function current_connections() {
		$connections = $this->network->get_supervised_blogs( get_current_user_id() );
		$this->setup_table( $connections );
		$this->populate_table( $connections );
		return $this->table->as_string();
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function setup_table( $connections ) {
		$bulk_actions = array(
			'unselected' => __( 'Bulk actions', 'wpan' ),
			'disconnect' => __( 'Disconnect', 'wpan' )
		);

		// Set up basic structure
		$this->table = AdminTable::build( 'student_roster' )->use_checkbox( true )
			->set_bulk_actions( $bulk_actions )
			->add_column( 'user', __( 'Student', 'wpan' ) )
			->add_column( 'blog', __( 'Blog', 'wpan' ) );

		// Pagination
		list( $per_page ) = $this->pagination( $connections );
		$pages = (int) ceil( count( $connections ) / $per_page );
		if ( 1 > $pages ) $pages = 1;

		$this->table->set_total_pages( $pages )->auto_set_page();
	}

	/**
	 * Populates the table with teachers data.
	 */
	protected function populate_table( $connections ) {
		if ( empty( $connections ) ) return;

		foreach ( $connections as $connection )
			$this->table->add_row( array(
				'row_id' => $connection['blog_id'],
				'user' => View::admin( 'hub/student-connections/user-details', array( 'data' => $connection, 'users' => $this->users ) ),
				'validated' => View::admin( 'hub/student-connections/blog-details', array( 'data' => $connection ) )
			) );
	}

	/**
	 * Used to ensure the roster object knows which page of results has been requested and
	 * how many results should be returned per page.
	 *
	 * @param $connections
	 * @return array
	 */
	public function pagination( array &$connections ) {
		$per_page = apply_filters( 'wpan_connected_students_per_page', 12 );
		$current_page = $this->table->get_page_num();

		$offset = ( $per_page * $current_page ) - $per_page;
		$connections = array_slice( $connections, $offset, $per_page );

		return array( $per_page, $current_page );
	}
}