<?php
namespace WPAN;

use WP_Admin_Bar,
	WPAN\Network,
	WPAN\Users;


class Relationships
{
	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var WP_Admin_Bar
	 */
	protected $admin_bar;


	/**
	 * Sets up the Relationships component, used to link andd unlink teacher users and student
	 * blogs.
	 */
	public function __construct() {
		$this->network = Core::object()->network();
		$this->users = Core::object()->users();
		$this->actions();
	}

	/**
	 * Hooks into WordPress where needed.
	 */
	protected function actions() {
		add_action( 'admin_bar_menu', array( $this, 'add_connect_links' ), 50 );
		add_action( 'init', array( $this, 'connection_requests' ) );
	}

	/**
	 * Adds "Connect" links to the admin menu bar as needed for teacher and student users.
	 */
	public function add_connect_links( WP_Admin_Bar $wp_admin_bar ) {
		$this->admin_bar = $wp_admin_bar;

		// Who is visiting the site and what blog are they visiting?
		$user = get_current_user_id();
		$blog = get_current_blog_id();
		$show = false;

		// It only makes sense to expose these links to a teacher on a student blog, or student on a teacher blog
		if ( $this->users->is_student( $user ) && $this->network->is_teacher_blog( $blog ) ) $show = true;
		if ( $this->users->is_teacher( $user ) && $this->network->is_student_blog( $blog ) ) $show = true;
		if ( ! $show ) return;

		$this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships',
			'title' => _x( 'Academic Network', 'admin bar', 'wpan' ),
			'href' => ''
		) );

		if ( $this->users->is_student( $user ) ) $this->add_connect_options_for_student();
		if ( $this->users->is_teacher( $user ) ) $this->add_connect_options_for_teacher();
	}

	/**
	 * Adds connect/disconnect links for students on teacher blogs.
	 */
	protected function add_connect_options_for_student() {
		$teacher_id = $this->network->get_teacher_for( get_current_blog_id() );
		$student_blog = $this->network->get_primary_blog( get_current_user_id() );

		if ( $this->network->is_teacher_supervisor( $student_blog, $teacher_id ) ) {
			$state = 'connected';
			$status = __( 'You have already connected with this teacher:', 'wpan' );
		}
		else {
			$state = 'unconnected';
			$status = __( 'You are not connected to this teacher:', 'wpan' );
		}

		$this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_state',
			'parent' => 'wpan_toolbar_relationships',
			'title' => $status
		) );

		$this->add_connector_menu_items( $state );
	}

	/**
	 * Shows the connect/disconnect link for teachers visting a student blog.
	 */
	protected function add_connect_options_for_teacher() {
		if ( $this->network->is_teacher_supervisor( get_current_blog_id(), get_current_user_id() ) ) {
			$state = 'connected';
			$status = __( 'You are already connected to this blog', 'wpan' );
		}
		else {
			$state = 'unconnected';
			$status = __( 'You are not connected to this blog:', 'wpan' );
		}

		$this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_state',
			'parent' => 'wpan_toolbar_relationships',
			'title' => $status
		) );

		$this->add_connector_menu_items( $state );
	}

	protected function add_connector_menu_items( $state ) {
		$origin = substr( hash( 'md5', uniqid() . get_current_blog_id() ), 5, 17 );

		if ( 'connected' === $state ) $this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_disconnect_me',
			'parent' => 'wpan_toolbar_relationships',
			'title' => __( '&rarr; Please disconnect', 'wpan' ),
			'href' => add_query_arg( array(
				'wpan_relationship_builder' => 'disconnect',
				'origin' => $origin,
				'confirm' => wp_create_nonce( 'WPAN build relationship' . get_current_user_id() . $origin )
			) )
		) );

		if ( 'unconnected' === $state ) $this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_connect_me',
			'parent' => 'wpan_toolbar_relationships',
			'title' => __( '&rarr; Connect me!', 'wpan' ),
			'href' => add_query_arg( array(
				'wpan_relationship_builder' => 'connect',
				'origin' => $origin,
				'confirm' => wp_create_nonce( 'WPAN build relationship' . get_current_user_id() . $origin )
			) )
		) );
	}

	/**
	 * Listens for and processes connection requests.
	 */
	public function connection_requests() {
		if ( ! isset( $_GET['wpan_relationship_builder'] ) ) return;
		if ( ! isset( $_GET['origin'] ) || ! isset( $_GET['confirm'] ) ) return;
		if ( ! wp_verify_nonce( $_GET['confirm'], 'WPAN build relationship' . get_current_user_id() . $_GET['origin'] ) ) return;

		switch ( $_GET['wpan_relationship_builder'] ) {
			case 'connect':
			case 'disconnect':
				$this->make_connection();
			break;
		}
	}

	/**
	 * Tries to form or break the connection.
	 */
	protected function make_connection() {
		$operation = $_GET['wpan_relationship_builder']; // Should be 'connect' or 'disconnect'

		$blog_id = get_current_blog_id();
		$user_id = get_current_user_id();
		$is_student = $this->users->is_student( $user_id );
		$is_teacher = $this->users->is_teacher( $user_id );

		if ( $is_student ) {
			$student_blog = $this->network->get_primary_blog( $user_id );
			$teacher_id = $this->network->get_teacher_for( $blog_id );
		}

		if ( 'connect' === $operation && $is_teacher )
			$this->network->assign_teacher_supervisor( $blog_id, $user_id );

		if ( 'disconnect' === $operation && $is_teacher )
			$this->network->unassign_teacher_supervisor( $blog_id, $user_id );

		if ( 'connect' === $operation && $is_student )
			$this->network->assign_teacher_supervisor( $student_blog, $teacher_id );

		if ( 'disconnect' === $operation && $is_student )
			$this->network->unassign_teacher_supervisor( $student_blog, $teacher_id );

		// If a teacher disconnects in the student blog admin environment they will no longer have
		// privileges and will see the WP "cheating" message - try to catch this and redirect them
		if ( $is_teacher && 'disconnect' === $operation && is_admin() ) {
			wp_safe_redirect( home_url() );
			exit();
		}
	}
}