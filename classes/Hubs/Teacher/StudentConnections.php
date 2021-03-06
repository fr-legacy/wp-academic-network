<?php
namespace WPAN\Hubs\Teacher;

use WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Relationships,
	WPAN\Requests,
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
	 * @var Requests
	 */
	protected $requests;

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
	 * Lets different controller actions set separate bulk actions.
	 *
	 * @var array
	 */
	protected $bulk_actions = array();


	/**
	 * Sets up the Students object.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->relationships = Core::object()->relationships();
		$this->requests = Core::object()->requests();
		$this->users = Core::object()->users();
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
		$this->action_requests();
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
			'requested' => __( 'Pending requests', 'wpan' )
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
		$notices = apply_filters( 'wpan_notices', $this->notices() );

		switch ( $subtab ) {
			case 'requested': return $notices . $this->requests_view(); break;
			default: return $notices . $this->connections_view(); break;
		}
	}

	/**
	 * Returns a 'package' of notices to display, if any are set (else an empty string).
	 *
	 * @return string
	 */
	protected function notices() {
		if ( empty( $this->notices ) ) return '';
		$package = '<div class="notices">';

		foreach ( $this->notices as $notice ) $package .= '<p>' . $notice . '</p>';
		return $package . '</div>';
	}

	/**
	 * Provide a view of all incoming link/unlink requests.
	 */
	protected function requests_view() {
		$requests = $this->requests_list();

		$this->bulk_actions = ( array(
			'unselected' => __( 'Bulk actions', 'wpan' ),
			'approve' => __( 'Approve', 'wpan' ),
			'decline' => __( 'Decline', 'wpan' )
		) );

		$this->requests_table( $requests );
		$this->requests_data( $requests );
		$this->requests_pagination( $requests );

		return $this->table->as_string();
	}

	/**
	 * Builds a list of requests, ordered with unlink requests first followed by link requests.
	 *
	 * @return array
	 */
	protected function requests_list() {
		$teacher_id = get_current_user_id();
		$unlinks = $this->relationships->list_requests( $teacher_id, Relationships::STUDENT_TEACHER_UNLINK );
		$links = $this->relationships->list_requests( $teacher_id, Relationships::STUDENT_TEACHER_LINK );
		return array_merge( $unlinks, $links );
	}

	/**
	 * Populates the table with incoming connection requests.
	 */
	protected function requests_data( $requests ) {
		if ( empty( $requests ) ) return;

		foreach ( $requests as $request ) {
			$user = get_user_by( 'id', $request->from );
			$blog = get_blog_details( $request->data->student_blog );

			$this->table->add_row( array(
				'row_id' => $request->ID,
				'user' => View::admin( 'hub/student-requests/user-details', array( 'request' => $request, 'users' => $this->users, 'student' => $user ) ),
				'type' => View::admin( 'hub/student-requests/request-type', array( 'request' => $request ) ),
				'data' => View::admin( 'hub/student-requests/blog-details', array( 'blog' => $blog ) )
			) );
		}
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function requests_table( array $requests ) {
		// Set up basic structure
		$this->table = AdminTable::build( 'incoming_student_requests' )->use_checkbox( true )
			->set_bulk_actions( $this->bulk_actions )
			->add_column( 'user', __( 'Student', 'wpan' ) )
			->add_column( 'type', __( 'Type', 'wpan' ) )
			->add_column( 'blog', __( 'Blog', 'wpan' ) );

		// Pagination
		list( $per_page ) = $this->requests_pagination( $requests );
		$pages = (int) ceil( count( $requests ) / $per_page );
		if ( 1 > $pages ) $pages = 1;

		$this->table->set_total_pages( $pages )->auto_set_page();
	}

	/**
	 * Used to paginate the results of connections and determine how many results should be
	 * returned per page.
	 *
	 * @param $requests
	 * @return array
	 */
	public function requests_pagination( array &$requests ) {
		$per_page = apply_filters( 'wpan_student_requests_per_page', 12 );
		$current_page = $this->table->get_page_num();

		$offset = ( $per_page * $current_page ) - $per_page;
		$requests = array_slice( $requests, $offset, $per_page );

		return array( $per_page, $current_page );
	}

	/**
	 * Provide a view of all established connections to students/student blogs.
	 */
	protected function connections_view() {
		$connections = $this->network->get_supervised_blogs( get_current_user_id() );
		$connections = $this->connections_search_filter( $connections );

		$this->bulk_actions = ( array(
			'unselected' => __( 'Bulk actions', 'wpan' ),
			'disconnect' => __( 'Disconnect', 'wpan' )
		) );

		$this->connections_table( $connections );
		$this->connections_data( $connections );

		return $this->table->as_string();
	}

	/**
	 * Filters connections according to any search keywords that have been set.
	 *
	 * Since the connection list isn't obtained via a WP_Query/WP_User_Query-like
	 * mechanism we do some filtering of our own to achieve this.
	 *
	 * @param $connections
	 */
	protected function connections_search_filter( $connections ) {
		$search = isset( $_REQUEST['s'] ) ? trim( (string) $_REQUEST['s'] ) : '';
		if ( empty( $search ) ) return $connections;

		$new_list = array();

		foreach ( $connections as $connection ) {
			$blog_match    = ( false !== stripos( $connection['blog_name'], $search ) );
			$student_match = ( false !== stripos( $connection['student_name'], $search ) );
			$url_match     = ( false !== stripos( $connection['blog_url'], $search ) );

			if ( $blog_match || $student_match || $url_match )
				$new_list[] = $connection;
		}

		return $new_list;
	}

	/**
	 * Sets up the basic table view structure.
	 */
	protected function connections_table( $connections ) {
		// Set up basic structure
		$this->table = AdminTable::build( 'student_connections' )->use_checkbox( true )
			->set_bulk_actions( $this->bulk_actions )
			->add_column( 'user', __( 'Student', 'wpan' ) )
			->add_column( 'blog', __( 'Blog', 'wpan' ) )
			->has_search( true )
			->set_search_terms( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '' );

		// Pagination
		list( $per_page ) = $this->connections_pagination( $connections );
		$pages = (int) ceil( count( $connections ) / $per_page );
		if ( 1 > $pages ) $pages = 1;

		$this->table->set_total_pages( $pages )->auto_set_page();
	}

	/**
	 * Populates the table with established connections.
	 */
	protected function connections_data( $connections ) {
		if ( empty( $connections ) ) return;

		foreach ( $connections as $connection )
			$this->table->add_row( array(
				'row_id' => $connection['blog_id'],
				'user' => View::admin( 'hub/student-connections/user-details', array( 'data' => $connection, 'users' => $this->users ) ),
				'blog' => View::admin( 'hub/student-connections/blog-details', array( 'data' => $connection ) )
			) );
	}

	/**
	 * Used to paginate the results of connections and determine how many results should be
	 * returned per page.
	 *
	 * @param $connections
	 * @return array
	 */
	public function connections_pagination( array &$connections ) {
		$per_page = apply_filters( 'wpan_connected_students_per_page', 12 );
		$current_page = $this->table->get_page_num();

		$offset = ( $per_page * $current_page ) - $per_page;
		$connections = array_slice( $connections, $offset, $per_page );

		return array( $per_page, $current_page );
	}



	/**
	 * Listens for and handles bulk/single item actions.
	 */
	protected function action_requests() {
		// Security check
		if ( ! isset( $_REQUEST['check'] ) ) return;
		if ( ! wp_verify_nonce( $_REQUEST['check'], 'connection_actions' . get_current_user_id() ) ) return;

		// Sanity checks
		if ( isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action'];
		if ( isset( $_REQUEST['action_request_2'] ) ) $action = $_REQUEST['action_2'];
		if ( ! isset( $action ) ) return;

		switch ( $action ) {
			case 'disconnect': $this->action_disconnect(); break;
			case 'approve': $this->action_approve(); break;
			case 'decline': $this->action_decline(); break;
			case 'password_reset': $this->action_password_reset();
		}
	}

	/**
	 * Handles single/bulk approval of requests.
	 */
	protected function action_approve() {
		$teacher_id = get_current_user_id();

		if ( ! $this->users->is_teacher( $teacher_id ) ) {
			Log::warning( sprintf( __( 'Attempt to approve request via teacher hub made by non-teacher %d.', 'wpan' ), $teacher_id ) );
			return;
		}

		// Disconnect!
		foreach ( (array) $_REQUEST['item'] as $request_id ) {
			$this->relationships->approve_request( $request_id );
		}
	}

	/**
	 * Handles single/bulk declining of requests.
	 */
	protected function action_decline() {
		$teacher_id = get_current_user_id();

		if ( ! $this->users->is_teacher( $teacher_id ) ) {
			Log::warning( sprintf( __( 'Attempt to decline request via teacher hub made by non-teacher %d.', 'wpan' ), $teacher_id ) );
			return;
		}

		// Disconnect!
		foreach ( (array) $_REQUEST['item'] as $request_id ) {
			$request = $this->requests->load( $request_id );
			$student_blog = $request->data->student_blog;
			$this->relationships->close_student_teacher_requests( $student_blog, $teacher_id );
		}
	}

	/**
	 * Handles single/bulk requests to disconnect student users/blogs.
	 */
	protected function action_disconnect() {
		$teacher_id = get_current_user_id();

		if ( ! $this->users->is_teacher( $teacher_id ) ) {
			Log::warning( sprintf( __( 'Attempt to disconnect student via teacher hub made by non-teacher %d.', 'wpan' ), $teacher_id ) );
			return;
		}

		// Disconnect!
		foreach ( (array) $_REQUEST['item'] as $blog_id ) {
			$this->network->unassign_teacher_supervisor( $blog_id, $teacher_id );
			$this->relationships->close_student_teacher_requests( $blog_id, $teacher_id );
		}
	}

	/**
	 * Handles password reset requests for students.
	 */
	protected function action_password_reset() {
		$teacher_id = get_current_user_id();

		if ( ! $this->users->is_teacher( $teacher_id ) ) {
			Log::warning( sprintf( __( 'Attempt to reset student password via teacher hub made by non-teacher %d.', 'wpan' ), $teacher_id ) );
			return;
		}

		// Reset
		foreach ( (array) $_REQUEST['item'] as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$password = WordPress::reset_password( $user_id );

			$this->notices[] = sprintf( __( 'Password for user <em>%s</em> (#%d) changed to <code>%s</code> &hellip;please make them aware of this change (or regenerate it if you are unhappy with the result).', 'wpan' ), $user->display_name, $user_id, esc_html( $password ) );
			Log::action( __( 'Password for user %d reset by user %d.', 'wpan' ), $user_id, $teacher_id );
		}
	}
}