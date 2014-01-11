<?php
/**
 * @var $student
 */
?>

<h4>
	<?php esc_html_e( $student->user_login ) ?>
	<span class="unbold user email">
		<a href="mailto:<?php esc_attr_e( $student->user_email ) ?>"><?php esc_attr_e( $student->user_email ) ?></a>
	</span>
</h4>

<div class="row-actions">
	<span class="purge danger_action">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $student->ID, 'action_request' => 'purge' ) ), 'student_actions' . get_current_user_id(), 'check' ) ?>
		<a class="purge" title="<?php esc_attr_e( 'Purge this user account and any associated blogs', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Purge completely', 'wpan' ) ?></a>
	</span>
</div>