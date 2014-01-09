<?php
namespace WPAN\Hub;

use WPAN\Core,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Roster,
	WPAN\Users;


class Manager {
	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Roster
	 */
	protected $teacher_roster;

	/**
	 * @var Roster
	 */
	protected $student_roster;


	/**
	 * Sets up any hub-level facilities, admin screens and functionality that is required.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		if ( ! $this->hub_environment() ) return;
		$this->admin_facilities();
	}

	/**
	 * Checks if the current admin environment is the main site and the current user has
	 * at least the wpan_access_hub_tools capability.
	 *
	 * @return bool
	 */
	protected function hub_environment() {
		if ( ! is_admin() ) return false;
		if ( ! $this->network->is_hub() ) return false;
		if ( ! wp_get_current_user()->has_cap( 'wpan_access_hub_tools' ) ) return false;
		return true;
	}

	/**
	 * Admin-only elements.
	 */
	protected function admin_facilities() {
		if ( ! is_admin() ) return;
		add_action( 'admin_menu', array( $this, 'setup_hub_menu' ) );
	}

	/**
	 * Creates the menu used to access hub tools and services.
	 */
	public function setup_hub_menu() {
		$menu_params = apply_filters( 'wpan_hub_menu', array(
			__( 'Hub Dashboard', 'wpan' ),
			__( 'Academic Hub', 'wpan' ),
			'wpan_access_hub_tools',
			'wpan_hub',
			array( $this, 'hub_screen' ),
			'',
			WordPress::safe_menu_position( 2 )
		) );

		call_user_func_array( 'add_menu_page', $menu_params );
	}

	/**
	 * Puts together the admin page.
	 */
	public function hub_screen() {
		echo View::admin( 'hub/frame', array(
			'menu_pages' => $this->hub_page_tabs(),
			'view' => $this->current_hub_tab()
		) );
	}

	/**
	 * Generates the hub admin screen tabs.
	 *
	 * Extensions/third party plugins can add to these by making use of the wpan_hub_admin_tabs
	 * and wpan_current_hub_page filter hooks.
	 *
	 * @return mixed|void
	 */
	protected function hub_page_tabs() {
		$menu_pages = apply_filters( 'wpan_hub_admin_tabs', array(
			'dashboard' => __( 'Dashboard', 'wpan' ),
			'teachers' => __( 'Teachers', 'wpan' ),
			'students' => __( 'Students', 'wpan' ),
			'maintenance' => __( 'Maintenance', 'wpan' )
		) );

		$admin_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub' );
		$tab_menu = WordPress::tab_menu( $menu_pages, $admin_url );

		return apply_filters( 'wpan_hub_page_tab_menu', $tab_menu );
	}

	/**
	 * Returns a view representing the current hub admin view.
	 *
	 * @return View
	 */
	protected function current_hub_tab() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		$html = '';

		switch ( $tab ) {
			case 'teachers': $controller = new Teachers; break;
		}

		if ( isset($controller) && method_exists( $controller, 'get_page' ) )
			$html = $controller->get_page();

		return apply_filters( 'wpan_current_hub_view', $html, $tab );
	}

	/**
	 * Returns the student roster object.
	 *
	 * @return Roster
	 */
	public function get_student_roster() {
		if ( ! isset( $this->student_roster ) )
			$this->student_roster = new Roster( Users::STUDENT );
		return $this->student_roster;
	}
	
	/**
	 * Returns the teacher roster object.
	 *
	 * @return Roster
	 */
	public function get_teacher_roster() {
		if ( ! isset( $this->teacher_roster ) )
			$this->teacher_roster = new Roster( Users::TEACHER );
		return $this->teacher_roster;
	}
}