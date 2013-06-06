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
		'admin_init' => 'admin_resources'
	);


	protected function setup() {
		if (!is_admin()) return;
		if (!class_exists('WP_List_Table'))
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
	}


	public function admin_resources() {
		wp_enqueue_style('teachblog_admin_theme', $this->system->url . 'assets/admin.css');
		wp_enqueue_script('teachblog_admin_script', $this->system->url . 'assets/admin.js', array('jquery'));
		wp_localize_script('teachblog_admin_script', 'teachblog', array(
			'on' => __('On', 'teachblog'),
			'off' => __('Off', 'teachblog')
		));
	}


	public function page($view, array $vars = null) {
		$vars = array_merge(array(
			'title' => 'Teachblog',
			'menu' => '',
			'content' => ''
		), (array)$vars);

		$vars['content'] = $view = $this->view($view, $vars, false);
		$this->view('admin_frame', $vars);
	}


	public function view($view, array $vars = null, $render = true) {
		$path = $this->system->dir . "system/views/$view.php";
		if (!file_exists($path)) return;

		if (!$render) ob_start();

		if (null !== $vars) extract($vars);
		include $path;

		if (!$render) return ob_get_clean();
	}
}