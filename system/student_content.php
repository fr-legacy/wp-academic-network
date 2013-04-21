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
	const TEACHBLOG_BLOG_TAXONOMY = 'teachblog_blogs';
    const TEACHBLOG_MENU_SLUG = 'teachblog_student_content';
    const TEACHBLOG_BLOGS_SLUG = 'teachblog_student_blogs';

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
            'show_in_menu' => self::TEACHBLOG_MENU_SLUG,
			'supports' => $supports,
			'register_meta_box_cb' => array($this, 'editor_metaboxes'),
			'taxonomy' => self::TEACHBLOG_BLOG_TAXONOMY,
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
		register_taxonomy(self::TEACHBLOG_BLOG_TAXONOMY, self::TEACHBLOG_POST, array(
			'label' => __('Student Blogs', self::DOMAIN),
			'labels' => array(
				'singular_name' => _x('Student Blog', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'all_items' => _x('All Student Blogs', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'edit_item' => _x('Edit Blog', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'view_item' => _x('View Blog', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'update_item' => _x('Update Blog', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'add_new_item' => _x('Add New Student Blog', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'new_item_name' => _x('New Student Blogs', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'parent_item' => _x('Parent Blog/Group', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN),
				'search_items' => _x('Search Blogs', self::TEACHBLOG_BLOG_TAXONOMY, self::DOMAIN) ),
			'hierarchical' => true,
            'public' => true,
			'rewrite' => array(
				'slug' => _x('studentblog', 'student_blog_slug', self::DOMAIN) )	
		));
	}
	
	
	/**
	 * Helps to avoid edge cases where the link between taxonomy and post type is not observed. 
	 */
	protected function type_taxonomy_link() {
		register_taxonomy_for_object_type(self::TEACHBLOG_BLOG_TAXONOMY, self::TEACHBLOG_POST);
	}
	
	
	/**
	 * Triggers the addition of metaboxes to the student post editor.
	 */
	public function editor_metaboxes() {
		do_action('teachblog_editor_metaboxes');
	}


    /**
     * Generates the basic menu structure
     */
    public function menu_structure() {
        $this->admin_menu->register_menu(
            'Student Content',
            self::TEACHBLOG_MENU_SLUG,
            array($this, 'dashboard_page'),
            'edit_posts' );

        /*$this->admin_menu->register_submenu(
            'Student Blogs',
            self::TEACHBLOG_MENU_SLUG,
            self::TEACHBLOG_BLOGS_SLUG,
            'manage_categories',
            array($this, 'blogs_page')
        );*/

        $this->admin_menu->register_taxonomy_subpage(
            'Student Blogs',
            self::TEACHBLOG_MENU_SLUG,
            self::TEACHBLOG_BLOG_TAXONOMY,
            'manage_categories'
        );

        // $this->admin_menu->send_to_top(self::TEACHBLOG_MENU_SLUG, self::TEACHBLOG_BLOGS_SLUG);
    }
}