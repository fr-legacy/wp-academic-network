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
 * Provides basic services to all inheriting objects.
 */
class Teachblog_Base_Object {
	/**
	 * Common domain for l10n/i18n and option namespacing
	 */
	const DOMAIN = 'teachblog';

	/**
	 * @var Teachblog
	 */
	protected $system;

	/**
	 * @var Teachblog_Admin_Menu
	 */
	protected $admin_menu;

	/**
	 * @var Teachblog_Admin_Environment
	 */
	protected $admin;

	/**
	 * @var Teachblog_Shortcode_Location
	 */
	protected $shortcode;


	public function __construct() {
		// Key system object references/aliases
		$this->system = Teachblog::core();
		$this->admin = $this->system->admin_environment;
		$this->admin_menu = $this->system->admin_menu;
		$this->shortcode = $this->system->shortcode_location;

		// Allow components the opportunity to determine if they should run or not
		if (method_exists($this, 'preflight') and !$this->preflight()) return;

		$this->setup_hooks('add_action', 'actions');
		$this->setup_hooks('add_filter', 'filters');
		if (method_exists($this, 'setup')) $this->setup();
	}


	protected function setup_hooks($register, $map) {
		if (property_exists($this, $map) and is_array($this->$map))
			foreach ($this->$map as $hook => $callback)
				$register($hook, array($this, $callback));
	}


	/**
	 * Gets or sets a blog specific setting.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function local_setting($key, $value = null) {
		$key = 'teachblog' . "_$key";
		$t = get_option($key);
		if (is_null($value)) return get_option($key);
		else return update_option($key, $value);
	}


	/**
	 * Gets or sets a site/network wide setting.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function global_setting($key, $value = null) {
		$key = 'teachblog' . "_$key";

		if (is_null($value)) return get_site_option($key);
		else return update_site_option($key, $value);
	}
}