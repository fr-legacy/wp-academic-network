<?php
namespace WPAN;

use WP_Query,
	WPAN\Helpers\Log;


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
	 * Meta key used to record the request type.
	 */
	const TYPE_KEY = 'wpan_request_type';

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
	public function open( $from, $to, $type, $details ) {
		switch_to_blog( $this->network->get_hub_id() );

		$id = wp_insert_post( array(
			'post_content' => json_encode( $details ),
			'post_type' => self::REQUEST_OBJECT,
			'post_mime_type' => 'text/json'
		) );

		if ( $id > 0 ) {
			update_post_meta( $id, self::FROM_KEY, $from );
			update_post_meta( $id, self::TO_KEY, $to );
			update_post_meta( $id, self::TYPE_KEY, $type );

			do_action( 'wpan_' . $type . '_request_opened', $from, $to, $type, $details );
			Log::action( sprintf( __( 'Request %d created (from user %d to user %d).', 'wpan' ), $id, $from, $to ) );
		}
		else {
			Log::warning( sprintf( __( 'Failed to create request of type %s (from user %d to user %d).', 'wpan' ), $type, $from, $to ) );
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
	 * Any details in the returned requests, which would have been stored in JSON format, will
	 * be converted back to a native PHP object.
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
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => $key,
					'value' => $user
				) ),
			'post_status' => 'any' // This is a moot point for us, but WP assumes published/private
		);

		// Are we interested in a specific type of request?
		if ( null !== $type ) $args['meta_query'][] = array(
			'key' => self::TYPE_KEY,
			'value' => $type
		);

		$query = new WP_Query( $args );

		// Clean up *then* return
		restore_current_blog();
		return $this->requestify( (array) $query->posts );
	}

	/**
	 * Loads and returns the specified request. If a post object is returned it will have "from" and
	 * "to" properties dynamically attached to indicate the relevant user IDs.
	 *
	 * @param $request_id
	 * @return \WP_Post | false
	 */
	public function load( $request_id ) {
		switch_to_blog( $this->network->get_hub_id() );
		$post = get_post( $request_id );
		restore_current_blog();

		if ( null === $post ) return false;
		$this->requestify( $post ); // Operates on object ref

		return $post;
	}

	/**
	 * Decodes the post_content from JSON to a native PHP object. Accepts one or more
	 * posts objects.
	 *
	 * @param $request_posts
	 * @return array
	 */
	protected function requestify( $request_posts ) {
		switch_to_blog( $this->network->get_hub_id() );
		$requests = is_array( $request_posts ) ? $request_posts : array( $request_posts );

		foreach ( $requests as $request_object ) {
			if ( 'text/json' === $request_object->post_mime_type )
				$request_object->post_content = json_decode( $request_object->post_content );

			$request_object->data = $request_object->post_content;
			$request_object->type = get_post_meta( $request_object->ID, self::TYPE_KEY, true );
			$request_object->from = (int) get_post_meta( $request_object->ID, self::FROM_KEY, true );
			$request_object->to = (int) get_post_meta( $request_object->ID, self::TO_KEY, true );
		}

		restore_current_blog();
		return $request_posts;
	}

	/**
	 * Completes a request and deletes it.
	 *
	 * @param $id
	 */
	public function close( $id ) {
		switch_to_blog( $this->network->get_hub_id() );
		$request = get_post( $id );

		if ( null === $request ) {
			Log::warning( sprintf( __( 'Attempted to close non-existent request %d.', 'wpan' ), $id ) );
			restore_current_blog();
			return;
		}

		wp_delete_post( $id );

		$type = get_post_meta( $request->ID, self::FROM_KEY, true );;
		do_action( 'wpan_' . $type . '_request_closed', $id, $type );
		Log::action( sprintf( __( 'Closed request %d.', 'wpan' ), $id ) );

		restore_current_blog();
	}
}