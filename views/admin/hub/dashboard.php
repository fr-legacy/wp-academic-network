<?php
use WPAN\Helpers\Form;
?>
<div class="wrap">
<h2> <?php _e( 'WP Academic Network &mdash; Hub Central', 'wpan' ) ?> </h2>

<?php echo isset( $menu_pages ) ? $menu_pages : '' ?>

<br style="clear:both" />
<form method="post">
<?php
echo Form::select( 'request_type', 'wpan_request_type', array (
	'all' => __( 'All request types', 'wpan' ),
	'teacher' => __( 'Teacher requests', 'wpan' ),
	'student' => __( 'Student requests', 'wpan' )
), 'teacher' );
?>

<?php
echo Form::select( 'request_status' ,'wpan_request_status', array (
	'all' => __( 'All states', 'wpan' ),
	'submitted' => __( 'Submitted', 'wpan' ),
	'on_hold' => __( 'On hold', 'wpan' ),
	'approved' => __( 'Approved', 'wpan' ),
	'rejected' => __( 'Rejected', 'wpan' )
), 'submitted' );
?>

<input type="submit" name="filter_requests" value="<?php esc_attr_e( 'Filter', 'wpan' ) ?>" class="button action" />

</form>
</div>