<?php
namespace WPAN;


class Hub {
	/**
	 * Container for the Hub object.
	 *
	 * @var Hub
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
			array( $this, 'hub_dashboard' ),
			'',
			WordPress::safe_menu_position( 2 )
		) );

		call_user_func_array( 'add_menu_page', $menu_params );
	}

	public function hub_dashboard() {
		$menu_pages = WordPress::subsubsub_tab_menu( array(
			'dashboard' => __( 'Dashboard', 'wpan' ),
			'requests' => __( 'Requests', 'wpan' )
		), 'dashboard' );

		echo View::admin( 'hub/dashboard', array(
			'menu_pages' => $menu_pages
		) );
	}

	/**
	 * Front-end specific elements.
	 */
	protected function front_facilities() {
		if ( is_admin() ) return;
	}

}