<?php
/**
 * @var $work_in_progress
 * @var $job_details
 */
?>
<h4> <?php _e( 'Upload a roster update file', 'wpan' ) ?> </h4>

<?php if ( $work_in_progress) : ?>
	<div class="section_wrapper warning"> <p>
		<strong> <?php _e( 'Roster updates are currently in progress.', 'wpan') ?> </strong>
		<?php _e( 'You can upload a new set of roster updates, however doing so will destroy the current update task and replace it with the new one.', 'wpan' ) ?>
	</p> </div>
<?php endif ?>

<?php
/**
 * We need to close the form element already opened by the admin view wrapper and open a new form element
 * suitable for file uploads. We don't need to close it, however, as the admin view wrapper automatically
 * adds a closing form element.
 */
echo '</form>';

$unique_id = uniqid();
$nonce = wp_create_nonce( get_current_user_id() . $unique_id . 'Updated roster' );
?>
<form method="post" enctype="multipart/form-data">

	<input type="file" name="teacher_roster" /> <br/>
	<input type="submit" name="<?php esc_attr_e( $unique_id ) ?>" value="<?php _e( 'Upload', 'wpan' ) ?>" class="button primary" />
	<input type="hidden" name="origin" value="<?php esc_attr_e( $nonce ) ?>" />

<p> <?php _e( 'Your file should be CSV format (comma separated values). There must be a row of column headers and there should be no blank lines before this. As a guide, expected column headers are listed below.', 'wpan' ) ?> </p>

<div class="section_wrapper">
	<table class="csv_headers_list">
		<tr>
			<th scope="row"> <?php _e( 'uaid', 'wpan') ?> </th>
			<td> <?php _e( 'Staff number or other unique identifier', 'wpan') ?> </td>
		</tr>

		<tr>
			<th scope="row"> <?php _e( 'username', 'wpan') ?> </th>
			<td> <?php _e( 'The desired username', 'wpan') ?> </td>
		</tr>

		<tr>
			<th scope="row"> <?php _e( 'email', 'wpan') ?> </th>
			<td> <?php _e( 'Email address', 'wpan') ?> </td>
		</tr>

		<tr>
			<th scope="row"> <?php _e( 'password', 'wpan') ?> </th>
			<td> <?php _e( 'Desired password', 'wpan') ?> </td>
		</tr>

		<tr>
			<th scope="row"> <?php _e( 'blogpath', 'wpan') ?> </th>
			<td> <?php _e( 'URL/blog slug', 'wpan') ?> </td>
		</tr>

		<tr>
			<th scope="row"> <?php _e( 'blogtitle', 'wpan') ?> </th>
			<td> <?php _e( 'Title for the user\'s blog', 'wpan') ?> </td>
		</tr>

	</table>
</div>

