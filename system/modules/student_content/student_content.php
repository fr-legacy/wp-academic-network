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
		'admin_menu' => 'menu_structure',
		'init' => array(
			'blog_setup',
			'setup_content_support',
			'setup_supporting_tools',
			'setup_complete'
		),
		'widgets_init' => 'register_widgets'
	);

	/**
	 * @var Teachblog_Front_Editor
	 */
	public $front_editor;

	/**
	 * @var Teachblog_Front_Individual_List
	 */
	public $front_list;

	/**
	 * @var Teachblog_Front_Submissions
	 */
	public $front_submissions;

    /**
     * @var Teachblog_Blog_Requests
     */
    public $blog_requests;


	protected function preflight() {
		if (!$this->system->modules->is_enabled('student_content')) return false;
		return true;
	}


	/**
	 * Integrates blogs and blog posts, hooks up event handlers, sets up UI tweaks etc.
	 */
	public function blog_setup() {
		add_action('edited_' . self::TEACHBLOG_BLOG_TAXONOMY, array($this, 'save_assigned_student'), 20, 1);
		add_action('created_' . self::TEACHBLOG_BLOG_TAXONOMY, array($this, 'save_assigned_student'), 20, 1);
		add_filter('manage_edit-' . self::TEACHBLOG_BLOG_TAXONOMY . '_columns', array($this, 'blog_list_columns'));
		add_filter('manage_' . self::TEACHBLOG_BLOG_TAXONOMY . '_custom_column', array($this, 'populate_assignee_columns'), 10, 3);
		add_filter('manage_edit-' . self::TEACHBLOG_POST . '_columns', array($this, 'post_list_columns'));
		add_filter('manage_' . self::TEACHBLOG_POST . '_custom_column', array($this, 'populate_post_columns'), 10, 2);
		add_action(self::TEACHBLOG_BLOG_TAXONOMY . '_add_form_fields', array($this, 'new_ownership_selector'));
		add_action(self::TEACHBLOG_BLOG_TAXONOMY . '_edit_form_fields', array($this, 'existing_ownership_selector'));
	}


	public function setup_content_support() {
		$this->register_type();
		$this->register_taxonomy();
		$this->type_taxonomy_link();
	}


	public function setup_supporting_tools() {
		$this->front_editor = new Teachblog_Front_Editor;
		$this->front_list = new Teachblog_Front_Individual_List;
		$this->front_submissions = new Teachblog_Front_Submissions;
        $this->blog_requests = new Teachblog_Blog_Requests;
	}


	public function setup_complete() {
		do_action('teachblog_student_content_init');
	}


	public function register_widgets() {
		register_widget('Teachblog_Widget_My_Posts');
		register_widget('Teachblog_Widget_Blogger_Posts');
		register_widget('Teachblog_Widget_Recent_Posts');
		register_widget('Teachblog_Widget_Student_Blogs');
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
			'label' => __('Student Posts', 'teachblog'),
			'labels' => array(
				'singular_name' => __('Student Post', 'teachblog'),
				'all_items' => __('All Student Posts', 'teachblog'),
				'add_new' => _x('Add New', self::TEACHBLOG_POST, 'teachblog')),
			'description' => __('Container for student submitted content', 'teachblog'),
			'public' => true,
			'show_in_menu' => self::TEACHBLOG_MENU_SLUG,
			'menu_icon' => $this->system->url . 'assets/tango-web-icon-32.png',
			'supports' => $supports,
			'register_meta_box_cb' => array($this, 'editor_metaboxes'),
			'taxonomy' => self::TEACHBLOG_BLOG_TAXONOMY,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => _x('studentpost', 'student_post_slug', 'teachblog'))
		));
	}


	/**
	 * Registers the Teachblog custom taxonomy (only when student content has been enabled for the
	 * current blog) which is intended as a tool to effectively organize posts into individual
	 * "sub-blogs".
	 */
	protected function register_taxonomy() {
		register_taxonomy(self::TEACHBLOG_BLOG_TAXONOMY, self::TEACHBLOG_POST, array(
			'label' => __('Student Blogs', 'teachblog'),
			'labels' => array(
				'singular_name' => _x('Student Blog', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'all_items' => _x('All Student Blogs', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'edit_item' => _x('Edit Blog', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'view_item' => _x('View Blog', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'update_item' => _x('Update Blog', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'add_new_item' => _x('Add New Student Blog', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'new_item_name' => _x('New Student Blogs', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'parent_item' => _x('Parent Blog/Group', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog'),
				'search_items' => _x('Search Blogs', self::TEACHBLOG_BLOG_TAXONOMY, 'teachblog')),
			'hierarchical' => true,
			'public' => true,
			'rewrite' => array(
				'slug' => _x('studentblog', 'student_blog_slug', 'teachblog'),),
			'show_admin_column' => true // Effective in WP 3.5+
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
			'edit_posts'
		);

		$slug = $this->admin_menu->register_taxonomy_subpage(
			'Student Blogs',
			self::TEACHBLOG_MENU_SLUG,
			self::TEACHBLOG_BLOG_TAXONOMY,
			'manage_categories'
		);

		$this->admin_menu->send_to_top(self::TEACHBLOG_MENU_SLUG, $slug);
	}


	/**
	 * Updates the association between the student blog and the student user(s). The method draws on the $_POST super
	 * global and inspects the student_blog_owner entry, however optional param $user_ids can be set and be used
	 * instead.
	 *
	 * This method supports the concept of multiple users being assigned to a single blog even if not implemented by the
	 * basic additions to the UI.
	 *
	 * It is not a requirement that assigned users be student_users however, they can belong to any role.
	 *
	 * @param $term_id
	 * @param array $user_ids
	 */
	public function save_assigned_student($term_id, array $user_ids = null) {
		// A supplied $user_ids list takes precedence over an entry in $_POST
		if (!is_array($user_ids) and isset($_POST['student_blog_owner'])) {
			$this->edit_tag_checks();
			$user_ids = (array)$_POST['student_blog_owner'];
		}

		// If we have neither/it's an empty list then bug out
		if (!is_array($user_ids) or empty($user_ids)) return;

		// Start by *unassigning* all
		Teachblog_Blogger::unassign_all($term_id);

		// Now assign all users as requested
		foreach ($user_ids as $user) {
			$user = Teachblog_Blogger::load((int)$user);
			if (!$user->loaded) continue; // Non-existent/invalid user?
			else $user->assign_to_blog($term_id);
		}
	}


	/**
	 * Emulates the security and sanity checks normally performed in edit-tags.php/ajax-actions.php. Failure means
	 * execution will be halted.
	 */
	protected function edit_tag_checks() {
		// New blog (taxonomy) being created?
		if (isset($_REQUEST['action']) and $_REQUEST['action'] === 'add-tag') {
			if (defined('DOING_AJAX') and DOING_AJAX) check_ajax_referer('add-tag', '_wpnonce_add-tag'); // via ajax
			else check_admin_referer('add-tag', '_wpnonce_add-tag'); // without ajax
			if (!current_user_can(get_taxonomy(self::TEACHBLOG_BLOG_TAXONOMY)->cap->edit_terms)) wp_die(-1);
		} // Or else existing blog (taxonomy) being edited?
		else {
			$tag_id = (int)$_POST['tag_ID'];
			check_admin_referer('update-tag_' . $tag_id);

			if (!current_user_can(get_taxonomy(self::TEACHBLOG_BLOG_TAXONOMY)->cap->edit_terms))
				wp_die(__('You do not have permission to carry out this action.', 'teachblog'));

			$tag = get_term($tag_id, self::TEACHBLOG_BLOG_TAXONOMY);
			if (!$tag) wp_die(__('The blog you are trying to edit is no longer available.', 'teachblog'));
		}
	}


	/**
	 * Adds UI elements to allow blogs to be assigned to student users (when a new blog is created).
	 */
	public function new_ownership_selector() {
		$this->admin->view('student_selector', array(
			'label' => __('Blog Owner', 'teachblog'),
			'description' => __('Any posts the assigned student makes will automatically be linked to this blog.', 'teachblog'),
			'students' => $this->system->student_user->list_users()
		));
	}


	/**
	 * Adds UI elements to allow blogs to be assigned to student users (when an existing blog is being edited).
	 */
	public function existing_ownership_selector() {
		$tag_id = isset($_REQUEST['tag_ID']) ? (int)$_REQUEST['tag_ID'] : 0;

		$this->admin->view('student_selector_table', array(
			'label' => __('Blog Owner', 'teachblog'),
			'description' => __('Any posts the assigned student makes will automatically be linked to this blog.', 'teachblog'),
			'students' => $this->system->student_user->list_users(),
			'selected' => Teachblog_Blogger::get_assigned_users($tag_id)
		));
	}


	/**
	 * Adds and removes columns from the list of blogs. By default the assignee is added while the slug and description
	 * columns are removed for a cleaner view.
	 *
	 * @param $columns
	 * @return mixed
	 */
	public function blog_list_columns($columns) {
		if (apply_filters('teachblog_bloglist_remove_slug', true) and isset($columns['slug']))
			unset($columns['slug']);

		if (apply_filters('teachblog_bloglist_remove_description', true) and isset($columns['description']))
			unset($columns['description']);

		$columns['teachblog_assignees'] = __('Assignee(s)', 'teachblog');
		return $columns;
	}


	/**
	 * Populates each entry of the assignee column with the assignee names etc (if a user is assigned).
	 *
	 * @param $html
	 * @param $column_name
	 * @param $tag_id
	 */
	public function populate_assignee_columns($html, $column_name, $tag_id) {
		if ($column_name !== 'teachblog_assignees') return $html;

		$users = (array)Teachblog_Blogger::get_assigned_users($tag_id);

		if (empty($users)) $html = __('No one is assigned to this blog', 'teachblog');
		else foreach ($users as $user) {
			$user = get_user_by('id', $user);
			$html .= '<a href="' . get_edit_user_link($user->ID) . '">' . esc_attr($user->user_nicename) . '</a> <br />';
		}

		return $html;
	}


	/**
	 * Adds and removes columns from the list of posts. The taxonomy column in particular (auto generated only on
	 * WP 3.5+) will be relabelled.
	 *
	 * @param $columns
	 * @return mixed
	 */
	public function post_list_columns($columns) {
		// Relabel the taxonomy (student blog) column
		if (isset($columns['taxonomy-'.self::TEACHBLOG_BLOG_TAXONOMY]))
			$columns['taxonomy-'.self::TEACHBLOG_BLOG_TAXONOMY] = __('Blog', 'teachblog');

		$columns['teachblog_last_edit'] = __('Last Edit By', 'teachblog');
		return $columns;
	}


	/**
	 * Populates the "last edited by" column.
	 *
	 * @todo implementation - stub only
	 * @param $html
	 * @param $column_name
	 * @param $tag_id
	 */
	public function populate_post_columns($html, $column_name) {
		if ($column_name !== 'teachblog_last_edit' or $column_name !== 'teachblog_status') return $html;

		$html = 'TODO!';
		return $html;
	}


    /**
     * Creates a new blog with the provided title and optionally a description, and assigns to the specified user.
     *
     * @param $title
     * @param string $description = '' (optional)
     * @return mixed
     */
    public function create_blog($title, $description = '') {
        $result = wp_insert_term($title, self::TEACHBLOG_BLOG_TAXONOMY, array(
            'decription' => $description
        ));

        if (is_wp_error($result)) return false;
        return $result['term_id'];
    }
}