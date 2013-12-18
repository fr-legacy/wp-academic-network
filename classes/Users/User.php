<?php
namespace WPAN\Users;


/**
 * Base template for User classes.
 *
 * Class User
 * @package WPAN\Users
 */
abstract class User {
	/**
	 * Registers the user role.
	 *
	 * WordPress itself takes care of checking if the role is already in existence, so it needn't incur
	 * any particular overhead even if this is called on each request.
	 *
	 * When optional param $force is set to true the user role is rebuilt completely: this can be useful
	 * if capabilities have been added to/removed from the default roles that WPAN roles inherit from
	 * and we want to bring them uptodate.
	 *
	 * @param bool $force
	 * @return bool
	 */
	public static function register( $force = false ) {
		$implementing_class = get_called_class();
		$user_type = new $implementing_class();

		// Remove to force a rebuild of the role?
		if ( $force ) remove_role( $user_type->get_role() );

		add_role( $user_type->get_role(), $user_type->get_display_name(), $user_type->get_capabilities() );
		return true;
	}

	/**
	 * Returns the capabilities of an existing user role. Useful to merge one set into a new role.
	 *
	 * @param $existing_role
	 * @return array
	 */
	public static function get_existing_role_capabilities( $existing_role ) {
		$role = get_role( $existing_role );
		if ( ! is_a( $role, 'WP_Role' ) ) return array();
		return (array) $role->capabilities;
	}

	/**
	 * Returns the internam role name.
	 *
	 * @return string
	 */
	public abstract function get_role();

	/**
	 * Returns an array of user capabilities.
	 *
	 * @return array
	 */
	public abstract function get_capabilities();

	/**
	 * Returns a "human friendly" name for this user role.
	 *
	 * @return string
	 */
	public abstract function get_display_name();
}