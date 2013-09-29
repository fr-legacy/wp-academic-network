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
 * Helpers for working with the WordPress admin menu.
 */
class Teachblog_Admin_Menu extends Teachblog_Base_Object {
	const SUBMENU_SLUG_INDEX = 2;

	/**
	 * The menu start position uses a decimal value to avoid collisions,
	 * this is a workaround suggested in the WP codex (when used with
	 * menu API calls it must be cast to string, however).
	 */
	protected $menu_position = 50.256;

	/**
	 * Registered menu's "hook_suffix".
	 *
	 * @see http://codex.wordpress.org/Function_Reference/add_menu_page
	 * @var string
	 */
	protected $menu_hook = '';

	/**
	 * Slug for the last (normally, the only) registered top level menu
	 * created by this plugin.
	 *
	 * @var string
	 */
	protected $menu_slug = '';

	/**
	 * Used for re-ordering submenu admin items.
	 */
	protected $parent_file_map = array();


	protected function setup() {
		$this->menu_position = apply_filters('teachblog_menu_start_pos', $this->menu_position);
	}


	/**
	 * Registers a top level administrative menu. Intended to be used once only.
	 */
	public function register_menu($title, $slug, $controller, $capability = '', $icon = '') {
		// For l10n purposes the title translation needs to be assessed upstream of this method
		$title = _x($title, 'menu-entry', 'teachblog');
		$icon = empty($icon) ? $this->system->url . 'assets/menu-icon-set.png' : $icon;

		$this->menu_hook = add_menu_page(
			$title, $title, $capability, $slug, $controller, $icon, $this->safe_menu_position());

		$this->menu_slug = $slug;
	}


	/**
	 * Registers a submenu page.
	 */
	public function register_submenu($title, $slug, $controller, $capability, $parent = '') {
		// For l10n purposes the title translation needs to be assessed upstream of this method
		$title = _x($title, 'menu-entry', 'teachblog');

		if (empty($parent)) $parent = $this->menu_slug;
		add_submenu_page($parent, $title, $title, $capability, $slug, $controller);
	}


	/**
	 * Registers the taxonomy admin page as a submenu item.
	 */
	public function register_taxonomy_subpage($title, $parent, $taxonomy, $capability) {
		global $submenu;
		if (!isset($submenu[$parent])) return;

		$title = _x($title, 'menu-entry', 'teachblog'); // For i18n the string can be obtained upstream
		$slug = "edit-tags.php?taxonomy=$taxonomy";
		$submenu[$parent][] = array($title, $capability, $slug, $title);

		// Map to the new parent item
		$this->parent_file_map[$taxonomy] = $parent;
		add_filter('parent_file', array($this, 'parent_file_remap'));

		return $slug;
	}


	/**
	 * Used to return a new parent file where items have been re-ordered within the menu.
	 *
	 * @param $parent_file
	 */
	public function parent_file_remap($parent_file) {
		global $current_screen;

		if (!isset($current_screen->taxonomy)) return $parent_file;
		if (!isset($this->parent_file_map[$current_screen->taxonomy])) return $parent_file;

		return $this->parent_file_map[$current_screen->taxonomy];
	}


	/**
	 * Prioritizes the submenu item so it is the first item in the menu.
	 */
	public function send_to_top($menu_slug, $subitem_slug) {
		global $submenu;
		$entry = array();

		if (!isset($submenu[$menu_slug])) return; // No such menu

		// Find the subitem to be prioritized
		foreach ($submenu[$menu_slug] as $key => $subitem)
			if ($subitem[self::SUBMENU_SLUG_INDEX] === $subitem_slug) {
				$entry = $subitem; // Copy subitem
				unset($submenu[$menu_slug][$key]); // Remove from original position
				array_unshift($submenu[$menu_slug], $entry); // Reinstate at top of the array
			}

		if (!isset($key)) return; // Subitem wasn't found

		// Rebuild the entry
		$entry[] = $subitem;
	}


	/**
	 * Returns a menu position as-yet free for use.
	 *
	 * It's possible another plugin/theme will override it even so, however the secondary
	 * measure of adding a decimal value and casting to string should help mitigate this
	 * in most circumstances.
	 *
	 * @see http://codex.wordpress.org/Function_Reference/add_menu_page
	 */
	public function safe_menu_position() {
		// Find a vacant slot to add the menu item
		while ($this->menu_position_is_occupied()) $this->menu_position++;

		// Cast to string to support our secondary anti-collision workaround
		return (string)$this->menu_position++;
	}


	/**
	 * Returns true if the current menu_position value is taken.
	 *
	 * Comparisons are made using menu_position as a string and as an integer,
	 * further reducing opportunities for Teachblog menus to become a broken group
	 * (if more than one are registered in the same request).
	 *
	 * @return bool
	 */
	protected function menu_position_is_occupied() {
		global $menu;
		return (isset($menu[(string)$this->menu_position]) or isset($menu[(int)$this->menu_position]));
	}
}