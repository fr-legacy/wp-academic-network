<?php
/*
	Plugin Name: WP Academic Network
	Description: Provides a framework and set of utilities to help teachers and students interact, blog and learn together.
	Network: true
	Author: Forthrobot Software
	Version: 0.8.0dev1
	Author URI: http://forthrobot.com
	License: GPL3

	WPAN (WP Academic Network) - provides a teacher led collaborative blogging environment
	Copyright (C) 2013 Forthrobot Software

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


define( 'WPAN_VERSION', '0.8.0dev1' );
define( 'WPAN_DIR', __DIR__ );
define( 'WPAN_URL', plugin_dir_url( __FILE__ ) );


class WPAN_Loader
{
	/**
	 * Notes any problems found during preflight checks.
	 *
	 * @var array
	 */
	protected static $preflight_errors = array();

	/**
	 * Flag used to confirm checks complete.
	 *
	 * @var bool
	 */
	protected static $checks_complete = false;

	/**
	 * Namespaces registered with the autoloader.
	 *
	 * @var array
	 */
	protected static $namespaces = array();

	/**
	 * Specific classes registered with the autoloader.
	 *
	 * @var array
	 */
	protected static $classes = array();

	/**
	 * Checks that we have a suitable environment and bootstraps WPAN.
	 */
	public static function start() {
		add_action( 'plugins_loaded', array( __CLASS__, 'internationalize' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'preflight_checks' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'bootstrap' ) );
	}

	/**
	 * Register our "wpan" text domain.
	 */
	public static function internationalize() {
		load_plugin_textdomain( 'wpan', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * We require PHP 5.3+ and WP 3.7+ as minimums.
	 */
	public static function preflight_checks() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, '5.3', '<' ) )
			self::$preflight_errors[] = __( 'PHP 5.3 or greater is required.', 'wpan' );

		if ( ! isset( $wp_version ) || version_compare( $wp_version, '3.7', '<' ) )
			self::$preflight_errors[] = __( 'WordPress 3.7 or greater is required.', 'wpan' );

		self::$checks_complete = true;
	}

	/**
	 * If all preflight checks passed let's kick things off. Otherwise,
	 */
	public static function bootstrap() {
		// Can't be called without our preflight checks running first
		if ( ! self::$checks_complete ) return;

		// Deal with any preflight failures
		if ( ! empty( self::$preflight_errors ) ) {
			add_action( 'network_admin_notices', array( __CLASS__, 'display_admin_warnings' ) );
			add_action( 'admin_init', array( __CLASS__, 'deactivate_self' ) );
			return;
		}

		self::register_namespace( 'WPAN', WPAN_DIR . '/classes' );
		spl_autoload_register( array( __CLASS__, 'class_loader' ) );

		// Instantiate the core class safely (shouldn't trigger parser errors in a PHP 5.2 env)
		//call_user_func( array( 'WPAN\\Core', 'object' ) );
		WPAN\Core::object()->setup();
	}

	/**
	 * Within the admin environment, shows a warning to alert the users that the plugin cannot
	 * run due to preflight check failures.
	 */
	public static function display_admin_warnings() {
		echo '<div class="updated"> <p>';
		_e( 'WP Academic Network cannot function as expected and will be deactivated until the following issues are resolved.', 'wpan' );
		echo ' <em>' . esc_html( join( ' ', self::$preflight_errors ) );
		echo '</em> </p> </div>';
	}

	/**
	 * Can be used to automatically deactivate the plugin in times where it is unlikely to run
	 * nicely within the host environment/WP installation etc.
	 */
	public static function deactivate_self() {
		if ( ! function_exists( 'deactivate_plugins' ) ) return;
		deactivate_plugins( plugin_basename( __FILE__ ), false, true );
	}

	/**
	 * Registers a root directory for a specific namespace for class autoloading purposes.
	 *
	 * @param $namespace
	 * @param $root_dir
	 */
	public static function register_namespace( $namespace, $root_dir ) {
		self::$namespaces[$namespace] = $root_dir;
	}

	/**
	 * Classes that don't live within a namespace can still be registered for autoloading.
	 *
	 * @param $class
	 * @param $file_path
	 */
	public static function register_class( $class, $file_path ) {
		self::$classes[$class] = $file_path;
	}

	/**
	 * Attempts to load any classes that exist within registered namespaces and/or any associated
	 * vendor or supporting library classes.
	 *
	 * Currently only accepts root namespaces (ie, we don't support having "vendor" in one dir
	 * and "vendor\lib_a", "vendor\lib_b" in completely unrelated directories).
	 *
	 * Attempts to load any classes that exist within registered (top level) namespaces and/or
	 * any associated vendor or supporting library classes.
	 *
	 * @param $class
	 */
	public static function class_loader( $class ) {
		if ( isset( self::$classes[$class] ) && file_exists( self::$classes[$class] ) ) {
			require self::$classes[$class];
			return;
		}

		$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
		$class_path = explode( DIRECTORY_SEPARATOR, $class );

		if ( count( $class_path ) <= 1 ) return;
		if ( ! isset( self::$namespaces[$class_path[0]] ) ) return;

		$class_path[0] = self::$namespaces[$class_path[0]];
		$class_path = implode( '/', $class_path ) . '.php';

		if ( file_exists( $class_path ) ) require $class_path;
	}
}


WPAN_Loader::start();