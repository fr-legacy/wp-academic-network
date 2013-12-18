<?php
namespace WPAN\Hub;
use WPAN\Network;
use WPAN\WordPress;
use WPAN\View;


class Manager {
	/**
	 * Container for the Hub object.
	 *
	 * @var Manager
	 */
	protected static $object;


	public static function object() {
		if ( isset( self::$object ) ) return self::$object;
		self::$object = new self();
		return self::$object;
	}

	/**
	 * Sets up any hub-level facilities, admin screens and functionality that is required.
	 */
	protected function __construct() {
		if ( ! Network::is_hub() ) return;
		$this->common_facilities();
		$this->admin_facilities();
		$this->front_facilities();
	}

	/**
	 * Elements common to admin and frontend.
	 */
	protected function common_facilities() {

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
			__( 'Hub', 'wpan' ),
			'wpan_access_hub_tools',
			'wpan_hub',
			array( $this, 'hub_screen' ),
			'',
			WordPress::safe_menu_position( 2 )
		) );

		call_user_func_array( 'add_menu_page', $menu_params );
	}

	public function hub_screen() {
		echo View::admin( 'hub/frame', array(
			'menu_pages' => $this->hub_page_tabs(),
			'view' => $this->current_hub_tab()
		) );
	}

	protected function hub_page_tabs() {
		$menu_pages = apply_filters( 'wpan_hub_admin_tabs', array(
			'dashboard' => __( 'Dashboard', 'wpan' ),
			'requests' => __( 'Requests', 'wpan' )
		) );

		$admin_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_hub' );
		$tab_menu = WordPress::subsubsub_tab_menu( $menu_pages, $admin_url );

		return apply_filters( 'wpan_hub_page_tab_menu', $tab_menu );
	}

	protected function current_hub_tab() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		switch ( $tab ) {
			case 'requests':
				return Requests::controller();
			break;
			case 'dashboard':
			default:
				return View::admin( 'hub/dashboard' );
			break;
		}

	}

	/**
	 * Front-end specific elements.
	 */
	protected function front_facilities() {
		if ( is_admin() ) return;
	}

}