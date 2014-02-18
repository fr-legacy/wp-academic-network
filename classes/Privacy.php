<?php
namespace WPAN;

use WPAN\Helpers\View,
	WPAN\Helpers\WordPress,
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
	 * Post meta key used to indicate post visibility.
	 */
	const ACCESS_MARKER = 'wpan_visibility';

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

		add_action( 'add_meta_boxes', array( $this, 'teacher_metabox' ) );
		add_action( 'save_post', array( $this, 'public_marker_changes' ) );

		add_action( 'wpan_assess_privacy_needs', array( $this, 'protect_student_blogs' ), 10, 2 );
		add_action( 'wpan_assess_privacy_needs', array( $this, 'protect_teacher_blogs' ), 10, 2 );
		add_action( 'wpan_assess_privacy_needs', array( $this, 'lockdown_unsupervised_student_blogs' ), 10, 2 );
		add_action( 'wpan_assess_privacy_needs', array( $this, 'promote_hub_access' ), 10, 2 );

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
			'marked_open' => $this->is_post_publicly_accessible()
		);

		do_action( 'wpan_assess_privacy_needs', $this->analysis, $this );
	}

	/**
	 * Returns true if the current query is singular and the post is marked open (publicly
	 * accessible).
	 */
	protected function is_post_publicly_accessible() {
		global $post;

		if ( ! is_admin() && ! is_singular() ) return false;
		$marker = get_post_meta( $post->ID, self::ACCESS_MARKER, true );
		return ( 'public' === $marker );
	}

	/**
	 * Returns true if the current query is singular and the post is marked as accessible
	 * to authenticated observers (for further granular control via the privacy filters).
	 */
	protected function is_post_observer_accessible() {
		global $post;

		if ( ! is_admin() && ! is_singular() ) return false;
		$marker = get_post_meta( $post->ID, self::ACCESS_MARKER, true );
		return ( 'observer' === $marker );
	}

	/**
	 * Protected student blogs from access by unauthenticated users.
	 */
	public function protect_student_blogs( array $analysis, Privacy $privacy ) {
		if ( ! $analysis['student_blog'] ) return;
		if ( ! $analysis['authenticated'] ) $privacy->recommend_denial();
		else $privacy->recommend_access();
	}

	/**
	 * Protected teacher blogs from access by unauthenticated users except where a singular
	 * post has been requested and that post has been marked as publicly accessible.
	 */
	public function protect_teacher_blogs( array $analysis, Privacy $privacy ) {
		if ( ! $analysis['teacher_blog'] ) return;
		if ( ! $analysis['authenticated'] && ! $analysis['marked_open'] ) $privacy->recommend_denial();
		else $privacy->recommend_access();
	}

	/**
	 * Protects student blogs, further restricting access if they have no teacher supervisor to
	 * users (ie teachers, network admins) with appropriate higher level roles/capabilities and the
	 * blog owner themselves.
	 *
	 * @param array $analysis
	 * @param Privacy $privacy
	 */
	public function lockdown_unsupervised_student_blogs( array $analysis, Privacy $privacy ) {
		// If it is not a student blog, or is a student blog but has a teacher supervisor, bail out
		if ( ! $analysis['student_blog'] ) return;
		if ( false !== $this->network->get_teacher_for( get_current_blog_id() ) ) return;

		// Recommend denial if the current user is not the owner
		$visitor = get_current_user_id();
		$student = $this->network->get_student_for( get_current_blog_id() );

		// Users with promote_users capability, or roving teachers, allowed by default
		$safety_cap = current_user_can( apply_filters( 'wpan_student_lockdown_approved_user_cap', 'promote_users' ) );
		$is_teacher = $this->users->is_teacher( $visitor );
		$should_allow = apply_filters( 'wpan_student_lockdown_allow_current_user', ( $safety_cap || $is_teacher ), $visitor );

		// Decide!
		if ( $visitor === $student ) $privacy->recommend_access();
		elseif ( $should_allow ) $privacy->recommend_access();
		else $privacy->recommend_denial();
	}

	/**
	 * Recommend access be allowed to the hub site.
	 */
	public function promote_hub_access( array $analysis, Privacy $privacy ) {
		if ( ! $analysis['is_hub'] ) return;
		else $privacy->recommend_access();
	}

	/**
	 * Convenience method to recommend access be denied.
	 */
	public function recommend_denial() {
		$this->recommend( self::RECOMMEND_DENIAL );
	}

	/**
	 * Convenience method to recommend access be allowed.
	 */
	public function recommend_access() {
		$this->recommend( self::RECOMMEND_ACCESS );
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
		$should_allow = self::RECOMMEND_ACCESS === ( $this->recommendations & self::RECOMMEND_ACCESS );
		$should_deny = self::RECOMMEND_DENIAL === ( $this->recommendations & self::RECOMMEND_DENIAL );
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

	/**
	 * Sets up a meta box within the post editor for teacher blogs to allow them to mark certain
	 * posts as publicly accessible.
	 */
	public function teacher_metabox() {
		if ( ! apply_filters( 'wpan_privacy_show_public_marker_meta_box', $this->users->is_teacher() ) ) return;
		$post_type_editors = apply_filters( 'wpan_privacy_public_marker_meta_box_screens', array( 'post', 'page' ) );

		foreach ( $post_type_editors as $target )
			add_meta_box( 'wpan_markpublic_metabox', __( 'Public Access Control', 'wpan' ),
				array( $this, 'do_metabox' ), $target, 'side', 'high' );
	}

	/**
	 * Controller for the public accessiblity meta box.
	 */
	public function do_metabox() {
		echo View::admin( 'public_access_metabox', array(
			'publicly_accessible' => $this->is_post_publicly_accessible(),
			'observer_accessible' => $this->is_post_observer_accessible()
		) );
	}

	/**
	 * Listens for post saves and updates the public accessibility marker if appropriate.
	 */
	public function public_marker_changes() {
		global $post;

		if ( ! isset( $_POST['wpan_confirm_public_accessiblity'] ) ) return;
		if ( ! wp_verify_nonce( $_POST['wpan_confirm_public_accessiblity'], 'WPAN public marker' . get_current_user_id() ) ) return;

		if ( isset( $_POST['wpan_public_item'] ) )
			update_post_meta( $post->ID, self::ACCESS_MARKER, $_POST['wpan_public_item'] );
	}
}