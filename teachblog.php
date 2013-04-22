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
	 * 	id, domain, path, site_name
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
	 * @var Teachblog_Student_Content
	 */
	public $student_content;
	
	/**
	 * @var array
	 */
	protected $classmap = array(
		'Teachblog_Admin_Environment' => 'admin_environment',
		'Teachblog_Admin_Menu' => 'admin_menu',
		'Teachblog_Base_Object' => 'base',
		'Teachblog_Student_Content' => 'student_content'
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
		$classpath = $this->dir.'system/'.$this->classmap[$class].'.php';
		
		if (file_exists($classpath)) {
			require $classpath;
			return true;
		}
		
		return false;
	}
	
	
	public function launch() {
		$this->current_site = get_current_site();
        $this->admin_environment = new Teachblog_Admin_Environment;
        $this->admin_menu = new Teachblog_Admin_Menu;
		$this->student_content = new Teachblog_Student_Content;
	}
}


// Launch Teachblog
Teachblog::core();