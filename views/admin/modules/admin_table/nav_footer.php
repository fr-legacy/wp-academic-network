<?php
/**
 * Vars expected by this view:
 *
 * @var $bulk_actions
 * @var $filter_actions
 * @var $filter_default
 * @var $current_page
 * @var $num_pages
 */
use WPAN\Helpers\Form;
?>
<div class="tablenav bottom">
	<?php if ( isset( $bulk_actions ) && ! empty( $bulk_actions ) ): ?>
		<div class="alignleft actions bulkactions">
			<?php echo Form::select( 'action2' ,'wpan_apply_actions', $bulk_actions ); ?>
			<input type="submit" name="action_request_2" value="<?php esc_attr_e( 'Apply', 'wpan' ) ?>" class="button action" />
		</div>
	<?php endif ?>

	<?php if ( isset( $current_page ) && isset( $num_pages ) ): ?>
		<div class="alignright paginate">
			<label for="wpan_results_page_2"> <?php _e( 'Page:', 'wpan' ) ?> </label>
			<?php
			$pages = array();
			for ( $i = 1; $i <= $num_pages; $i++) $pages[$i] = $i;
			echo Form::select( 'results_page_2', 'wpan_results_page', $pages, $current_page );
			?>
			<input type="submit" name="view_page_2" value="<?php esc_attr_e( 'Jump', 'wpan' ) ?>" class="button action" />
		</div>
	<?php endif ?>
</div>