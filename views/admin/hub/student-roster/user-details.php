<?php
/**
 * @var WP_User $student
 * @var Users $users
 */
use WPAN\Users;
?>

<h4>
	<span class="user-display-name inline-editable"
	      title="<?php echo esc_attr( sprintf( __( 'Actual login: %s', 'wpan' ), $student->user_login ) ) ?>"
	      data-user-id="<?php echo esc_attr( $student->ID ) ?>"
	      data-check="<?php echo esc_attr( wp_create_nonce( 'edit-name-' . $student->ID ) ) ?>">
	          <?php echo esc_html( esc_html_e( $student->display_name ) ) ?>
	</span>

	<?php
	// Optionally look for useful sundry details to help identify the user
	$params = $users->get_additional_data( $student->ID );
	$firstname = isset( $params['firstname'] ) ? $params['firstname'] : '';
	$lastname = isset( $params['lastname'] ) ? $params['lastname'] : '';
	$user_identifier = apply_filters( 'wpan_roster_table_user_details_identifier', " $firstname $lastname ", $student );
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