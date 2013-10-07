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
 * Implements a privacy model where unauthenticated users are directed to a
 * specified "gateway" page and may not leave it until they have authenticated.
 * Does not interfere with wp-admin requests.
 */
class Teachblog_Gateway_Privacy extends Teachblog_Base_Object
{
	protected function setup() {
		if (is_admin() || defined('DOING_AJAX')) return;
		if (!is_user_logged_in()) add_action('wp', array($this, 'try_to_redirect'));
	}


	public function try_to_redirect() {
		$redirect_to = $this->get_redirect_url();
		if (null === $redirect_to) return;
		if (!$redirect_to || headers_sent())
			wp_die(__('You do not have permission to view this page, please login first!', 'teachblog'));
		exit(wp_safe_redirect($redirect_to));
	}


	protected function get_redirect_url() {
		$setting = $this->local_setting('content_privacy');
		if (!isset($setting['gateway_id'])) return false;
		if (!isset($GLOBALS['post'])) return false;

		if ($setting['gateway_id'] == $GLOBALS['post']->ID) return null;
		return get_permalink(absint($setting['gateway_id']));
	}
}
