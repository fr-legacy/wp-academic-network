<?php
namespace WPAN\Helpers;


/**
 * Utility functions and helpers to aid integration within WordPress.
 *
 * @package WPAN
 */
class WordPress {
	/**
	 * Accepts an admin menu position, determines if it is free (or not) then considers incrementing it.
	 * The result may be a decimal value and will be expressed as a string.
	 *
	 * @param $desired_position
	 * @return string
	 */
	public static function safe_menu_position( $desired_position ) {
		global $menu;
		$desired_position = absint( $desired_position );

		while ( isset($menu[$desired_position] ) ) {
			$desired_position += ( rand( 1, 100 ) / 100 );
		}

		return (string) $desired_position;
	}


	public static function safe_form_action() {
		global $wp_query;

		if ( is_admin() ) return '';
		if ( is_home() ) return home_url();
		if ( is_tax() ) return get_term_link( $wp_query->queried_object->term_id );
		if ( is_singular() ) return get_permalink( get_the_id() );

		return home_url();
	}


	/**
	 * Generates a .subsubsub menu for admin screens.
	 *
	 * @return string
	 */
	public static function tab_menu( array $tabs, $base_url ) {
		$output = '<h3 class="nav-tab-wrapper">';

		// What is the current tab?
		$tab_keys = array_keys( $tabs );
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tab_keys ) ) $current = $_GET['tab'];
		else $current = array_shift( $tab_keys );

		foreach ( $tabs as $slug => $label ) {
			$class = ( $current === $slug ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			$href = esc_url( add_query_arg( 'tab', $slug, $base_url ) );
			$output .= '<a href="' . $href . '" class="' . $class . '">' . $label . '</a> ';
		}

		return $output . '</h3>';
	}

	/**
	 * Attempts to generate a new user account. Accepts the same array of user params as would
	 * wp_insert_user().
	 *
	 * The primary difference between this method and wp_insert_user() is that this method assumes the
	 * password field has already been hashed (ie, it was not stored within the database in plain text
	 * while waiting to be actioned).
	 *
	 * @see \wp_insert_user()
	 * @param array $user_params
	 * @return mixed boolean false on failure or the user ID upon success
	 */
	public static function create_user( array $user_params ) {
		global $wpdb;

		$user_id = \wp_insert_user( $user_params );
		if ( \is_wp_error( $user_id ) ) return false;

		// Update password (to prevent it being double-hashed)
		$password = $user_params['user_pass'];
		$result = $wpdb->update( $wpdb->users, array( 'user_pass' => $password ), array( 'ID', $user_id ),
			array( '%s', '%s' ), array( '%s', '%d' ) );

		return ( false === $result ) ? false : $user_id;
	}

	/**
	 * Returns the domain to be used when registering new sites. Assumes a standard subdir installation
	 * where the domain is consistent across the network; can be filtered where this is undesirable.
	 */
	public static function get_new_site_domain() {
		$site = get_current_site();
		$domain = isset( $site->domain ) ? $site->domain : '';
		return apply_filters( 'wpan_wordpress_new_site_domain', $domain );
	}
}