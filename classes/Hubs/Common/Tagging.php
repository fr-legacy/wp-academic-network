<?php
namespace WPAN\Hubs\Common;

use WPAN\Core;

class Tagging
{
	public function __construct() {
		add_action( 'wp_ajax_wpan_remove_user_tag', array( $this, 'remove_user_tag' ) );
		add_action( 'wp_ajax_wpan_add_user_tags', array( $this, 'add_user_tags' ) );
	}

	public static function make_tag( $user_id, $tag ) {
		$tag        = esc_attr( $tag );
		$id_attr    = esc_attr( $user_id );
		$remove_txt = _x( '&#10799;', 'remove-tag', 'wpan' );
		$check_val  = esc_attr( wp_create_nonce( 'remove-user-tag-' . $user_id ) );
		
		return "
			<span class='user-tag' data-user_id='$id_attr' data-check='$check_val' data-tag='$tag'>
				$tag
				<span class='remove'> $remove_txt </span>
			</span>
		";
	}

	public function remove_user_tag() {
		$expected = array_flip( array(
			'user_id', 'check', 'tag'
		) );

		$fields = array_intersect_key( $_POST, $expected );

		if ( count( $fields ) !== 3 || ! wp_verify_nonce( $fields['check'], 'remove-user-tag-' . $_POST['user_id'] ) ) {
			wp_send_json( array(
				'result'  => 'fail',
				'user_id' => absint( @$fields['user_id'] )
			) );
		}

		Core::object()->users()->tags_remove( $fields['user_id'], $fields['tag'] );

		wp_send_json( array(
			'result'  => 'success',
			'user_id' => absint( @$fields['user_id'] ),
			'tag'     => $fields['tag']
		) );
	}

	public function add_user_tags() {
		$expected = array_flip( array(
			'user_id', 'check', 'tags'
		) );

		$fields = array_intersect_key( $_POST, $expected );

		if ( count( $fields ) !== 3 || ! wp_verify_nonce( $fields['check'], 'tag-user-' . $_POST['user_id'] ) ) {
			wp_send_json( array(
				'result'  => 'fail',
				'user_id' => absint( @$fields['user_id'] )
			) );
		}

		$html  = '';
		$users = Core::object()->users();

		foreach ( explode( ',', $fields['tags'] ) as $new_tag ) {
			$tag = $users->tags_add( $fields['user_id'], $new_tag );
			if ( ! $tag ) continue;
			$html .= self::make_tag( $fields['user_id'], $tag );
		}

		wp_send_json( array(
			'result'  => 'success',
			'user_id' => $fields['user_id'],
			'html'    => $html
		) );
	}
}