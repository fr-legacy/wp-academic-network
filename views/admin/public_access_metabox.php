<?php
/**
 * @var $checked
 */

$checked = $checked ? 'checked="checked"' : '';
$confirm = wp_create_nonce( 'WPAN public marker' . get_current_user_id() );
?>

<input type="checkbox" name="wpan_public_item" id="wpan_public_item" value="1" <?php echo $checked ?> />
<label for="wpan_public_item"> <?php _e( 'Mark as publicly accessible', 'wpan' ) ?> </label>

<input type="hidden" name="wpan_confirm_public_accessiblity" value="<?php esc_attr_e( $confirm ) ?>" />

<p> <?php _e( 'Do not forget to hit <em>save, update</em> or <em>publish</em> to ensure this setting sticks.', 'wpan' ); ?> </p>
