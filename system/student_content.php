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
 * Sets up and manages student posts.
 */
class Teachblog_Student_Content extends Teachblog_Base_Object {
	const TEACHBLOG_POST = 'teachblog_post';
	const TEACHBLOG_TAXONOMY = 'teachblog_tax';
	
	
	protected function setup() {
		$this->register_type();
	}
	
	
	/**
	 * Registers the Teachblog custom post type and taxonomy (only when student content has
	 * been enabled for the current blog). 
	 */
	protected function register_type() {
		if (!$this->local_setting('student_content_enabled')) return;
		
		register_post_type(self::TEACHBLOG_POST, array(
			'label' => __('Student Posts', self::DOMAIN),
			'labels' => array(
				'singular_name' => __('Student Post', self::DOMAIN),
				'all_items' => __('All Student Posts', self::DOMAIN),
				'add_new' => _x('Add New', self::TEACHBLOG_POST, self::DOMAIN) ),
			'description' => __('Container for student submitted content', self::DOMAIN),
			'public' => true,
				
		));		
	}
}