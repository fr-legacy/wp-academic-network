<?php
namespace WPAN;

use WP_Query;


class Requests {
	/**
	 * Post type handle for requests.
	 */
	const REQUEST_OBJECT = 'wpan_request';

	/**
	 * Meta key used to record the user a request originates from.
	 */
	const FROM_KEY = 'wpan_request_from';

	/**
	 * Meta key used to record the user a request is intended for.
	 */
	const TO_KEY = 'wpan_request_to';

	/**
	 * Flags if the setup processes have completed.
	 *
	 * @var bool
	 */
	protected static $setup_complete = false;

	/**
	 * @var Network
	 */
	protected $network;


	/**
	 * Sets up the request system.
	 */
	public function __construct() {
		$this->setup();
		$this->network = Core::object()->network();
	}

	/**
	 * Carries out required setup tasks the first time a Requests object is instantiated.
	 */
	protected function setup() {
		if ( self::$setup_complete ) return;
		add_action( 'init', array( $this, 'register_type' ) );
		self::$setup_complete = true;
	}

	/**
	 * Register the requests (non-public) post type.
	 */
	public function register_type() {
		register_post_type( self::REQUEST_OBJECT, array( 'public' => false ) );
	}

	/**
	 * Makes a request from one user to another.
	 *
	 * @param int $from user ID
	 * @param int $to user ID
	 * @param string $type
	 * @param string $details
	 */
	public function make( $from, $to, $type, $details ) {
		switch_to_blog( $this->network->get_hub_id() );

		$id = wp_insert_post( array(
			'post_content' => json_encode( $details ),
			'post_title' => $type,
			'post_type' => self::REQUEST_OBJECT,
			'post_mime_type' => 'text/json'
		) );

		if ( $id > 0 ) {
			update_post_meta( $id, 'wpan_request_from', $from );
			update_post_meta( $id, 'wpan_request_to', $to );
		}

		restore_current_blog();
	}

	/**
	 * Returns a list of requests as an array (which may be empty) sent *to* the specified user.
	 * If only interested in a specific type of request, then an optional $type param can be
	 * supplied.
	 *
	 * @param $for
	 * @param $type
	 * @return array
	 */
	public function find_for( $for, $type = null ) {
		return $this->find( $for, self::TO_KEY, $type );
	}

	/**
	 * Returns a list of requests as an array (which may be empty) sent *by* the specified user.
	 * If only interested in a specific type of request, then an optional $type param can be
	 * supplied.
	 *
	 * @param $from
	 * @param $type
	 * @return array
	 */
	public function find_from( $from, $type = null ) {
		return $this->find( $from, self::FROM_KEY, $type );
	}

	/**
	 * Returns an array of posts (or an empty array) representing requests to or from the
	 * specified user (setting key to FROM_KEY or TO_KEY controls the direction). Request type
	 * is optional and can be left at null if all types should be returned.
	 *
	 * @param $user
	 * @param $key
	 * @param $type
	 * @return array
	 */
	protected function find( $user, $key, $type = null ) {
		switch_to_blog( $this->network->get_hub_id() );

		$args = array(
			'post_type' => self::REQUEST_OBJECT,
			'meta_key' => $key,
			'meta_value' => $user
		);

		if ( null !== $type ) $args['post_title'] = $type;
		$query = new WP_Query( $args );

		// Clean up *then* return
		restore_current_blog();
		return (array) $query->posts;
	}
}