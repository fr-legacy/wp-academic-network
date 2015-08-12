<?php
namespace WPAN\Hubs\Common;


class LiveEdits {
	public function __construct() {
		add_action( 'wp_ajax_wpan_student_display_name', array( $this, 'student_display_name' ) );
	}

	public function student_display_name() {
		$expected = array_flip( array(
			'user_id', 'check', 'new_name'
		) );

		$fields = array_intersect_key( $_POST, $expected );

		if ( count( $fields ) !== 3 || ! wp_verify_nonce( $fields['check'], 'edit-name-' . $fields['user_id'] ) ) {
			wp_send_json( array(
				'result'  => 'fail',
				'user_id' => absint( @$fields['user_id'] )
			) );
		}

		wp_update_user( array(
			'ID'           => absint( $fields['user_id'] ),
			'display_name' => sanitize_text_field( $fields['new_name'] )
		) );

		$user = get_user_by( 'id', absint( $fields['user_id'] ) );

		wp_send_json( array(
			'result'   => 'success',
			'user_id'  => absint( $fields['user_id'] ),
			'new_name' => esc_html( $user->display_name )
		) );
	}
}