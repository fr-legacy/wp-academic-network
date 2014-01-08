<?php
namespace WPAN\Gadgets;

use WPAN\View,
	WP_Widget;


/**
 * Gadgets are combined widgets and shortcodes: classes inheriting from the abstract
 * base gadget are responsible for implementing those methods required by WP_Widget.
 *
 * @package WPAN\Gadgets
 */
abstract class BaseGadget extends WP_Widget {
	public static function register() {
		$class = get_called_class();
		register_widget( $class );
	}

	/**
	 * Sets up the widget and shortcode that comprise the gadget.
	 *
	 * @param bool $id_base
	 * @param bool $name
	 */
	public function __construct( $id_base = false, $name = false ) {
		add_shortcode( $this->shortcode_name(), array( $this, 'do_shortcode' ) );

		$id_base = ( false === $id_base ) ? $this->widget_base() : $id_base;
		$name = ( false === $name ) ? $this->widget_name() : $name;

		parent::__construct( $id_base, $name, array(
			'description' => $this->widget_description()
		) );
	}

	/**
	 * Implementing methods should return the widget's ID base.
	 *
	 * @return string
	 */
	protected abstract function widget_base();

	/**
	 * The duty of this method is to return the (human friendly) widget name.
	 *
	 * @return string
	 */
	protected abstract function widget_name();

	/**
	 * The duty of this method is to return the widget description.
	 *
	 * @return string
	 */
	protected abstract function widget_description();

	/**
	 * The duty of this method is to return the name used for the shortcode.
	 *
	 * @return string
	 */
	protected abstract function shortcode_name();


	/**
	 * Processes shortcode requests.
	 *
	 * @param $options
	 * @return string
	 */
	public function do_shortcode( $options ) {
		$widget_class = get_class( $this );
		$args = apply_filters( 'wpan_' . strtolower( $widget_class ) . '_gadget_shortcode_defaults', $this->default_args() );

		ob_start();
		the_widget( $widget_class, $options, $args );
		return ob_get_clean();
	}

	/**
	 * Provides an array of reasonable default args for the display of widgets/shortcodes.
	 *
	 * @return array
	 */
	protected function default_args() {
		return array(
			'before_widget' => '<div class="wpan gadget shortcode">',
			'after_widget' => '</div>',
			'before_title' => '<h4>',
			'after_title' => '</h4>'
		);
	}

	public function widget( $args, $instance ) {}

	public function form( $instance ) {}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	/**
	 * Returns a gadget view. It automatically assumes the front/gadget directory and there is no
	 * need to specify the file extension.
	 *
	 * @param $template_path
	 * @param array $vars
	 * @return View
	 */
	protected function public_view( $template_path, array $vars = null ) {
		$template_path = WPAN_DIR . '/views/front/gadgets/' . $template_path . '.php';
		return new View( $template_path, $vars );
	}
}