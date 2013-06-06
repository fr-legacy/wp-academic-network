<?php
/* 
	Plugin Name: Teachblog
	Description: Provides blogging and other tools to students within the framework of an existing site on a WP network.
	Author: Barry Hughes (forthrobot.com)
	Version: 0.1.0
	Author URI: http://forthrobot.com
	License: GPL3

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
 * Teachblog umbrella class. Sets up the plugin and loads key components.
 */
class Teachblog {
	const VERSION = '0.1.0';
	const CODENAME = 'Busy Biretta';

	protected static $instance = false;

	/**
	 * Plugin directory URL
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Plugin directory
	 *
	 * @var string
	 */
	public $dir = '';

	/**
	 * After init contains an object with details of the current site; the following
	 * properties can be expected:
	 *
	 *    id, domain, path, site_name
	 *
	 * @var mixed
	 */
	public $current_site = false;

	/**
	 * @var Teachblog_Admin_Environment
	 */
	public $admin_environment;

	/**
	 * @var Teachblog_Admin_Menu
	 */
	public $admin_menu;

	/**
	 * @var Teachblog_General_Settings
	 */
	public $general_settings;

	/**
	 * @var Teachblog_Student_Content
	 */
	public $student_content;

	/**
	 * @var Teachblog_Student_User
	 */
	public $student_user;

	/**
	 * @var Teachblog_Modules
	 */
	public $modules;

	/**
	 * @var Teachblog_Shortcode_Location
	 */
	public $shortcode_location;


	/**
	 * @var array
	 */
	protected $classmap = array(
		'Teachblog_Admin_Environment' => 'admin_environment',
		'Teachblog_Admin_Menu' => 'admin_menu',
		'Teachblog_Admin_Table' => 'helpers/admin_table',
		'Teachblog_Base_Object' => 'base',
		'Teachblog_Blogger' => 'modules/student_content/blogger',
        'Teachblog_Blog_Request_Docket' => 'modules/student_content/blog_request_docket',
        'Teachblog_Blog_Request_Form' => 'modules/student_content/blog_request_form',
        'Teachblog_Blog_Request_Submissions' => 'modules/student_content/blog_request_submissions',
        'Teachblog_Blog_Requests' => 'modules/student_content/blog_requests',
		'Teachblog_Form' => 'helpers/form',
		'Teachblog_Front_Editor' => 'modules/student_content/front_editor',
		'Teachblog_Front_Individual_List' => 'modules/student_content/front_individual_list',
		'Teachblog_Front_Submissions' => 'modules/student_content/front_submissions',
		'Teachblog_General_Settings' => 'general_settings',
		'Teachblog_Modules' => 'modules',
		'Teachblog_Shortcode_Location' => 'modules/core/shortcode_location',
		'Teachblog_Strings' => 'helpers/strings',
		'Teachblog_Student_Content' => 'modules/student_content/student_content',
		'Teachblog_Student_User' => 'modules/core/student_user',
		'Teachblog_Template' => 'helpers/template'
	);


	/**
	 * Returns the Teachblog core instance/launches it if it does not already exist.
	 *
	 * @return Teachblog
	 */
	public static function core() {
		if (self::$instance === false)
			self::$instance = new self;

		return self::$instance;
	}


	protected function __construct() {
		$this->locate_self();
		$this->setup();
		add_action('init', array($this, 'launch'));
	}


	protected function locate_self() {
		$this->dir = trailingslashit(plugin_dir_path(__FILE__));
		$this->url = trailingslashit(plugin_dir_url(__FILE__));
	}


	protected function setup() {
		spl_autoload_register(array($this, 'class_loader'));
	}


	public function class_loader($class) {
		if (!isset($this->classmap[$class])) return;
		$classpath = $this->dir . 'system/' . $this->classmap[$class] . '.php';

		if (file_exists($classpath)) {
			require $classpath;
			return true;
		}

		return false;
	}


	public function launch() {
		$this->current_site = get_current_site();
		$this->modules = new Teachblog_Modules;
		$this->admin_environment = new Teachblog_Admin_Environment;
		$this->general_settings = new Teachblog_General_Settings;
		$this->admin_menu = new Teachblog_Admin_Menu;
		$this->shortcode_location = new Teachblog_Shortcode_Location;
        $this->student_content = new Teachblog_Student_Content;
		$this->student_user = new Teachblog_Student_User;

        do_action('teachblog_launch');
	}
}


// Launch Teachblog
Teachblog::core();