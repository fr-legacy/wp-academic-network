<?php
namespace WPAN\Helpers;


class Form {
	/**
	 * Returns an HTML select (dropdown) element.
	 *
	 * @param $name
	 * @param $id
	 * @param $options
	 * @param null $selected
	 * @return string
	 */
	public static function select( $name, $id, $options, $selected = null ) {
		$output = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';

		foreach ( $options as $value => $label ) {
			$select = ( $selected === $value ) ? ' selected="selected"' : '';
			$output .= '<option value="' . esc_attr( $value ) . '"' . $select . '> ' . $label . ' </option>';
		}

		return $output . '</select>';
	}
}