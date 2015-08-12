<?php
foreach ( $tags as $tag ) {
	echo WPAN\Hubs\Common\Tagging::make_tag( $data['student_id'], $tag );
}

$add_tags_txt = count( $tags ) > 0
	? __( 'Add more tags', 'wpan' )
	: __( 'Add tags', 'wpan' );
?>

<span class="user-tagging-controls"
      data-user_id="<?php echo esc_attr( $data['student_id'] ) ?>"
      data-check="<?php echo esc_attr( wp_create_nonce( 'tag-user-' . $data['student_id'] ) ) ?>">
          <a href="#"><?php echo $add_tags_txt ?></a>
          <input type="text" placeholder="<?php esc_attr_e( 'keyword-1, keyword-2, etc', 'wpan' ) ?>" style="display: none" />
</span>