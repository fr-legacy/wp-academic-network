<?php
/**
 * Vars expected by this view:
 *
 * @var $bulk_actions
 * @var $filter_actions
 * @var $filter_default
 * @var $current_page
 * @var $num_pages
 * @var $has_search
 * @var $current_search
 */
use WPAN\Helpers\Form;
?>
<div class="tablenav top">
	<?php if ( isset( $bulk_actions ) && ! empty( $bulk_actions ) ): ?>
		<div class="alignleft actions bulkactions">
			<?php echo Form::select( 'action' ,'wpan_apply_actions', $bulk_actions ); ?>
			<input type="submit" name="action_request" value="<?php esc_attr_e( 'Apply', 'wpan' ) ?>" class="button action" />
		</div>
	<?php endif ?>

	<?php if ( isset( $filter_actions ) && ! empty( $filter_actions ) ): ?>
	<div class="alignleft actions">
		<?php foreach ( $filter_actions as $filter_name => $filter_list ): ?>
			<?php echo Form::select( $filter_name, 'wpan_' . $filter_name, $filter_list, $filter_default[$filter_name] ); ?>
		<?php endforeach ?>
		<input type="submit" name="filter_requests" value="<?php esc_attr_e( 'Filter', 'wpan' ) ?>" class="button action" />
	</div>
	<?php endif ?>

	<?php if ( isset( $current_page ) && isset( $num_pages ) ): ?>
		<div class="alignright paginate">
			<label for="wpan_results_page"> <?php _e( 'Page:', 'wpan' ) ?> </label>
			<?php
			$pages = array();
			for ( $i = 1; $i <= $num_pages; $i++) $pages[$i] = $i;
			echo Form::select( 'results_page', 'wpan_results_page', $pages, $current_page );
			?>
			<input type="submit" name="view_page" value="<?php esc_attr_e( 'Jump', 'wpan' ) ?>" class="button action" />
		</div>
	<?php endif ?>

	<?php if ( isset( $has_search ) && $has_search ): ?>
		<div class="alignright search">
			<input name="s" type="search" class="search" value="<?php esc_attr_e( $current_search ) ?>" />
			<input name="<?php echo $current_search ? 'do_search' : 'new_search' ?>" type="submit" class="button-secondary" value="<?php _e( 'Search', 'wpan' ) ?>" />
		</div>
	<?php endif ?>
</div>