<?php
/**
 * @var $title
 */

wp_nonce_field( 'wpan_update_observer_gadget_settings', 'wpan_gadget_observer' );
?>

<p>
	<?php _e( 'This widget allows visitors to request an observer account which can be used to give them access to parts of the network. It should of course be positioned in a publicly accessible part of the network.', 'wpan' ) ?>
</p>

<p>
	<label for="title"> <?php _e( 'Title:', 'wpan' ) ?> </label>
	<input type="text" name="title" id="title" value="<?php esc_attr_e( $title ) ?>" class="widefat" />
</p>
