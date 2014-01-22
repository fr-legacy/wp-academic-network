<?php
namespace WPAN\Gadgets;

use WPAN\Helpers\BaseGadget,
	WPAN\Helpers\View;


class ObserverSignup extends BaseGadget
{
	/**
	 * Friendly name for the gadget when used within the widgets admin screen.
	 *
	 * @return string
	 */
	protected function widget_name() {
		return _x( 'Academic Observer Accounts', 'wpan' );
	}

	/**
	 * Explains the role of the gadget within the widgets admin screen.
	 * @return string
	 */
	protected function widget_description() {
		return _x( 'Used to facilitate the user signup process for academic observer accounts (a useful step when giving parents access to student blogs, etc).', 'widget description', 'wpan' );
	}

	/**
	 * Shortcode handle.
	 *
	 * @return string
	 */
	protected function shortcode_name() {
		return _x( 'wpan_observer_signup', 'gadget shortcode', 'wpan' );
	}

	/**
	 * @return string
	 */
	protected function widget_base() {
		return 'wpan_observer_signup';
	}

	/**
	 * Displays the widget admin settings form.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		echo View::admin( 'gadgets/observer-signup', array( 'title' => $title ) );
	}

	/**
	 * Handles gadget settings updates.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		if ( ! wp_verify_nonce( $_POST['wpan_gadget_observer'], 'wpan_update_observer_gadget_settings' ) ) return;
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
		$params = array_merge( $args, $instance );
		echo $this->public_view( 'observer-signup', $params );
	}
}