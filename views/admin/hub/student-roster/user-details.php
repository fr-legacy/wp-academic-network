<?php
/**
 * @var $student
 */
?>

<h4>
	<?php esc_html_e( $student->user_login ) ?>
</h4>

<div class="row-actions">
	<span class="validate">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $student->ID, 'action' => 'validate' ) ), 'student_actions' . get_current_user_id(), 'check' ) ?>
		<a class="purge" title="<?php esc_attr_e( 'Marks the account as still being current', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Validate now', 'wpan' ) ?></a>
	</span>
	|
	<span class="purge danger_action">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $student->ID, 'action' => 'purge' ) ), 'student_actions' . get_current_user_id(), 'check' ) ?>
		<a class="purge" title="<?php esc_attr_e( 'Purge this user account and any associated blogs', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Purge completely', 'wpan' ) ?></a>
	</span>
</div>