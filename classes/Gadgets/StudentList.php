<?php
namespace WPAN\Gadgets;

use WPAN\Core,
	WPAN\Helpers\BaseGadget,
	WPAN\Helpers\View,
	WPAN\Helpers\WordPress;


class StudentList extends BaseGadget
{
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
	 * Runs at the tailend of the parent class constructor: sets up listeners to check for submissions etc.
	 */
	protected function setup() {

	}

	/**
	 * Displays the widget admin settings form.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		echo View::admin( 'gadgets/student-list', array( 'title' => $title ) );
	}

	/**
	 * Handles gadget settings updates.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		if ( ! wp_verify_nonce( $_POST['wpan_student_list'], 'wpan_update_student_list_settings' ) ) return;
		if ( isset($_POST['title'] ) ) return array( 'title' => $_POST['title' ] );
		else return array( 'title' => '' );
	}

	/**
	 * Renders the gadget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$params = array_merge( (array) $args, (array) $instance );
		echo $this->public_view( 'student-list', $params );
	}
}