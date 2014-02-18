<?php
/**
 * @var $observer_accessible
 * @var $publicly_accessible
 */

$choices = apply_filters( 'wpan_public_access_meta_options', array(
	'normal' => __( 'Normal network rules', 'wpan' ),
	'observer' => __( 'Observers (parents)', 'wpan' ),
	'public' => __( 'Public', 'wpan' )
) );

if ( $publicly_accessible ) $selected = 'public';
elseif ( $observer_accessible ) $selected = 'observer';
else $selected = 'normal';

$selected = apply_filters( 'wpan_public_access_meta_choice', $selected );
$confirm = wp_create_nonce( 'WPAN public marker' . get_current_user_id() );
?>
<label for="wpan_public_item"> <?php _e( 'Who can see this?', 'wpan' ) ?> </label>

<select name="wpan_public_item">
	<?php foreach ( $choices as $value => $label ): ?>
		<option value="<?php esc_attr_e( $value ) ?>"<?php if ( $selected === $value ) echo ' selected="selected"' ?>>
			<?php esc_html_e( $label ) ?>
		</option>
	<?php endforeach ?>
</select>

<input type="hidden" name="wpan_confirm_public_accessiblity" value="<?php esc_attr_e( $confirm ) ?>" />

<p> <?php _e( 'Do not forget to hit <em>save, update</em> or <em>publish</em> to ensure this setting sticks.', 'wpan' ); ?> </p>
