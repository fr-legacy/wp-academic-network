<?php
namespace WPAN;


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
	public static function subsubsub_tab_menu( array $tabs, $current ) {
		$output = '<ul class="subsubsub">';
		$items = 0;

		// What is the current tab?
		$tab_keys = array_keys( $tabs );
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tab_keys ) ) $current = $_GET['tab'];
		else $current = array_shift( $tab_keys );

		foreach ( $tabs as $slug => $label ) {
			if ( 1 < ++$items ) $output .= ' | ';
			$class = ( $current === $slug ) ? ' class="current" ' : '';
			$output .= '<li> <a href=""' . $class . '>' . $label . '</a> </li>';
		}

		return $output . '</ul>';
	}
}