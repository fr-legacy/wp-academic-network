<?php
namespace WPAN;


/**
 * Utility function for working within a WordPress network.
 *
 * Class Network
 * @package WPAN
 */
class Network {
	/**
	 * Used to identify blog meta entries.
	 */
	const META_KEY_PREFIX = 'wpan_blog_';


	/**
	 * Determines if the specified site (or current site, if no site ID is provided) is the hub.
	 *
	 * The hub in a WPAN context is ordinarily the network and 'root' or main sites within the network.
	 * Useful for determining if admin screens etc with network-level significance should be registered.
	 *
	 * @param null $blog_id
	 * @return bool
	 */
	public static function is_hub( $blog_id = null ) {
		return (bool) apply_filters( 'wpan_is_hub', is_main_site( $blog_id ), $blog_id );
	}

	/**
	 * Returns the blog ID for the hub site.
	 *
	 * This works in tandem with Network::is_hub(), therefore if a plugin decides to modify the logic
	 * for determining what the hub is then care should be taken to ensure both methods follow suit.
	 *
	 * @return int blog_id
	 */
	public static function get_hub_id() {
		global $current_site;
		return (int) apply_filters( 'wpan_hub_id', $current_site->blog_id );
	}

	/**
	 * Marks a specific user as owner of the specified site. By extension, the user account
	 * will be assigned to the site.
	 *
	 * @param $user_id
	 * @param null $blog_id
	 * @param $role
	 */
	public static function assign_owner( $user_id, $blog_id, $role ) {
		$blog_id = absint( $blog_id );
		$user_id = absint( $user_id );
		add_user_to_blog( $blog_id, $user_id, $role );
		self::update_blog_meta( 'site_owner', $user_id, $blog_id );
	}

	/**
	 * Removes ownership of the specified blog from any given user.
	 *
	 * @param $blog_id
	 */
	public static function clear_owner( $blog_id ) {
		self::delete_blog_meta( 'site_owner', $blog_id );
	}

	/**
	 * Returns the ID of the user who owns the specified site (or current site, if no site is
	 * specified) or else boolean false if there is no owner.
	 *
	 * It will also return false if the owner's user account does not actually belong to the
	 * same blog, which is a prerequisite for ownership.
	 *
	 * @param null $blog_id
	 * @return mixed int|false
	 */
	public static function who_owns( $blog_id ) {
		$blog_id = absint( $blog_id );
		$user_id = self::get_blog_meta( 'site_owner', false, $blog_id ); // Rtns the ID as a string, or false
		return ( false === $user_id ) ? false : (int) $user_id; // Correct to ensure an actual int is rtn'd
	}

	/**
	 * Provides a list of all sites owned by the specified user.
	 *
	 * @param $user_id
	 * @return array of site IDs
	 */
	public static function owned_by( $user_id ) {
		$belongs_to = get_blogs_of_user( $user_id );
		$owns = array();

		foreach ( $belongs_to as $member_of )
			if ( $user_id == self::who_owns( $member_of->userblog_id ) )
				$owns[] = $member_of->userblog_id;

		return $owns;
	}

	/**
	 * Used to associate pieces of meta data with a specific blog. If the item does not already exist
	 * it is created.
	 *
	 * Blog meta in a WPAN network context is stored at network level rather than within the individual
	 * site options table. This is useful and efficient where the meta data is used to form a link
	 * between different sites as multiple tables need not be traversed.
	 *
	 * @param $key
	 * @param $value
	 * @param null $blog_id
	 * @return bool
	 */
	public static function update_blog_meta( $key, $value, $blog_id = null ) {
		$blog_id = ( null === $blog_id ) ? get_current_site()->id : $blog_id;
		return update_site_option( self::META_KEY_PREFIX . $blog_id . '_' . $key, $value);
	}

	/**
	 * Retrieves a piece of meta data associated with a specific blog, or else the optional
	 * default value is returned (defaults to false).
	 *
	 * As with Network::update_blog_meta() this operates at network level (individual site option
	 * tables are not used).
	 *
	 * @param $key
	 * @param bool $default
	 * @param null $blog_id
	 * @return mixed|void
	 */
	public static function get_blog_meta( $key, $default = false, $blog_id = null ) {
		$blog_id = ( null === $blog_id ) ? get_current_site()->id : $blog_id;
		return get_site_option( self::META_KEY_PREFIX . $blog_id . '_'  . $key, $default );
	}

	/**
	 * Deletes a piece of meta data associated with a specific blog.
	 *
	 * As with Network::update_blog_meta() this operates at network level (individual site option
	 * tables are not used).
	 *
	 * @param $key
	 * @param null $blog_id
	 */
	public static function delete_blog_meta( $key, $blog_id = null ) {
		$blog_id = ( null === $blog_id ) ? get_current_site()->id : $blog_id;
		delete_site_option( self::META_KEY_PREFIX . $blog_id . '_' . $key );
	}
}