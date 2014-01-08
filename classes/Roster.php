<?php
namespace WPAN;


class Roster
{
	/**
	 * One of the user roles as defined in the Users class.
	 *
	 * @var string
	 */
	protected $type;


	/**
	 * Sets up the Roster object to work with the specified user role.
	 *
	 * @param $user_type
	 */
	public function __construct( $user_type ) {
		$this->type = $user_type;
	}


}