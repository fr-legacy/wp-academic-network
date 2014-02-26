<?php
namespace WPAN\Gadgets;

use WPAN\Core,
	WPAN\Helpers\BaseGadget,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress;


class StudentList extends BaseGadget
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
	 * Runs at the tailend of the parent class constructor: sets up listeners to check for submissions etc.
	 */
	protected function setup() {
		$core = Core::object();
		$this->network = $core->network();
		$this->users = $core->users();
	}

	/**
	 * Friendly name for the gadget when used within the widgets admin screen.
	 *
	 * @return string
	 */
	protected function widget_name() {
		return _x( 'Academic Network: Student List', 'wpan' );
	}

	/**
	 * Explains the role of the gadget within the widgets admin screen.
	 * @return string
	 */
	protected function widget_description() {
		return _x( 'Provides a list of all students and their associated blogs on the network.', 'widget description', 'wpan' );
	}

	/**
	 * Shortcode handle.
	 *
	 * @return string
	 */
	protected function shortcode_name() {
		return _x( 'wpan_student_list', 'gadget shortcode', 'wpan' );
	}

	/**
	 * @return string
	 */
	protected function widget_base() {
		return 'wpan_student_list';
	}

	/**
	 * Displays the widget admin settings form.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$show_unsupervised = isset( $instance['show_unsupervised'] ) ? $instance['show_unsupervised'] : '';

		echo View::admin( 'gadgets/student-list', array(
			'title' => $title,
			'show_unsupervised' => (bool) $show_unsupervised
		) );
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

		return $new_instance;
	}

	/**
	 * Renders the gadget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$students = $this->load_student_data();
		$instance['students'] = $students;

		$params = array_merge( (array) $args, (array) $instance );
		echo $this->public_view( 'student-list', $params );
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
}