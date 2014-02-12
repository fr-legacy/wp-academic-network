<?php
namespace WPAN;

use WP_Admin_Bar,
	WPAN\Helpers\Log,
	WPAN\Network,
	WPAN\Users;


class Relationships
{
	/**
	 * Identifier for student/teacher link requests.
	 */
	const STUDENT_TEACHER_LINK = 'student_teacher_link';

	/**
	 * Identifier for student/teacher unlink requests.
	 */
	const STUDENT_TEACHER_UNLINK = 'student_teacher_unlink';

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var Requests
	 */
	protected $requests;

	/**
	 * @var WP_Admin_Bar
	 */
	protected $admin_bar;


	/**
	 * Sets up the Relationships component, used to link andd unlink teacher users and student
	 * blogs.
	 */
	public function __construct() {
		add_action( 'wpan_loaded', array( $this, 'setup' ) );
	}

	public function setup() {
		$this->network = Core::object()->network();
		$this->requests = Core::object()->requests();
		$this->users = Core::object()->users();
		$this->actions();
	}

	/**
	 * Hooks into WordPress where needed.
	 */
	protected function actions() {
		add_action( 'wpan_toolbar_ready', array( $this, 'toolbar_integration' ) );
		add_action( 'init', array( $this, 'connection_requests' ) );
	}

	/**
	 * Generates a request to link or unlink the student and teacher blogs.
	 *
	 * @param $student_blog
	 * @param $teacher_id
	 * @param $type
	 */
	public function student_teacher_request( $student_blog, $teacher_id, $type ) {
		$student_id = $this->network->get_student_for( $student_blog );
		$this->requests->open( $student_id, $teacher_id, $type, array(
			'student_blog' => $student_blog
		) );
	}

	/**
	 * Closes any currently open request to link/unlink student and teacher blogs.
	 *
	 * @param $student_blog
	 * @param $teacher_id
	 */
	public function close_student_teacher_requests( $student_blog, $teacher_id ) {
		$student_id = $this->network->get_student_for( $student_blog );

		foreach ( $this->list_requests( $teacher_id, self::STUDENT_TEACHER_LINK ) as $request )
			if ( $request->from === $student_id ) $this->requests->close( $request->ID );

		foreach ( $this->list_requests( $teacher_id, self::STUDENT_TEACHER_UNLINK ) as $request )
			if ( $request->from === $student_id ) $this->requests->close( $request->ID );
	}

	/**
	 * Lists currently open student/teacher link requests. The user ID provided can relate to a student
	 * or a teacher.
	 *
	 * @param $user_id
	 * @param $type
	 * @return array
	 */
	public function list_requests( $user_id, $type ) {
		if ( $this->users->is_teacher( $user_id ) ) return $this->requests->find_for( $user_id, $type );
		if ( $this->users->is_student( $user_id ) ) return $this->requests->find_from( $user_id, $type );
		return array();
	}

	/**
	 * Determines if a request to connect is currently open between the student owner of $student_blog and
	 * $teacher_id.
	 *
	 * @param $student_blog
	 * @param $teacher_id
	 * @param $type
	 * @return bool
	 */
	public function has_pending_request( $student_blog, $teacher_id, $type ) {
		$student_id = $this->network->get_student_for( $student_blog );
		$requests = array();

		if ( self::STUDENT_TEACHER_LINK === $type ) $requests = $this->list_requests( $teacher_id, self::STUDENT_TEACHER_LINK );
		if ( self::STUDENT_TEACHER_UNLINK === $type ) $requests = $this->list_requests( $teacher_id, self::STUDENT_TEACHER_UNLINK );

		foreach ( $requests as $request )
			if ( $request->from === $student_id ) return true;

		return false;
	}

	/**
	 * Approves a student/teacher link request: the teacher will be made a supervisor of the student
	 * site.
	 *
	 * @param $request_id
	 * @return bool
	 */
	public function approve_request( $request_id ) {
		$request = $this->requests->load( $request_id );

		if ( false === $request ) {
			Log::warning( sprintf( __( 'Attempt to approve request %d failed - no such request.', 'wpan' ), $request_id ) );
			return false;
		}

		if ( self::STUDENT_TEACHER_LINK !== $request->type ) {
			Log::warning( sprintf( __( 'Attempt to approve request %d failed - type "%s" expected but type "%s" given.', 'wpan' ), $request_id, self::STUDENT_TEACHER_LINK, $request->type ) );
			return false;
		}

		$student_blog = $this->network->get_primary_blog( $request->from );
		$teacher = $request->to;
		$result = $this->network->assign_teacher_supervisor( $student_blog, $teacher );

		if ( $result ) {
			do_action( 'wpan_student_teacher_link_approved', $request );
			Log::action( sprintf( __( 'Request %d from user %d to user %d approved.', 'wpan' ), $request->id, $request->from, $request->to ) );
			return true;
		}

		Log::warning( sprintf( __( 'Request %d could not be approved due to a system fault.', 'wpan' ), $request->id ) );
		return false;
	}

	public function toolbar_integration( WP_Admin_Bar $wp_admin_bar ) {
		$this->admin_bar = $wp_admin_bar;

		// Who is visiting the site and what blog are they visiting?
		$user = get_current_user_id();
		$blog = get_current_blog_id();
		$show = false;

		// It only makes sense to expose these links to a teacher on a student blog, or student on a teacher blog
		if ( $this->users->is_student( $user ) && $this->network->is_teacher_blog( $blog ) ) $show = true;
		if ( $this->users->is_teacher( $user ) && $this->network->is_student_blog( $blog ) ) $show = true;
		if ( ! $show ) return;

		if ( $this->users->is_student( $user ) ) $this->connection_menu_students();
		if ( $this->users->is_teacher( $user ) ) $this->connection_menu_teachers();
	}

	/**
	 * Adds connect/disconnect links for students on teacher blogs.
	 */
	protected function connection_menu_students() {
		$teacher_id = $this->network->get_teacher_for( get_current_blog_id() );
		$student_blog = $this->network->get_primary_blog( get_current_user_id() );
		$connected = $this->network->is_teacher_supervisor( $student_blog, $teacher_id );

		if ( $connected && $this->has_pending_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_UNLINK ) ) {
			$state = 'pending';
			$status = __( 'You have requested this teacher disconnect from your blog:', 'wpan' );
		}
		elseif ( $connected ) {
			$state = 'connected';
			$status = __( 'You have already connected with this teacher:', 'wpan' );
		}
		elseif ( $this->has_pending_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_LINK ) ) {
			$state = 'pending';
			$status = __( 'A request to connect with this teacher is currently pending:', 'wpan' );
		}
		else {
			$state = 'unconnected';
			$status = __( 'You are not connected to this teacher:', 'wpan' );
		}

		$this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_state',
			'parent' => 'wpan_toolbar',
			'title' => $status
		) );

		$this->connection_choices( $state );
	}

	/**
	 * Shows the connect/disconnect link for teachers visting a student blog.
	 */
	protected function connection_menu_teachers() {
		$student_blog = get_current_blog_id();
		$teacher_id = get_current_user_id();
		$connected = $this->network->is_teacher_supervisor( $student_blog, $teacher_id );

		if ( $connected && $this->has_pending_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_UNLINK ) ) {
			$state = 'connected';
			$status = __( 'Student has requested you disconnect:', 'wpan' );
		}
		elseif ( $connected ) {
			$state = 'connected';
			$status = __( 'You are already connected to this blog', 'wpan' );
		}
		elseif ( $this->has_pending_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_LINK ) ) {
			$state = 'pending';
			$status = __( 'The student has requested you connect to this blog:', 'wpan' );
		}
		elseif ( $this->has_pending_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_UNLINK ) ) {
			$state = 'pending';
			$status = __( 'The student has requested you disconnect from this blog:', 'wpan' );
		}
		else {
			$state = 'unconnected';
			$status = __( 'You are not connected to this blog:', 'wpan' );
		}

		$this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_state',
			'parent' => 'wpan_toolbar',
			'title' => $status
		) );

		$this->connection_choices( $state );
	}

	protected function connection_choices( $state ) {
		$origin = substr( hash( 'md5', uniqid() . get_current_blog_id() ), 5, 17 );
		$is_teacher = $this->users->is_teacher( get_current_user_id() );

		if ( 'connected' === $state ) $this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_disconnect_me',
			'parent' => 'wpan_toolbar',
			'title' => __( '&rarr; Please disconnect', 'wpan' ),
			'href' => add_query_arg( array(
				'wpan_relationship_builder' => 'disconnect',
				'origin' => $origin,
				'confirm' => wp_create_nonce( 'WPAN build relationship' . get_current_user_id() . $origin )
			) )
		) );

		if ( 'unconnected' === $state || ( 'pending' === $state && $is_teacher ) ) $this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_connect_me',
			'parent' => 'wpan_toolbar',
			'title' => __( '&rarr; Connect me!', 'wpan' ),
			'href' => add_query_arg( array(
				'wpan_relationship_builder' => 'connect',
				'origin' => $origin,
				'confirm' => wp_create_nonce( 'WPAN build relationship' . get_current_user_id() . $origin )
			) )
		) );

		$cancel_verbiage = $is_teacher
			? __( '&rarr; Decline this request', 'wpan' )
			: __( '&rarr; Cancel pending request', 'wpan' );

		if ( 'pending' === $state ) $this->admin_bar->add_menu( array(
			'id' => 'wpan_toolbar_relationships_disconnect_me',
			'parent' => 'wpan_toolbar',
			'title' => $cancel_verbiage,
			'href' => add_query_arg( array(
				'wpan_relationship_builder' => 'cancel',
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
			case 'cancel':
			case 'disconnect':
				$this->make_connection();
			break;
		}
	}

	/**
	 * Tries to form or break the connection.
	 */
	protected function make_connection() {
		$operation = $_GET['wpan_relationship_builder']; // Should be 'connect', 'disconnect' or 'cancel'

		$blog_id = get_current_blog_id();
		$user_id = get_current_user_id();
		$is_student = $this->users->is_student( $user_id );
		$is_teacher = $this->users->is_teacher( $user_id );

		if ( $is_student ) {
			$student_blog = $this->network->get_primary_blog( $user_id );
			$teacher_id = $this->network->get_teacher_for( $blog_id );
		}
		elseif ( $is_teacher ) {
			$student_blog = $blog_id;
			$teacher_id = $user_id;
		}

		if ( 'connect' === $operation && $is_teacher ) {
			$this->network->assign_teacher_supervisor( $blog_id, $user_id );
			$this->close_student_teacher_requests( $student_blog, $teacher_id );
		}

		if ( 'disconnect' === $operation && $is_teacher ) {
			$this->network->unassign_teacher_supervisor( $blog_id, $user_id );
			$this->close_student_teacher_requests( $student_blog, $teacher_id );
		}

		if ( 'connect' === $operation && $is_student )
			$this->student_teacher_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_LINK );

		if ( 'disconnect' === $operation && $is_student )
			$this->student_teacher_request( $student_blog, $teacher_id, self::STUDENT_TEACHER_UNLINK );

		if ( 'cancel' === $operation )
			$this->close_student_teacher_requests( $student_blog, $teacher_id );

		// If a teacher disconnects in the student blog admin environment they will no longer have
		// privileges and will see the WP "cheating" message - try to catch this and redirect them
		if ( $is_teacher && 'disconnect' === $operation && is_admin() ) {
			wp_safe_redirect( home_url() );
			exit();
		}
	}
}