<?php
namespace WPAN;

/**
 * @var $work_in_progress
 * @var $job_details
 * @var $type
 */

// Security
$origin = uniqid();
$check = wp_create_nonce( $origin . get_current_user_id() . 'WPAN worker' );
$typecheck = hash( 'md5', $type . $check . $origin );

// Calculate num rows left
$total_rows = isset( $job_details['total_rows'] ) ? $job_details['total_rows'] : 1;
$remaining_rows = isset( $job_details['remaining_rows'] ) ? $job_details['remaining_rows'] : 1;
$processed_rows = $total_rows - $remaining_rows;

$progress = ( $total_rows - $remaining_rows ) / $total_rows;
$progress = absint( $progress ) . '%';
?>

<?php if ( ! $work_in_progress ): ?>
	<div class="section_wrapper warning"> <p>
		<strong> <?php _e( 'No roster updates are currently in progress.', 'wpan') ?> </strong>
		<?php _e( 'To upload a new roster file please use the <em>update/import</em> screen.', 'wpan' ) ?>
	</p> </div>
<?php else: ?>

	<p> <?php _e( 'Please note if you navigate away from this page while the realtime processor is running it will automatically stop shortly thereafter. If you need it to run until the current update completes, please keep the page open in your browser.', 'wpan' ) ?> </p>

	<table class="controls" id="worker_controls">
		<tr>
			<th scope="row"> <?php _e( 'Progress', 'wpan' ) ?> </th>
			<td>
				<div class="meterbox">
					<div class="bar" id="worker_progress" style="width: <?php esc_attr_e( $progress ) ?>"></div>
				</div>
				<div class="meter_percent" id="worker_progress_text"> <?php esc_attr_e( $progress ) ?> </div>
			</td>
		</tr>
		<tr>
			<td class="aligncenter">
				<div class="workdots">
					<div class="dot"></div>
					<div class="dot"></div>
					<div class="dot"></div>
					<div class="dot"></div>
				</div>
			</td>
			<td>
				<div class="hidden" id="worker_buttons">
					<button class="button button-primary run_worker" id="run_worker"> <?php _e( 'Run worker', 'wpan' ) ?> </button>
					<button class="button button-secondary stop_worker" id="stop_worker"> <?php _e( 'Stop worker', 'wpan' ) ?> </button>
				</div>

				<div id="worker_advice_js">
					<p> <em> <?php _e( 'If this advice does not disappear then a technical issue may be preventing the realtime processor from working as expected.', 'wpan' ) ?> </em> </p>
				</div>
			</td>
		</tr>
	</table>

	<div id="worker_log" class="hidden">
		<h4> <?php _e( 'Debug log', 'wpan' ) ?> </h4>
		<p> <?php _e( 'Feedback about the update/import operation will be added here.', 'wpan' ) ?> </p>
		<p> <?php _e ( 'This can be useful if errors have occurred or you are having problems during import. The presence of warning messages does not necessarily indicate that there have been failures, however.', 'wpan' ) ?> </p>
		<ul></ul>
	</div>

	<script type="text/javascript" src="<?php echo esc_url( WPAN_URL . 'resources/wpan-roster-worker.js' ) ?>"></script>
	<script type="text/javascript">
		var wpan_worker = {
			<?php if ( isset( $_GET['do'] ) && 'now' === $_GET['do'] ) echo 'immediate: "1",'; ?>
			action: "wpan_roster_update",
			origin: "<?php esc_html_e( $origin ) ?>",
			check: "<?php esc_html_e( $check ) ?>",
			type: "<?php esc_html_e( $type ) ?>",
			typecheck: "<?php esc_html_e( $typecheck ) ?>",
			completion_msg: "<?php _e( '<strong>The update task has now completed.</strong> Please view the <em>current roster</em> to confirm everything is in order.', 'wpan' ) ?>"
		};
	</script>

<?php endif ?>