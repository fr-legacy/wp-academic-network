<?php
/**
 * The following are normally expected to be passed into this view.
 *
 * @var $before_widget
 * @var $after_widget
 * @var $before_title
 * @var $after_title
 * @var $title
 */

echo $before_widget;

// Add the title wrappers only if we have a title to display
if ( ! empty( $title ) ) echo $before_title . esc_html( $title ) . $after_title;

// Show the following section if the form has not yet been submitted / has been submitted but contains errors ...
if ( ! isset( $processing ) || ( isset( $processing ) && ! empty( $errors ) ) ):
?>
	<p class="preamble">
		<?php _e( 'Students wishing to request a new user account and site on the network can do so here. Please complete the following fields.', 'wpan' ) ?>
	</p>

	<?php if ( isset( $errors ) && is_array( $errors ) ): ?>
		<div class="errors"> <ul>
		<?php foreach ( $errors as $error ): ?>
			<li> <?php esc_html_e( $error ) ?> </li>
		<?php endforeach ?>
		</ul> </div>
	<?php endif ?>

	<?php
	if ( isset( $errors ) ) {
		$error_keys = array_keys( $errors );
		$error_keys = join( ' | ', $error_keys );
	}
	else {
		$error_keys = '';
	}
	?>

	<form method="post" action="<?php esc_attr_e( $form_action ) ?>">

		<?php wp_nonce_field( 'wpan_new_teacher_request', 'wpan_service_request' ) ?>

		<?php do_action( 'wpan_new_student_request_start' ) ?>

		<p>
			<?php $class = ( false !== strpos( $error_keys, 'blogname' ) ) ? 'warning' : '' ?>
			<label for="wpan_blogname"> <?php _e( 'Desired blogname', 'wpan' ) ?> </label>
			<input type="text" name="username" id="wpan_blogname" value="<?php esc_attr_e( isset( $_POST['blogname'] ) ? stripslashes( $_POST['blogname'] ) : '' ) ?>" class="<?php echo $class ?>" />
		</p>

		<p>
			<?php $class = ( false !== strpos( $error_keys, 'username' ) ) ? 'warning' : '' ?>
			<label for="wpan_username"> <?php _e( 'Desired username', 'wpan' ) ?> </label>
			<input type="text" name="username" id="wpan_username" value="<?php esc_attr_e( isset( $_POST['username'] ) ? stripslashes( $_POST['username'] ) : '' ) ?>" class="<?php echo $class ?>" />
		</p>

		<p>
			<?php $class = ( false !== strpos( $error_keys, 'password' ) ) ? 'warning' : '' ?>
			<label for="wpan_password"> <?php _e( 'Password', 'wpan' ) ?> </label>
			<input type="password" name="password" id="wpan_password" value="<?php esc_attr_e( isset( $_POST['password'] ) ? stripslashes( $_POST['password'] ): '' ) ?>" class="<?php echo $class ?>" />
		</p>

		<p>
			<label for="wpan_submit"> <?php _e( 'Submit request', 'wpan' ) ?> </label>
			<input type="submit" name="wpan_submit" id="wpan_submit" value="<?php esc_attr_e( __( 'Send', 'wpan' ) ) ?>" />
		</p>

		<?php do_action( 'wpan_new_student_request_end' ) ?>

	</form>

<?php else: // Show the following section when an error-free submission has been made ... ?>

	<p class="submitted">
		<?php _e( 'Thank you for your submission. Your request is being processed and you should hear from us shortly.', 'wpan' ) ?>
	</p>

<?php
endif;

echo $after_widget;