<?php
namespace WPAN\Helpers;


/**
 * Lazy view encapsulation.
 *
 * @package WPAN
 */
class View {
	/**
	 * Path to the template file.
	 *
	 * @var string
	 */
	protected $template = '';

	/**
	 * Variables to be injected into the template.
	 *
	 * @var array
	 */
	protected $vars = array();


	/**
	 * Returns a view object referencing an admin template.
	 *
	 * @param $template
	 * @param array $vars
	 * @return View
	 */
	public static function admin( $template, array $vars = null ) {
		$template = WPAN_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $template;
		if ( false === strpos( $template, '.php' ) ) $template .= '.php';
		return new self( $template, $vars );
	}

	/**
	 * Returns a view object referencing a front-end template.
	 *
	 * @param $template
	 * @param array $vars
	 * @return View
	 */
	public static function front( $template, array $vars = null ) {
		$template = WPAN_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'front' . DIRECTORY_SEPARATOR . $template;
		if ( false === strpos( $template, '.php' ) ) $template .= '.php';
		return new self( $template, $vars );
	}

	/**
	 * Creates a lazy view object (it will not be rendered until the last possible moment).
	 *
	 * @param $template
	 * @param array $vars
	 */
	public function __construct( $template, array $vars = null ) {
		$this->template = $template;
		$this->vars = ( null === $vars ) ? array() : $vars;
	}

	/**
	 * Used to echo the view.
	 *
	 * @return string
	 */
	public function __toString() {
		ob_start();
		$this->render();
		return ob_get_clean();
	}

	/**
	 * Directly outputs the view.
	 *
	 * Can be used instead of stringifying the object if avoiding output buffering overhead is vital.
	 */
	public function render() {
		if ( ! file_exists( $this->template ) ) {
			Log::error( sprintf( __('View could not be rendered, template "%s" not found.', 'wpan' ), $this->template ) );
			return;
		}
		extract( $this->vars );
		include $this->template;
	}
}