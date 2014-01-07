<?php
namespace WPAN;

use WPAN\Hub\Manager as HubManager;


class Core
{
	/**
	 * Container for the Core WPAN object.
	 *
	 * @var Core
	 */
	protected static $object;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var HubManager
	 */
	protected $hub;

	/**
	 * Indicates if initial setup is complete. Internal flag.
	 *
	 * @var bool
	 */
	protected $setup = false;


	public static function object() {
		if ( isset( self::$object ) ) return self::$object;
		self::$object = new self();
		return self::$object;
	}


	/**
	 * Marked protected to enforce a singleton pattern.
	 */
	protected function __construct() {}

	/**
	 * Sets up and keeps references to other key components.
	 */
	public function setup() {
		if ( $this->setup ) return;
		else $this->setup = true;

		$this->users = new Users;
		$this->network = new Network;
		$this->hub = new HubManager;
	}

	/**
	 * @return Users
	 */
	public function users() {
		return $this->users;
	}

	/**
	 * @return Network
	 */
	public function network() {
		return $this->network;
	}
}