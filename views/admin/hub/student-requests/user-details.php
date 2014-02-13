<?php
/**
 * @var WP_User $student
 * @var Users $users
 * @var $request
 */
use WPAN\Users;
?>

<h4>
	<?php
	esc_html_e( $student->display_name );

	// Optionally look for useful sundry details to help identify the user
	$params = $users->get_additional_data( $student->ID );
	$firstname = isset( $params['firstname'] ) ? $params['firstname'] : '';
	$lastname = isset( $params['lastname'] ) ? $params['lastname'] : '';
	$user_identifier = apply_filters( 'wpan_student_user_details_identifier', " $firstname $lastname ", $student->ID );
	?>

	<span class="unbold"> <?php esc_html_e( $user_identifier ) ?> </span>
</h4>

<div class="row-actions">
	<span class="confirm">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $request->ID, 'action' => 'approve' ) ), 'connection_actions' . get_current_user_id(), 'check' ) ?>
		<a title="<?php esc_attr_e( 'Approve request', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Approve', 'wpan' ) ?></a>
	</span>
	|
	<span class="decline danger_action">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $request->ID, 'action' => 'decline' ) ), 'connection_actions' . get_current_user_id(), 'check' ) ?>
		<a title="<?php esc_attr_e( 'Decline', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Decline', 'wpan' ) ?></a>
	</span>
</div>