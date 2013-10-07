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
		'admin_menu' => 'add_settings_page',
		'admin_print_scripts-student-content_page_privacy_mode' => 'add_script'
	);


    protected function preflight() {
        $this->register_module();
        if (!$this->system->modules->is_enabled('content_privacy')) return false;
        return true;
    }


	protected function setup() {
		$setting = $this->local_setting('content_privacy');
		if (!isset($setting['mode'])) return;

		switch ($setting['mode']) {
			case 'gateway': new Teachblog_Gateway_Privacy; break;
			case 'selective': new Teachblog_Selective_Privacy; break;
		}
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
		if (!wp_verify_nonce($_POST['teachblog_privacy_settings'], 'privacy')) return;

		switch (Teachblog_Form::is_posted('mode') ? $_POST['mode'] : 'disabled') {
			case 'gateway': $mode = 'gateway'; break;
			case 'selective': $mode = 'selective'; break;
			default: $mode = 'disabled'; break;
		}

		if (Teachblog_Form::is_posted('gateway_page')) {
			$gateway_id = absint($_POST['gateway_page']);
		}

		$setting = $this->local_setting('content_privacy');
		if (!is_array($setting)) $setting = array();

		$setting['mode'] = $mode;
		if (isset($gateway_id)) $setting['gateway_id'] = $gateway_id;

		$this->local_setting('content_privacy', $setting);
	}


	public function add_settings_page() {
		$this->admin_menu->register_submenu('Privacy Mode', 'privacy_mode', array($this, 'controller'), 'manage_options', 'teachblog_student_content');
	}


	public function controller() {
		$setting = wp_parse_args((array) $this->local_setting('content_privacy'), array(
			'mode' => 'disabled',
			'gateway_id' => 0
		));

		$this->admin->page('content_privacy/settings', array(
			'title' => 'Privacy Settings',
			'mode' => $setting['mode'],
			'gateway_id' => $setting['gateway_id']
		));
	}


	public function add_script() {
		wp_enqueue_script('teachblog_privacy_admin', $this->system->url . '/assets/admin-privacy.js');
	}
}