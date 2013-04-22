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


	public function page($view, array $vars = null) {
		$vars = array_merge(array(
			'title' => 'Teachblog',
			'menu' => '',
			'content' => ''
		), (array) $vars);

		$vars['content'] = $view = $this->view($view, $vars, false);
		$this->view('admin_frame', $vars);
	}


	public function view($view, array $vars = null, $render = true) {
		$path = $this->system->dir."system/views/$view.php";
		if (!file_exists($path)) return;

		if (!$render) ob_start();

		extract($vars);
		include $path;

		if (!$render) return ob_get_clean();
	}
}