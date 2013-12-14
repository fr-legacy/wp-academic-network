<?php
namespace WPAN\Users;


/**
 * Teacher-level user. May be responsible for one or more students.
 *
 * Class Teacher
 * @package WPAN\Users
 */
class Teacher extends User {
	public function get_role() {
		return 'wpan_teacher';
	}

	public function get_display_name() {
		return __( 'Academic Network Teacher', 'wpan' );
	}

	/**
	 * The teacher role is essentially parallel to that of a regular WP administrator,
	 * however having a separate role allows us to encapsulate and modify their capabilities
	 * as needed for a particular situation without impinging on actual admin users.
	 *
	 * @return array
	 */
	public function get_capabilities() {
		return User::get_existing_role_capabilities( 'administrator' );
	}
}