<?php
namespace WPAN;

use WPAN\Helpers\Log,
	WP_User_Query;


/**
 * Utility functions for working within a WordPress network.
 *
 * Class Network
 * @package WPAN
 */
class Network
{
	/**
	 * Used to identify blog meta entries.
	 */
	const META_KEY_PREFIX = 'wpan_blog_';

	/**
	 * @var Users
	 */
	protected $users;


	/**
	 * Sets up system objects ready for helpers to reference.
	 */
	public function __construct() {
		$this->users = Core::object()->users();
	}

	/**
	 * Determines if the specified site (or current site, if no site ID is provided) is the hub.
	 *
	 * The hub in a WPAN context is ordinarily the network and 'root' or main sites within the network.
	 * Useful for determining if admin screens etc with network-level significance should be registered.
	 *
	 * @param null $blog_id
	 * @return bool
	 */
	public function is_hub( $blog_id = null ) {
		return (bool) apply_filters( 'wpan_is_hub', is_main_site( $blog_id ), $blog_id );
	}

	/**
	 * Returns the blog ID for the hub site.
	 *
	 * This works in tandem with Network::is_hub(), therefore if a plugin decides to modify the logic
	 * for determining what the hub is then care should be taken to ensure both methods follow suit.
	 *
	 * @return int blog_id
	 */
	public function get_hub_id() {
		global $current_site;
		return (int) apply_filters( 'wpan_hub_id', $current_site->blog_id );
	}



	/**
	 * Generates a new blog on the network belonging to a teacher.
	 */
	public function create_teacher_blog( $path, $title, $teacher_id ) {
		// Attempt to create the new blog
		$path = apply_filters( 'wpan_new_teacher_blog_path', get_current_site()->path . $path, $teacher_id );
		$domain = apply_filters( 'wpan_new_teacher_blog_domain', get_current_site()->domain, $teacher_id );
		$blog_id = wpmu_create_blog( $domain, $path, $title, $teacher_id );

		if ( is_wp_error( $blog_id ) ) {
			Log::error( sprintf( __( 'Failed to create new teacher blog at %s on %s.', 'wpan' ), $path, $domain ) );
			return false;
		}

		// The new teacher account will have been created as a regular administrator - make them a 'teacher'
		$this->users->change_blog_role( $blog_id, $teacher_id, Users::TEACHER );
		return true;
	}

	/**
	 * Creates a student blog.
	 *
	 * @param $path
	 * @param $title
	 * @param $student_id
	 * @param $supervising_teacher
	 * @return bool
	 */
	public function create_student_blog( $path, $title, $student_id, $supervising_teacher = null ) {
		// Attempt to create the new blog
		$path = apply_filters( 'wpan_new_student_blog_path', get_current_site()->path . $path, $supervising_teacher );
		$domain = apply_filters( 'wpan_new_student_blog_domain', get_current_site()->domain, $supervising_teacher );
		$blog_id = wpmu_create_blog( $domain, $path, $title, $supervising_teacher );

		if ( is_wp_error( $blog_id ) ) {
			Log::error( sprintf( __( 'Failed to create new student blog at %s on %s.', 'wpan' ), $path, $domain ) );
			return false;
		}

		// Assign the student
		if ( false === $this->users->is_student( $student_id ) ) {
			Log::warning( sprintf( __( 'User %d is not a student: blog %d has been created but the user was not assigned.', 'wpan' ), $student_id, $blog_id) );
			return false;
		}

		if ( false === add_user_to_blog( $blog_id, $student_id, Users::STUDENT ) ) {
			Log::warning( sprintf( __( 'Student user %d could not be assigned blog %d.', 'wpan' ), $student_id, $blog_id) );
			return false;
		}

		// Assign the supervising teacher (if provided)
		if ( null !== $supervising_teacher && $this->users->is_teacher( $supervising_teacher ) )
			$this->assign_teacher_supervisor( $blog_id, $supervising_teacher );

		Log::action( sprintf( __( 'New student blog %d has been built at %s for student user %d.', 'wpan' ), $blog_id, $path, $student_id ) );
		return true;
	}

	/**
	 * Attempts to assign a teacher as one of the supervisors
	 * @param $student_blog
	 * @param $teacher_id
	 * @return bool
	 */
	public function assign_teacher_supervisor( $student_blog, $teacher_id ) {
		// Ensure the teacher ID does indeed represent a teacher-role user
		if ( false === $this->users->is_teacher( $teacher_id ) ) {
			Log::error( sprintf( __( 'User %d is not a teacher and cannot be assigned as a supervisor for blog %d.', 'wpan' ), $teacher_id, $student_blog ) );
			return false;
		}

		// Is the target blog actually a student blog?
		if ( false === $this->is_student_blog( $student_blog ) ) {
			Log::error( sprintf( __( 'Blog %d is not the primary blog of any student users: cannot assign a teacher supervisor.', 'wpan' ), $student_blog ) );
			return false;
		}

		$success = add_user_to_blog( $student_blog, $teacher_id, Users::TEACHER );

		if ( $success )
			Log::action( sprintf( __( 'Teacher %d assigned as a supervisor of blog %d.', 'wpan' ), $teacher_id, $student_blog ) );

		return $success;
	}

	/**
	 * Unassigns a teacher from a student blog.
	 *
	 * @param $student_blog
	 * @param $teacher_id
	 * @return bool
	 */
	public function unassign_teacher_supervisor( $student_blog, $teacher_id ) {
		// Ensure the teacher ID does indeed represent a teacher-role user
		if ( false === $this->users->is_teacher( $teacher_id ) )
			Log::warning( sprintf( __( 'User %d is not a teacher but WPAN will attempt to remove them from blog %d anyway.', 'wpan' ), $teacher_id, $student_blog ) );

		// Is the target blog actually a student blog?
		if ( false === $this->is_student_blog( $student_blog ) )
			Log::warning( sprintf( __( 'Unassign teacher request: blog %d is not the primary blog of any student users.', 'wpan' ), $student_blog ) );

		if ( false === remove_user_from_blog( $teacher_id, $student_blog ) ) {
			Log::error( sprintf( __( 'Failed to unassign teacher %d from blog %d.', 'wpan' ), $teacher_id, $student_blog ) );
			return false;
		}

		Log::action( sprintf( __( 'Teacher %d removed as a supervisor of blog %d.', 'wpan' ), $teacher_id, $student_blog ) );
		return true;
	}

	/**
	 * Returns the primary blog of the specified user or boolean false if the user does not
	 * exist/does not have a primary blog.
	 *
	 * @param $user_id
	 * @return mixed int | bool
	 */
	public function get_primary_blog ( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		if ( false === $user ) {
			Log::error( sprintf( __( 'Attempt to determine primary blog failed: unable to load user record for user %d.', 'wpan' ), $user_id ) );
			return false;
		}

		$primary_blog = get_user_meta( $user_id, 'primary_blog', true );

		if ( empty( $primary_blog ) ) {
			Log::warning( sprintf( __( 'User %d exists but does not have a primary blog.', 'wpan' ), $user_id ) );
			return false;
		}

		return absint( $primary_blog );
	}

	/**
	 * Determines if the blog is a student-led blog: that is, it has a student-role user
	 * assigned to it and it is the primary blog for that same student.
	 *
	 * @param $blog_id
	 * @return bool
	 */
	public function is_student_blog( $blog_id ) {
		// Does the blog exist?
		if ( false === get_blog_details( $blog_id ) ) return false;

		// Look for user meta records relating to this blog
		$users = new WP_User_Query( array(
			'meta_key' => 'primary_blog',
			'meta_value' => $blog_id
		) );

		// No results indicates no users have this as their primary blog
		if ( empty( $users->results) ) return false;

		// Check each returned result and test to see if at least one user is a student
		foreach ( $users->results as $user )
			if ( $this->users->is_student( $user->ID ) ) return true;

		return false;
	}

	/**
	 * Determines if the blog is a teacher-led blog: that is, it has a teacher-role user
	 * assigned to it and it is the primary blog for that same student.
	 *
	 * @param $blog_id
	 * @return bool
	 */
	public function is_teacher_blog( $blog_id ) {
		// Does the blog exist?
		if ( false === get_blog_details( $blog_id ) ) return false;

		// Look for user meta records relating to this blog
		$users = new WP_User_Query( array(
			'meta_key' => 'primary_blog',
			'meta_value' => $blog_id
		) );

		// No results indicates no users have this as their primary blog
		if ( empty( $users->results) ) return false;

		// Check each returned result and test to see if at least one user is a student
		foreach ( $users->results as $user )
			if ( $this->users->is_teacher( $user->ID ) ) return true;

		return false;
	}
}