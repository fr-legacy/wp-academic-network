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
 * Provides a means of protecting student content (or sites where student content facilities are in
 * use).
 */
class Teachblog_Content_Privacy extends Teachblog_Base_Object
{
	protected $actions = array(
		'admin_init' => 'save_changes',
		'admin_menu' => 'add_settings_page'
	);


    protected function preflight() {
        $this->register_module();
        if (!$this->system->modules->is_enabled('content_privacy')) return false;
        return true;
    }


    protected function register_module() {
        $this->system->modules->register_module(
            __('Content Privacy', 'teachblog'),
            __('Tools to limit the visibility of student contains (or of sites that contain student content)', 'teachblog'),
            'content_privacy'
        );
    }


	public function save_changes() {
		if (!Teachblog_Form::check_admin_url()) return;
	}


	public function add_settings_page() {
		$this->admin_menu->register_submenu('Privacy Mode', 'privacy_mode', array($this, 'controller'), 'manage_options', 'teachblog_student_content');
	}


	public function controller() {
		$this->admin->page('content_privacy/settings', array(
			'title' => 'Privacy Settings'
		));
	}
}