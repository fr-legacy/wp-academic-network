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
 * Provides high level settings that essentially allow key capabilities to be turned
 * on or off. These settings integrate with the existing WordPress settings menu.
 */
class Teachblog_General_Settings extends Teachblog_Base_Object {
	const SETTINGS_SLUG = 'teachblog_general_settings';

	protected $actions = array(
		'admin_menu' => 'add_settings_page'
	);


	public function add_settings_page() {
		$title = _x('Educational Tools', 'menu-item', self::DOMAIN);
		add_options_page($title, $title, 'manage_options', self::SETTINGS_SLUG, array($this, 'options_page'));
	}


	public function options_page() {
		$this->admin->page('general_settings', array(
			'title' => __('Educational Tool Settings', self::DOMAIN)
		));
	}
}