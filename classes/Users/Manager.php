<?php
namespace WPAN\Users;


class Manager {
	/**
	 * Container for the Users Management object.
	 *
	 * @var Manager
	 */
	protected static $object;


	/**
	 * @return Manager
	 */
	public static function object() {
		if ( isset( self::$object ) ) return self::$object;
		self::$object = new self();
		return self::$object;
	}

	protected function __construct() {
		$this->register_user_roles();
	}


	protected function register_user_roles() {
		Admin::register();
		Student::register();
		Teacher::register();
	}
}