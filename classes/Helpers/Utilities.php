<?php
namespace WPAN\Helpers;


class Utilities
{
	/**
	 * Returns if the value is deemed to be true.
	 *
	 * Includes some default (but overrideable) keywords which are deemed to
	 * indicate truthiness, ie "yes", "y" and "true".
	 *
	 * @param  mixed   $value
	 * @return boolean
	 */
	public static function is_true( $value ) {
		$value = trim( strtolower( $value ) );
		$truth = apply_filters( 'wpan_truthy_keywords', array(
			'yes', 'y', 'true', 'on', '1', 'enable'
		) );

		if ( is_string( $value ) && in_array( $value, $truth ) )
			return true;

		if ( is_array( $value ) && ! empty( $value ) && current( $value ) )
			return true;

		return (bool) $value;
	}

	/**
	 * Given a string containing comma separated values, returns the individual
	 * items in an array.
	 *
	 * @param  string $csv_string
	 * @return array
	 */
	public static function array_from_csv_string( $csv_string ) {
		$items = array();

		foreach ( explode( ',', $csv_string ) as $item ) {
			$item = trim( $item );
			if ( ! empty( $item ) ) $items[] = $item;
		}

		return $items;
	}
}