<?php
/*
	Teachblog - provides a teacher led collaborative blogging environment
	Copyright (C) 2013 Barry Hughes

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http: *www.gnu.org/licenses/>.
*/

/**
 * HTML UI elements.
 */
class Teachblog_Form {
	/**
	 * Returns an on/off switch; essentially a checkbox which can be transformed into something closer to
	 * a representation of a physical on/off switch.
	 *
	 * @param $name
	 * @param $value
	 * @param bool $checked
	 * @return string
	 */
	public static function on_off_switch($name, $value, $checked = false) {
		$name = esc_attr($name);
		$value = esc_attr($value);
		$checked = $checked ? 'checked="checked"' : '';

		return '<div class="onoffswitch"> <input type="checkbox" name="' . $name . '" value="' . $value . '" ' . $checked . ' /> </div>';
	}


	/**
	 * Useful for forms embedded in pages/posts using a shortcode etc, this returns the current post
	 * permalink if available (or else an empty string).
	 *
	 * All URL queries for the current request will be appended to the returned string - unless an array
	 * of URL query params is passed in (in which case, only those will be appended). To ensure no
	 * URL query params are appended an empty array can be passed.
	 *
	 * @param array $get_params
	 * @return string
	 */
	public static function post_url(array $get_params = null) {
		// Basic URL
		global $post;
		$url = (is_object($post) and isset($post->ID)) ? get_permalink($post->ID) : '';
		$query = '';

		// Use all existing query vars?
		if ($get_params === null and !empty($_GET)) $query = http_build_query($_GET);

		// Specified params only?
		if (is_array($get_params)) {
			$get_params = array_flip($get_params);
			$query = http_build_query(array_intersect_key($_GET, $get_params));
		}

		if (!empty($query)) $url .= "?$query";
		return $url;
	}


	/**
	 * Returns a nonced action URL for use in admin screens (the nonce name for checks is "teachblog_admin".
	 *
	 * @return string
	 */
	public static function admin_url() {
		global $pagenow;
		$query = array();

		if (isset($_GET['page'])) $query['page'] = $_GET['page'];
		if (isset($_GET['subpage'])) $query['subpage'] = $_GET['subpage'];
		if (isset($_GET['page_num'])) $query['page_num'] = $_GET['page_num'];
		if (isset($_GET['show'])) $query['show'] = $_GET['show'];

		$action = $pagenow . '?' . _http_build_query($query);
		return wp_nonce_url(get_admin_url(null, $action), 'teachblog_admin');
	}


	/**
	 * Checks if an admin page was submitted and passed the basic nonce check.
	 *
	 * @return bool
	 */
	public static function check_admin_url() {
		return (isset($_REQUEST['_wpnonce']) and wp_verify_nonce($_REQUEST['_wpnonce'], 'teachblog_admin'));
	}


	/**
	 * Checks if the specified field is present and (optionally) that it is also set to the specified value.
	 *
	 * @param $field
	 * @return bool
	 */
	public static function is_posted($field, $value = null) {
		if (!isset($_POST[$field])) return false;
		if ($value === null) return true; // No inspection of the value
		return ($_POST[$field] == $value);
	}
}