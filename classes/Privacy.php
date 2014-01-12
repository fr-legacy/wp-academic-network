<?php
namespace WPAN;

use WPAN\Helpers\WordPress,
	WPAN\Network,
	WPAN\Users;


class Privacy
{
	/**
	 * Constants to be used by privacy filters to indicate if they wish to have no influence on
	 * the decision, or if they recommend access not be allowed, or if they recommend access should
	 * be allowed.
	 */
	const NO_RECOMMENDATION = 0;
	const RECOMMEND_DENIAL = 1;
	const RECOMMEND_ACCESS = 2;

	/**
	 * Post meta key used to indicate if a post should be publicly accessible.
	 */
	const PUBLIC_ACCESS_MARKER = 'wpan_publicly_accessible';

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * Contains a set of boolean flags useful for making decisions about allowing or denying
	 * requests.
	 *
	 * @var array
	 */
	protected $analysis = array();

	/**
	 * Used to record recommendations.
	 *
	 * @var int
	 */
	protected $recommendations = 0;


	/**
	 * Sets up and enforces blog privacy rules.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
		$this->actions();
	}

	/**
	 * Sets up actions.
	 */
	protected function actions() {
		add_action( 'wp', array( $this, 'assess' ), 5 );
		add_action( 'wp', array( $this, 'determine' ), 10 );

		add_action( 'wpan_assess_privacy_needs', array( $this, 'protect_student_blogs' ) );
		add_action( 'wpan_assess_privacy_needs', array( $this, 'protect_teacher_blogs' ) );
		add_action( 'wpan_disallow_request', array( $this, 'disallow' ) );
	}

	/**
	 * Looks at the current request and tries to determine if further access should be permitted
	 * or disallowed.
	 */
	public function assess() {
		$this->analysis = array(
			'authenticated' => is_user_logged_in(),
			'student_blog' => $this->network->is_student_blog( get_current_blog_id() ),
			'teacher_blog' => $this->network->is_teacher_blog( get_current_blog_id() ),
			'is_hub' => $this->network->is_hub(),
			'headers_sent' => headers_sent(),
			'is_singular' => is_singular(),
			'marked_open' => $this->is_post_marked_open()
		);

		do_action( 'wpan_assess_privacy_needs', $this->analysis, $this );
	}

	/**
	 * Returns true if the current query is singular and the post is marked open (publicly
	 * accessible).
	 */
	protected function is_post_marked_open() {
		global $post;

		if ( ! is_singular() ) return false;
		$marker = get_post_meta( $post->ID, self::PUBLIC_ACCESS_MARKER, true );
		return ( 'public' === $marker );
	}

	/**
	 * Protected student blogs from access by unauthenticated users.
	 */
	public function protect_student_blogs() {

	}

	/**
	 * Protected teacher blogs from access by unauthenticated users except where a singular
	 * post has been requested and that post has been marked as publicly accessible.
	 */
	public function protect_teacher_blogs() {

	}

	/**
	 * Security callbacks can use this method to record their recommendation as to whether the
	 * current user should be allowed access per the incoming request. $recommended should be
	 * set to one of the following class constants:
	 *
	 *     NO_RECOMMENDATION
	 *     RECOMMEND_ACCESS
	 *     RECOMMEND_DENIAL
	 *
	 * @param $recommendation
	 */
	public function recommend( $recommendation ) {
		$this->recommendations = $this->recommendations | $recommendation;
	}

	/**
	 * Inspects the recommendations property.
	 *
	 * When operating in strict mode (which is by default) if any recommendations for denial have been made, or if no
	 * recommendations of access were made, then the disallow action will be fired - this errs on the side of caution
	 * and prefers disallowing access to granting access.
	 *
	 * If strict mode is off (can be achieved by filtering wpan_privacy_strict_mode) then so long as no recommendations
	 * for denial were made no further action will be taken. In other words, we allow access unless a recommendation
	 * for denying access was specifically made.
	 */
	public function determine() {
		$should_allow = self::RECOMMEND_ACCESS === ( $this->recommendations | self::RECOMMEND_ACCESS );
		$should_deny = self::RECOMMEND_DENIAL === ( $this->recommendations | self::RECOMMEND_DENIAL );
		$strict_mode = apply_filters( 'wpan_privacy_strict_mode', true );

		// If we are in strict mode
		if ( $strict_mode && ( $should_deny || ! $should_allow ) ) do_action( 'wpan_disallow_request' );
		if ( ! $strict_mode && $should_deny ) do_action( 'wpan_disallow_request' );
	}

	/**
	 * Intended to run if the wpan_disallow_request event is fired: this allows other plugins an opportunity
	 * to take some other action prior to this running, or to shortc circuit it and implement some other
	 * course of action instead.
	 */
	public function disallow() {
		$hub_url = $this->network->get_hub_url();
		$redirect_to = apply_filters( 'wpan_no_access_redirect_url', $hub_url );
		header( 'Location: ' . $redirect_to );
		exit();
	}
}