<?php
namespace WPAN;


class Core {
	/**
	 * Container for the Core WPAN object.
	 *
	 * @var Core
	 */
	protected static $object;


	public static function object() {
		if ( isset( self::$object ) ) return self::$object;
		self::$object = new self();
		return self::$object;
	}


	/**
	 * Loads rest of the plugin, sets up key services.
	 */
	protected function __construct() {
		if ( Network::is_hub() ) Hub\Manager::object();
		Users\Manager::object();
		Requests\Manager::object();
		$result = Network::owned_by( 1 );
	}
}