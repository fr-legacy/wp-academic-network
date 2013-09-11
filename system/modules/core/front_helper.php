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
 * Library of helpers for displaying and formatting blog data publicly.
 */
class Teachblog_Front_Helper
{
	public static function readable_post_state($post) {
		$status = isset($post->post_status) ? $post->post_status : 'unknown';
		$state = 'Unknown';

		switch ($status) {
			case 'draft': $state = _x('Draft', 'post-states', 'teachblog'); break;
			case 'future': $state = _x('Scheduled', 'post-states', 'teachblog'); break;
			case 'pending': $state = _x('Pending Approval', 'post-states', 'teachblog'); break;
			case 'private': $state = _x('Private!', 'post-states', 'teachblog'); break;
			case 'publish': $state = _x('Published', 'post-states', 'teachblog'); break;
		}

		return esc_attr($state);
	}
}