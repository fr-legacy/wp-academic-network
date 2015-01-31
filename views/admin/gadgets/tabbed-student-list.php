<?php
/**
 * @var $title
 * @var $show_unsupervised
 * @var $ordering
 */

wp_nonce_field( 'wpan_update_student_list_settings', 'wpan_student_list' );
?>

<p>
	<?php _e( 'This widget generates a list of students established on the network.', 'wpan' ) ?>
</p>

<p>
	<label for="title"> <?php _e( 'Title:', 'wpan' ) ?> </label>
	<input type="text" name="title" id="title" value="<?php esc_attr_e( $title ) ?>" class="widefat" />
</p>
