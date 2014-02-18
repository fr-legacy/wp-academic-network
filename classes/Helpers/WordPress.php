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
	 * Generates a tabbed (h3.nav-tab-wrapper) menu for admin screens. It is expected that the
	 * active tab will correspond to a 'tab' param in the URL query.
	 *
	 * @param array $tabs
	 * @param $base_url
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
	 * Generates a horizontal list (ul.subsubsub) menu for admin screens. It is expected that the
	 * active item will correspond to a 'subtab' param in the URL query.
	 *
	 * @param array $tabs
	 * @param $base_url
	 * @return string
	 */
	public static function sub_menu( array $tabs, $base_url ) {
		$output = '<ul class="subsubsub">';
		$items = 0;

		// What is the current tab?
		$tab_keys = array_keys( $tabs );
		if ( isset( $_GET['subtab'] ) && in_array( $_GET['subtab'], $tab_keys ) ) $current = $_GET['subtab'];
		else $current = array_shift( $tab_keys );

		foreach ( $tabs as $slug => $label ) {
			if ( 1 < ++$items ) $output .= ' | ';
			$class = ( $current === $slug ) ? ' class="current" ' : '';

			$href = esc_url( add_query_arg( 'subtab', $slug, $base_url ) );
			$output .= '<li> <a href="' . $href . '"' . $class . '>' . $label . '</a> </li>';
		}

		return $output . '</ul> <br style="clear:both" /> ';
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

	/**
	 * Adds an incrementer to the end of a string, or increments an existing incrementer. For example,
	 * "string" will become "string-1", "string-4" will become "string-5", etc.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function slug_incrementer( $string ) {
		if ( false === strrpos( $string, '-' ) )
			return $string . '-1';

		$hyphen_pos = strrpos( $string, '-' );
		$post_hyphen = substr( $string, $hyphen_pos + 1);
		$int_version = (int) $post_hyphen;

		if ( is_numeric( $post_hyphen ) && $int_version == $post_hyphen )
			return substr( $string, 0, $hyphen_pos ) . '-' . ++$int_version;

		return $string . '-1';
	}

	/**
	 * Resets the specified user's password, changing it to something quasi-random (and returning
	 * the new password as a string, so it can be relayed to the affected user).
	 *
	 * Useful for student password resets, as students ordinarily will not be required to have
	 * an email address with which to accomplish this via ordinary channels.
	 *
	 * @param $user_id
	 * @return string
	 */
	public static function reset_password( $user_id ) {
		$password = apply_filters( 'wpan_random_password', RandomString::generate() );

		wp_update_user( array(
			'ID' => $user_id,
			'user_pass' => $password
		) );

		return $password;
	}

	/**
	 * Checks if the specified URL and the current URL are the same.
	 *
	 * @param $url
	 * @return bool
	 */
	public static function already_at_url( $url ) {
		if ( false === ( $url = parse_url($url) ) ) return false;

		$url['path'] = trim( $url['path'], '/' );
		$domain = $_SERVER['SERVER_NAME'];
		$path = trim( $_SERVER['REQUEST_URI'], '/' );

		return ( $domain === $url['host'] && $path === $url['path'] );
	}
}