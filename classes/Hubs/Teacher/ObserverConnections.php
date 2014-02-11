<?php
namespace WPAN\Hubs\Teacher;

use WP_User,
	WPAN\Core,
	WPAN\Helpers\AdminTable,
	WPAN\Helpers\Log,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Relationships,
	WPAN\Users;


class ObserverConnections
{
	/**
	 * @var AdminTable
	 */
	protected $table;

	/**
	 * @var Relationships
	 */
	protected $relationships;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * Records any errors, notices etc that need to be relayed to the user.
	 *
	 * @var array
	 */
	protected $notices = array();


	/**
	 * Sets up the Students object.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
		$this->listeners();
	}

	/**
	 * Sets up filters as required.
	 */
	protected function listeners() {
	}

	/**
	 * Returns the Teacher admin view.
	 *
	 * @return string
	 */
	public function get_page() {
		return $this->menu() . $this->page();
	}

	/**
	 * Returns the markup for the subtab menu.
	 *
	 * @return string
	 */
	protected function menu() {
		$base_url = get_admin_url( get_current_blog_id(), 'admin.php?page=wpan_teacher_hub&tab=observers' );
		$subtabs = array(
			'current' => __( 'Existing connections', 'wpan' ),
			'requested' => __( 'Requests', 'wpan' )
		);
		return WordPress::sub_menu( $subtabs, $base_url );
	}

	/**
	 * Returns the page itself.
	 *
	 * @return string
	 */
	protected function page() {

	}
}