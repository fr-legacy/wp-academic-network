<?php
namespace WPAN\Gadgets;
use WPAN\Requests\Manager as Request_Manager;
use WPAN\WordPress;


/**
 * Used to request a teacher or student presence on the network.
 *
 * @package WPAN\Gadgets
 */
class New_Teacher_Request extends Base {

	protected function shortcode_name() {
		return apply_filters( 'wpan_new_teacher_request_shortcode_name', 'wpan_new_teacher_request' );
	}

	protected function widget_base() {
		return 'wpan_teacher_request';
	}

	protected function widget_name() {
		return __( 'New Teacher Request Form', 'wpan' );
	}

	protected function widget_description() {
		return __( 'Allows teachers to apply for a site and user account on the network.', 'wpan' );
	}

	public function widget( $args, $instance ) {
		$args = wp_parse_args( $args, $this->default_args() );
		$request_manager = Request_Manager::object();

		if ( $request_manager->teacher->request_received ) {
			$instance['processing'] = true;
			$instance['errors'] = $request_manager->teacher->get_errors();
		}
		if ( ! isset( $instance['title'] ) ) $instance['title'] = __( 'New Teacher Request', 'wpan' );
		if ( ! isset( $instance['form_action'] ) ) $instance['form_action'] = WordPress::safe_form_action();

		echo $this->public_view( 'teacher-request', array_merge( $args, $instance ) );
	}

	public function form( $instance ) {
		echo '<p>' . __( 'Generates a form used to solicit new user account and site requests from teachers.', 'wpan' ) . '</p>';
		echo '<p>' . __( 'This can also be used from within posts and pages, via the [wpan_new_teacher_request] shortcode.', 'wpan' ) . '</p>';
	}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}
}