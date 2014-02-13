<?php
/**
 * @var WP_User $student
 * @var Users $users
 */
use WPAN\Users;
?>

<h4>
	<?php
	esc_html_e( $data['student_name'] );

	// Optionally look for useful sundry details to help identify the user
	$params = $users->get_additional_data( $data['student_id'] );
	$firstname = isset( $params['firstname'] ) ? $params['firstname'] : '';
	$lastname = isset( $params['lastname'] ) ? $params['lastname'] : '';
	$user_identifier = apply_filters( 'wpan_student_user_details_identifier', " $firstname $lastname ", $data['student_id'] );
	?>

	<span class="unbold"> <?php esc_html_e( $user_identifier ) ?> </span>
</h4>

<div class="row-actions">
	<span class="validate">
		<?php $action = wp_nonce_url( add_query_arg( array( 'item' => $data['student_id'], 'action' => 'disconnect' ) ), 'connection_actions' . get_current_user_id(), 'check' ) ?>
		<a title="<?php esc_attr_e( 'Disconnect from this student/their blog', 'wpan' ) ?>" href="<?php echo esc_url( $action ) ?>"><?php _e( 'Disconnect', 'wpan' ) ?></a>
	</span>
</div>