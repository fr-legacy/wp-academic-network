<?php
/**
 * The following are normally expected to be passed into this view.
 *
 * @var $before_widget
 * @var $after_widget
 * @var $before_title
 * @var $after_title
 * @var $title
 * @var $processing
 */

echo $before_widget;

// Add the title wrappers only if we have a title to display
if ( ! empty( $title ) ) echo $before_title . esc_html( $title ) . $after_title;

// Show the following section if the form has not yet been submitted / has been submitted but contains errors ...
if ( ! $processing || ! empty( $errors ) ):
	?>
	<p class="preamble">
		<?php _e( 'To gain access to the network please supply your desired username and a valid email address.', 'wpan' ) ?>
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

	$form_action = isset( $form_action ) ? $form_action : '';
	?>

	<form method="post" action="<?php esc_attr_e( $form_action ) ?>">

		<?php
		$base_token = hash( 'md5', uniqid() . date( 'Y-m-d H:i:s' ) . rand( 1, 16000000 ) );
		$origin = substr( $base_token, 5, 15 );
		$nonce_name = 'wpan_service_' . substr( hash( 'md5', $origin . NONCE_SALT ), 5, 15 );
		?>
		<input type="hidden" name="origin" value="<?php esc_attr_e( $origin ) ?>" />
		<?php wp_nonce_field( 'wpan_new_observer_request' . $origin, $nonce_name ) ?>

		<?php do_action( 'wpan_new_student_request_start' ) ?>

		<p>
			<?php $class = ( false !== strpos( $error_keys, 'username' ) ) ? 'warning' : '' ?>
			<label for="wpan_username"> <?php _e( 'Desired username', 'wpan' ) ?> </label>
			<input type="text" name="username" id="wpan_username" value="<?php esc_attr_e( isset( $_POST['username'] ) ? stripslashes( $_POST['username'] ) : '' ) ?>" class="<?php echo $class ?>" />
		</p>

		<p>
			<?php $class = ( false !== strpos( $error_keys, 'email' ) ) ? 'warning' : '' ?>
			<label for="wpan_email"> <?php _e( 'Email', 'wpan' ) ?> </label>
			<input type="text" name="email" id="wpan_email" value="<?php esc_attr_e( isset( $_POST['email'] ) ? stripslashes( $_POST['email'] ): '' ) ?>" class="<?php echo $class ?>" />
		</p>

		<p>
			<input type="submit" name="wpan_submit" id="wpan_submit" value="<?php esc_attr_e( __( 'Send request', 'wpan' ) ) ?>" />
		</p>

		<?php do_action( 'wpan_new_observer_request_end' ) ?>

	</form>

<?php else: // Show the following section when an error-free submission has been made ... ?>

	<p class="submitted">
		<?php _e( 'Thank you for your submission. Your request is being processed and you should hear from us shortly.', 'wpan' ) ?>
	</p>

<?php
endif;

echo $after_widget;