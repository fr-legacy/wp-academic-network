<?php
namespace WPAN\Users;


/**
 * Defines extra roles and responsibilities of a Network administrator (as distinct from a
 * super admin).
 *
 * In this context, a network admin might be an admin user assigned to the hub site: they
 * can perform various suporting functions such as approving the creation of new blogs.
 *
 * Class Admin
 * @package WPAN\Users
 */
class Admin extends User {
	/**
	 * Network admins have a number of additional capabilities that allow them to perform
	 * help-desk like functions in terms of approving requests to create new sites and
	 * users.
	 *
	 * @var array
	 */
	protected $caps = array(
		'wpan_access_hub_tools' => true,
		'wpan_create_site' => true,
		'wpan_delete_site' => true,
		'wpan_create_user' => true,
		'wpan_delete_user' => true,
		'wpan_handle_requests' => true
	);

	public function get_role() {
		return 'wpan_administrator';
	}

	public function get_display_name() {
		return __( 'Academic Network Administrator', 'wpan' );
	}

	public function get_capabilities() {
		$admin_caps = User::get_existing_role_capabilities( 'administrator' );
		return array_merge( $admin_caps, $this->caps );
	}
}