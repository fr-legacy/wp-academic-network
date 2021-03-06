<?php
namespace WPAN\Hubs\Student;

use WPAN\Core,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Roster,
	WPAN\Users;


class Manager
{
	/**
	 * Sets up network admin hub.
	 */
	public function __construct() {
		if ( ! is_admin() ) return;
		add_action( 'admin_menu', array( $this, 'setup_hub_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_ajax_wpan_roster_update', array( $this, 'roster_worker' ) );
	}

	/**
	 * Creates the menu used to access hub tools and services.
	 */
	public function setup_hub_menu() {
		$menu_params = apply_filters( 'wpan_hub_menu', array(
			__( 'Hub Dashboard', 'wpan' ),
			__( 'Student Hub', 'wpan' ),
			'edit_pages',
			'wpan_student_hub',
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
		$view = array(
			'menu_pages' => $this->hub_page_tabs(),
			'view' => $this->current_hub_tab(),
			'subtitle' => __( 'Student Hub', 'wpan' )
		);

		echo View::admin( 'hub/frame', $view );
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
			'teachers' => __( 'Teachers', 'wpan' ),
			/*'observers' => __( 'Observers (parents)', 'wpan' ),*/
		) );

		$admin_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_student_hub' );
		$tab_menu = WordPress::tab_menu( $menu_pages, $admin_url );

		return apply_filters( 'wpan_hub_page_tab_menu', $tab_menu );
	}

	/**
	 * Returns a view representing the current hub admin view.
	 *
	 * @return View
	 */
	protected function current_hub_tab() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'teachers';
		$html = '';

		switch ( $tab ) {
			case 'teachers': $controller = new TeacherConnections; break;
		}

		if ( isset($controller) && method_exists( $controller, 'get_page' ) )
			$html = $controller->get_page();

		return apply_filters( 'wpan_current_hub_view', $html, $tab );
	}

	/**
	 * Enqueues admin scripts and styles.
	 */
	public function admin_assets() {
		wp_enqueue_style( 'wpan_admin_css', WPAN_URL . 'resources/wpan-admin.css' );
	}
}