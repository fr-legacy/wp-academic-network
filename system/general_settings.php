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
		'admin_init' => 'save_changes',
		'admin_menu' => 'add_settings_page'
	);


	public function add_settings_page() {
		$title = _x('Educational Tools', 'menu-item', self::DOMAIN);
		add_options_page($title, $title, 'manage_options', self::SETTINGS_SLUG, array($this, 'options_page'));
	}


	public function options_page() {
		// Prepare the table used to layout the available modules
		$module_table = new Teachblog_Admin_Table;
		$module_table->set_actions(array(
			'Activate' => 'do-activation',
			'Deactivate' => 'do-deactivation'
		));

		$this->admin->page('general_settings', array(
			'title' => __('Educational Tools &ndash; Configuration', self::DOMAIN),
			'modules' => $this->system->modules,
			'module_table' => $module_table
		));
	}


	public function save_changes() {
		if (!Teachblog_Form::check_admin_url()) return;

		// Look for individual module activation/deactivations
		foreach ($this->system->modules->get_module_slugs() as $slug) {
			if (isset($_POST[$slug])) $this->system->modules->enable($slug);
			else $this->system->modules->disable($slug);
		}

		// Look for bulk actions
		if (Teachblog_Form::is_posted('actions-top', 'do-activation'))
			$this->activate_selected_modules();

		if (Teachblog_Form::is_posted('actions-top', 'do-deactivation'))
			$this->deactivate_selected_modules();

		// Reload
		wp_redirect(Teachblog_Form::admin_url());
	}


	public function activate_selected_modules() {
		foreach ($_POST['check_row'] as $module)
			$this->system->modules->enable($module);
	}


	public function deactivate_selected_modules() {
		foreach ($_POST['check_row'] as $module)
			$this->system->modules->disable($module);
	}
}