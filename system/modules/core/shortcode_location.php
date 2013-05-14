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
 * Keeps a register of where [teachblog_*] shortcodes are being used across the blog. One registry entry exists per
 * shortcode, therefore this is only useful where a shortcode is intended to exist in a single page/post - otherwise
 * problems can arise.
 */
class Teachblog_Shortcode_Location extends Teachblog_Base_Object {
	protected function setup() {
		add_action('save_post', array($this, 'check_for_shortcodes'), 20, 2);
	}


	/**
	 * Scans a saved post for shortcodes. The assumption is that for registry purposes we do not want to record
	 * shortcodes used in student post (otherwise the post list and front editor pages could in a sense be 'hijacked'
	 * by student users).
	 *
	 * @param $post_id
	 * @param $post
	 */
	public function check_for_shortcodes($post_id, $post) {
		// "Anti-hijack" measure: do not record [teachblog_*] shortcodes when embedded in student posts
		if ($post->post_type === Teachblog_Student_Content::TEACHBLOG_POST) return;

		// Use the post content as the "post"
		$post = $post->post_content;

		$shortcode_begins = strpos($post, '[teachblog_');
		if ($shortcode_begins === false) return;

		$shortcode_ends = strpos($post, ']', $shortcode_begins);
		$shortcode = substr($post, ++$shortcode_begins, $shortcode_ends - $shortcode_begins);

		// Allow shortcode controllers to disallow their shortcode from being indexed
		if (apply_filters('teachblog_allow_shortcode_indexing', true, $shortcode))
			$this->update_register($shortcode, $post_id);
	}


	protected function update_register($shortcode, $post_id) {
		$register = (array) $this->local_setting('shortcode_register');
		$register[$shortcode] = $post_id;
		$this->local_setting('shortcode_register', $register);
	}


	/**
	 * Returns the post ID of the post containing the specified shortcode (if it has been indexed) or else boolean false
	 * if not known.
	 *
	 * @param $shortcode
	 * @return int|bool
	 */
	public function get_location($shortcode) {
		$register = (array) $this->local_setting('shortcode_register');
		return isset($register[$shortcode]) ? (int) $register[$shortcode] : false;
	}
}