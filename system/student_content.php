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
	
	protected $actions = array(
		'admin_menu' => 'menu_structure'
	);
	
	
	protected function setup() {
		#if (!$this->local_setting('student_content_enabled')) return;
		
		$this->register_type();
		$this->register_taxonomy();
		$this->type_taxonomy_link();
	}	
	
	
	/**
	 * Registers the Teachblog custom post type (only when student content has been enabled for the 
	 * current blog). 
	 */
	protected function register_type() {		
		$supports = array('title', 'editor', 'description', 'excerpt', 'custom-fields', 'comments');
		
		// Optional supported functionality
		if ($this->local_setting('post_revisions')) $supports[] = 'revisions';
		if (current_theme_supports('post_thumbnails')) $supports[] = 'thumbnails';
		
		register_post_type(self::TEACHBLOG_POST, array(
			'label' => __('Student Posts', self::DOMAIN),
			'labels' => array(
				'singular_name' => __('Student Post', self::DOMAIN),
				'all_items' => __('All Student Posts', self::DOMAIN),
				'add_new' => _x('Add New', self::TEACHBLOG_POST, self::DOMAIN) ),
			'description' => __('Container for student submitted content', self::DOMAIN),
			'public' => true,
			'supports' => $supports,
			'register_meta_box_cb' => array($this, 'editor_metaboxes'),
			'taxonomy' => self::TEACHBLOG_TAXONOMY,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => _x('studentpost', 'student_post_slug', self::DOMAIN) )					
		));		
	}
	
	
	
	/**
	 * Registers the Teachblog custom taxonomy (only when student content has been enabled for the
	 * current blog) which is intended as a tool to effectively organize posts into individual
	 * "sub-blogs".
	 */
	protected function register_taxonomy() {
		register_taxonomy(self::TEACHBLOG_TAXONOMY, self::TEACHBLOG_POST, array(
			'label' => __('Student Blogs', self::DOMAIN),
			'labels' => array(
				'singular_name' => _x('Student Blog', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'all_items' => _x('All Student Blogs', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'edit_item' => _x('Edit Blog', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'view_item' => _x('View Blog', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'update_item' => _x('Update Blog', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'add_new_item' => _x('Add New Student Blog', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'new_item_name' => _x('New Student Blogs', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'parent_item' => _x('Parent Blog/Group', self::TEACHBLOG_TAXONOMY, self::DOMAIN),
				'search_items' => _x('Search Blogs', self::TEACHBLOG_TAXONOMY, self::DOMAIN) ),
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite' => array(
				'slug' => _x('studentblog', 'student_blog_slug', self::DOMAIN) )	
		));
	}
	
	
	/**
	 * Helps to avoid edge cases where the link between taxonomy and post type is not observed. 
	 */
	protected function type_taxonomy_link() {
		register_taxonomy_for_object_type(self::TEACHBLOG_TAXONOMY, self::TEACHBLOG_POST);
	}
	
	
	/**
	 * Triggers the addition of metaboxes to the student post editor.
	 */
	public function editor_metaboxes() {
		do_action('teachblog_editor_metaboxes');
	}
	
	
	/**
	 * Alters the menu structure created during post registration and allows additional items to be added.
	 * 
	 * @todo refactor: move to menu class
	 */
	public function menu_structure() {
		add_menu_page(
			_x('Student Content Management', 'menu-entry', self::DOMAIN),
			_x('Student Content', 'menu-entry', self::DOMAIN),
			'edit_posts',
			'teachblog_student_content',
			array($this, 'menu_page'),
			'',
			$this->safe_menu_position());			
	}
	
	
	/**
	 * @todo add already-occupied checks
	 */
	protected function safe_menu_position() {
		return '54.67';
	}
}