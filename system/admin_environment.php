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
 * Sets up supporting assets, effects and provides the helpers and glue needed to bond together
 * the admin experience.
 */
class Teachblog_Admin_Environment extends Teachblog_Base_Object {
	protected $actions = array(
		'admin_init' => 'admin_styles'
	);


	protected function setup() {
		if (!is_admin()) return;
	}


	public function admin_styles() {
		wp_enqueue_style(self::DOMAIN.'_admin_theme', $this->system->url.'assets/admin.css');
	}
}