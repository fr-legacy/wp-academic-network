<div class="wrap wpan">
	<h2> <?php _e( 'WP Academic Network', 'wpan' ) ?> <?php echo isset($subtitle) ? '&ndash; ' . esc_html($subtitle) : '' ?> </h2>

	<?php echo isset( $menu_pages ) ? $menu_pages : '' ?>

	<form method="get">
		<?php
		echo isset( $view ) ? $view : '';

		// Maintain page/tab/subtab state
		if ( isset( $_GET['page'] ) ) echo '<input type="hidden" name="page" value="' . esc_attr( $_GET['page'] ) . '" />';
		if ( isset( $_GET['tab'] ) ) echo '<input type="hidden" name="tab" value="' . esc_attr( $_GET['tab'] ) . '" />';
		if ( isset( $_GET['subtab'] ) ) echo '<input type="hidden" name="subtab" value="' . esc_attr( $_GET['subtab'] ) . '" />';
		?>
	</form>
</div>