<?php
/**
 * @var $title
 */

wp_nonce_field( 'wpan_update_observer_gadget_settings', 'wpan_gadget_observer' );
?>

<p>
	<label for="title"> <?php _e( 'Title:', 'wpan' ) ?> </label>
	<input type="text" name="title" id="title" value="<?php esc_attr_e( $title ) ?>" class="widefat" />
</p>
