<?php
namespace WPAN\Hub;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Users;


class Teachers
{
	/**
	 * @var AdminTable
	 */
	protected $table;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Users
	 */
	protected $users;


	/**
	 * Sets up the Teachers object.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
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
			'current' => __( 'Current Roster', 'wpan' ),
			'update' => __( 'Update/Import', 'wpan' )
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
		return 'Updates';
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
		if ( ! $this->have_teachers() ) return;

		foreach ( $this->get_teachers() as $teacher )
			$this->table->add_row( $this->form_teacher_row( $teacher ) );
	}

	/**
	 * Indicates if users assigned to the teacher (academic, network-wide) role exist.
	 *
	 * @return bool
	 */
	protected function have_teachers() {
		$num_teachers = $this->users->count_academic_role( Users::TEACHER );
		return ( 0 === $num_teachers ) ? false : true;
	}

	/**
	 * Loads and returns an array of teachers matching the current request parameters.
	 *
	 * @return array
	 */
	protected function get_teachers() {
		$limit = apply_filters( 'wpan_roster_items_per_page', 20 );
		$offset = ( $this->table->get_page_num() * $limit ) - $limit;

		$order = apply_filters( 'wpan_roster_order', 'ASC' );
		$order_by = apply_filters( 'wpan_roster_order_by', 'login' );

		return $this->users->get_teachers( $limit, $offset, $order, $order_by );
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
}