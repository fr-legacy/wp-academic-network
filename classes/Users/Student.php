<?php
namespace WPAN\Users;


/**
 * Student-level user. Normally associated with at least one teacher and possibly many.
 *
 * Class Student
 * @package WPAN\Users
 */
class Student extends User {
	/**
	 * Students by default fulfil an editor-like role, with the added power
	 * of being able to change and adjust their theme (and export data).
	 *
	 * @var array
	 */
	protected $caps = array(
		'switch_themes' => true,
		'edit_theme_options' => true,
		'export' => true
	);

	public function get_role() {
		return 'wpan_student';
	}

	public function get_display_name() {
		return __( 'Academic Network Student', 'wpan' );
	}

	public function get_capabilities() {
		$editor_caps = User::get_existing_role_capabilities( 'editor' );
		return array_merge( $editor_caps, $this->caps );
	}
}