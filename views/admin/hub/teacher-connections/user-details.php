<?php
/**
 * @var WP_User $student
 * @var Users $users
 */
use WPAN\Users;
?>

<h4>
	<?php
	esc_html_e( $teacher->display_name );

	// Optionally look for useful sundry details to help identify the user
	$params = $users->get_additional_data( $teacher->ID );
	$firstname = isset( $params['firstname'] ) ? $params['firstname'] : '';
	$lastname = isset( $params['lastname'] ) ? $params['lastname'] : '';
	$user_identifier = apply_filters( 'wpan_teacher_user_details_identifier', " $firstname $lastname ", $teacher->ID );
	?>

	<span class="unbold"> <?php esc_html_e( $user_identifier ) ?> </span>
</h4>