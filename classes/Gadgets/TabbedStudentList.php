<?php
namespace WPAN\Gadgets;

use WPAN\Core,
    WPAN\Helpers\BaseGadget,
    WPAN\Helpers\Utilities,
    WPAN\Helpers\View,
    WPAN\Users;


class TabbedStudentList extends BaseGadget
{
	/**
	 * @var \WPAN\Network
	 */
	protected $network;

	/**
	 * @var \WPAN\Users
	 */
	protected $users;

	/**
	 * Useful on a teacher's site: limits the result set to those students connected with the
	 * site owner (ie, the teacher).
	 *
	 * @var boolean
	 */
	protected $connected_students = false;


	/**
	 * Runs at the tailend of the parent class constructor: sets up listeners to check for submissions etc.
	 */
	protected function setup() {
		$core = Core::object();
		$this->network = $core->network();
		$this->users = $core->users();
		$this->panel_requests();
	}

	/**
	 * Friendly name for the gadget when used within the widgets admin screen.
	 *
	 * @return string
	 */
	protected function widget_name() {
		return _x( 'Academic Network: Tabbed Student List', 'wpan' );
	}

	/**
	 * Explains the role of the gadget within the widgets admin screen.
	 *
	 * @return string
	 */
	protected function widget_description() {
		return _x( 'Provides a list of all students and their associated blogs on the network, organized alphabetically and presented in a tabbed interface.', 'widget description', 'wpan' );
	}

	/**
	 * Shortcode handle.
	 *
	 * @return string
	 */
	protected function shortcode_name() {
		return _x( 'wpan_tabbed_student_list', 'gadget shortcode', 'wpan' );
	}

	/**
	 * @return string
	 */
	protected function widget_base() {
		return 'wpan_tabbed_student_list';
	}

	/**
	 * Displays the widget admin settings form.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		echo View::admin( 'gadgets/tabbed-student-list', array( 'title' => $title ) );
	}

	/**
	 * Handles gadget settings updates.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = (array) $new_instance;
		if ( ! wp_verify_nonce( $_POST['wpan_student_list'], 'wpan_update_student_list_settings' ) ) return $old_instance;

		if ( isset($_POST['title'] ) ) $new_instance['title'] = $_POST['title'];
		if ( isset($_POST['show_unsupervised'] ) ) $new_instance['show_unsupervised'] = (bool) $_POST['show_unsupervised'];
		if ( isset($_POST['ordering'] ) ) $new_instance['ordering'] = ( $_POST['ordering'] === 'blog' ) ? 'blog' : 'user';

		return $new_instance;
	}

	/**
	 * Renders the gadget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$params = array_merge( (array) $args, (array) $instance );

		if ( Utilities::is_true( @$params['connected_students' ] ) )
			$this->connected_students = true;

		$tabs = $this->get_tabs();
		$first = ! empty( $tabs ) ? $tabs[0]->header : '';

		$params['id'] = 'wpan_tabbed_student_list'; // @todo set dynamically to support multiple concurrent instances of gadget?
		$params['tabs'] = $tabs;
		$params['first_panel'] = ! empty( $first ) ? $this->get_panel( $first ) : '';
		echo $this->public_view( 'tabbed-student-list', $params );

		// Supporting JS
		$deps = array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' );
		wp_enqueue_script( 'wpan_tabbed_student_list', WPAN_URL . 'resources/tabbed-student-list.js', $deps, false, true );

		wp_localize_script( 'wpan_tabbed_student_list', 'wpan_tabbed_student_list', array(
			'ajax_url'             => get_admin_url( null, 'admin-ajax.php' ),
			'css_url'              => WPAN_URL . 'resources/tabbed-student-list.css',
			'loading_html'         => '<p>' . __( 'Loading&hellip;', 'wpan' ) . '</p>',
			'loading_failure_html' => '<p>' . __( 'Failed to load list.', 'wpan' ) . '</p>',
			'connected_students'   => $this->connected_students,
			'check'                => wp_create_nonce( 'wpan_panel_data_request' )
		) );
	}

	/**
	 * Build a list of tab headers based on student usernames.
	 *
	 * A custom query is used here for performance reasons; using methods from
	 * Core::object()->users would not be viable with a very large userbase which is
	 * the sort of scenario in which this gadget would be utilized.
	 */
	protected function get_tabs() {
		/** @var \wpdb */
		global $wpdb;

		// Generate possible connected students clause
		$connected_students_clause = $this->get_connected_students_clause();
		if ( ! empty( $connected_students_clause ) ) $connected_students_clause .= ' AND ';

		$query = "
			SELECT
				SUBSTR( display_name, 1, 1 ) AS header,
				COUNT( SUBSTR( display_name, 1, 1 ) ) as entries
			FROM
				$wpdb->users
					INNER JOIN
				$wpdb->usermeta ON ( $wpdb->users.ID = $wpdb->usermeta.user_id )
			WHERE
				$connected_students_clause
				$wpdb->usermeta.meta_key = 'wpan_academic_role'
				AND CAST( $wpdb->usermeta.meta_value AS CHAR ) = %s
			GROUP BY SUBSTR( display_name, 1, 1 )
			ORDER BY display_name ASC
		";

		return (array) $wpdb->get_results( $wpdb->prepare( $query, Users::STUDENT ) );
	}

	protected function panel_requests() {
		$handler = array( $this, 'panel_request_handler' );
		add_action( 'wp_ajax_wpan_tabbed_student_list_get_panel', $handler );
		add_action( 'wp_ajax_nopriv_wpan_tabbed_student_list_get_panel', $handler );
	}

	public function panel_request_handler() {
		$security_check = wp_verify_nonce( @$_POST['check'], 'wpan_panel_data_request' );
		$tab_request    = isset( $_POST['tab'] ) && strlen( $_POST['tab'] ) > 0;

		if ( ! $security_check || ! $tab_request ) exit( json_encode( array(
			'status' => 'fail',
			'reason' => ! $security_check
				? _x( 'Unauthorized', 'response failure message', 'wpan' )
				: _x( 'Could not determine tab', 'response failure message', 'wpan' )
		) ) );

		if ( @$_POST['connected_students'] ) $this->connected_students = true;

		exit( json_encode( array(
			'status' => 'success',
			'html'   => (string) $this->get_panel( $_POST['tab'] )
		) ) );
	}

	protected function get_panel( $first_char ) {
		/** @var \wpdb */
		global $wpdb;

		// Generate possible connected students clause
		$connected_students_clause = $this->get_connected_students_clause();
		if ( ! empty( $connected_students_clause ) ) $connected_students_clause .= ' AND ';

		$query = "
			SELECT
				ID
			FROM
				wp_users
					INNER JOIN
				wp_usermeta ON (wp_users.ID = wp_usermeta.user_id)
			WHERE
				$connected_students_clause
				wp_usermeta.meta_key = 'wpan_academic_role'
					AND CAST(wp_usermeta.meta_value AS CHAR) = %s
					AND 1 = LOCATE( %s, display_name )
		";

		$students = (array) $wpdb->get_col( $wpdb->prepare( $query, Users::STUDENT, $first_char ) );

		foreach ( $students as &$user ) {
			// Form a stdClass object we can assign further properties to, etc
			$entity = new \stdClass;
			$entity->user = get_user_by( 'id', absint( $user ) );

			$entity->blog = new \stdClass();
			$entity->blog->id = $this->network->get_primary_blog( $entity->user->ID );
			$entity->blog->supervised = (bool) $this->network->get_teacher_for( $entity->blog->id );
			$entity->blog->details = get_blog_details( $entity->blog->id );

			$user = $entity;
		}

		$current_user = get_current_user_id();
		$is_admin     = $this->users->is_teacher( $current_user ) || user_can( $current_user, 'manage_options' );

		return $this->public_view( 'tabbed-student-list-panel', array(
			'students'   => $students,
			'admin_user' => $is_admin
		) );
	}

	/**
	 * Builds a list (as an array) of student users and their associated blogs, if they have
	 * one. Each entry takes the form of an object with a student property (a WP_User object)
	 * and a blog property.
	 *
	 * @return array
	 */
	protected function load_student_data() {
		$students = array();

		foreach ( $this->users->get_students() as $student ) {
			$entity = new \stdClass();
			$entity->student = $student;

			$entity->blog = new \stdClass();
			$entity->blog->id = $this->network->get_primary_blog( $student->ID );
			$entity->blog->supervised = (bool) $this->network->get_teacher_for( $entity->blog->id );
			$entity->blog->details = get_blog_details( $entity->blog->id );

			$students[] = $entity;
		}

		return $students;
	}


	/**
	 * Provides a where clause if needed (may be an empty string if it is not needed)
	 * for use in SQL queries, to narrow the result set to connected students.
	 *
	 * @return string
	 */
	protected function get_connected_students_clause() {
		global $wpdb;

		// Do we want to limit the result set to connected students?
		if ( ! $this->connected_students ) return '';

		$connected_students = $this->get_connected_student_ids();

		return ! empty( $connected_students )
			? " $wpdb->users.ID IN ( " . join( ', ', $connected_students ) . " ) "
			: '';
	}

	/**
	 * Returns a list of students connected with the teacher.
	 *
	 * @return array
	 */
	protected function get_connected_student_ids() {
		$result = array();

		$teacher = $this->network->get_teacher_for( get_current_blog_id() );
		if ( ! $teacher ) return $result;

		foreach( $this->network->get_supervised_blogs( $teacher ) as $student ) {
			$result[] = (int) $student['student_id'];
		}

		return $result;
	}
}