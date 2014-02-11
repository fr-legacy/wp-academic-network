<?php
namespace WPAN\Hubs;

use WPAN\Core,
	WPAN\Hubs\Network\Manager as NetworkHub,
	WPAN\Hubs\Teacher\Manager as TeacherHub,
	WPAN\Network,
	WPAN\Users;


class Loader {
	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * Container for the hub manager object.
	 */
	public $hub_object;


	/**
	 * Sets up any hub-level facilities, admin screens and functionality that is required.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();

		if ( $this->is_network_hub() ) $this->hub_object = new NetworkHub();
		if ( $this->is_teacher_hub() ) $this->hub_object = new TeacherHub();
	}

	/**
	 * Checks if the current admin environment is the main site and the current user has
	 * at least the wpan_access_hub_tools capability.
	 *
	 * @return bool
	 */
	protected function is_network_hub() {
		if ( ! is_admin() ) return false;
		if ( ! $this->network->is_hub() ) return false;
		if ( ! wp_get_current_user()->has_cap( 'wpan_access_hub_tools' ) ) return false;
		return true;
	}

	/**
	 * Checks if the current admin environment is a teacher blog and the current user is
	 * both teacher and this is indeed their primary blog.
	 *
	 * @return bool
	 */
	protected function is_teacher_hub() {
		$blog_id = get_current_blog_id();
		$user_id = wp_get_current_user()->ID;

		if ( ! is_admin() ) return false;
		if ( ! $this->network->is_teacher_blog( $blog_id ) ) return false;
		if ( ! $this->users->is_teacher( $user_id ) ) return false;
		if ( $this->network->get_primary_blog( $user_id ) !== $blog_id ) return false;

		return true;
	}
}