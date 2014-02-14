<?php esc_html_e( $data['blog_name'] ) ?> <br />
<div class="row-actions">
	<span>
		<a href="<?php echo esc_url ( $data['blog_url'] ) ?>" title="<?php _e( 'Visit the student&#146;s blog', 'wpan' ) ?>">
			<?php _e( 'Visit blog', 'wpan' ) ?>
		</a>
	</span>
	|
	<span>
		<a href="<?php echo trailingslashit( esc_url ( $data['blog_url'] ) ) ?>wp-admin" title="<?php _e( 'Dashboard', 'wpan' ) ?>">
			<?php _e( 'Visit dashboard', 'wpan' ) ?>
		</a>
	</span>
</div>