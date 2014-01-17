<?php
/**
 * @var WP_User $student
 * @var Users $users
 */
use WPAN\Users;
?>

<h4>
	<?php
	esc_html_e( $student->user_login );

	// Optionally look for useful sundry details to help identify the user
	$params = $users->get_student_params( $student->ID );
	$firstname = isset( $params['firstname'] ) ? $params['firstname'] : '';
	$lastname = isset( $params['lastname'] ) ? $params['lastname'] : '';
	$user_identifier = apply_filters( 'wpan_roster_table_user_details_identifier', " $firstname $lastname " );
	?>

	<span class="unbold"> <?php esc_html_e( $user_identifier ) ?> </span>
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