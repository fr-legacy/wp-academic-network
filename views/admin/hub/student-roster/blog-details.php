<?php
/**
 * @var $blog_id
 */

$details = get_blog_details( $blog_id );

// It's possible they won't have a blog
if ( false === $details ) {
	__( 'No blog detected for this user', 'wpan' );
	return;
}
?>

<h4>
	<?php esc_html_e( $details->blogname ) ?>
	<span class="unbold blog_created">
		<?php
		$date = new DateTime( $details->registered );
		printf( esc_html( 'registered %s', 'wpan' ), $date->format( get_option( 'date_format' ) ) );
		?>
	</span>
</h4>

<div class="row-actions">
	<span>
		<?php $url = esc_url( $details->domain . $details->path ) ?>
		<a title="<?php esc_attr_e( 'Visit this blog', 'wpan' ) ?>" href="<?php echo $url ?>"><?php _e( 'Visit blog', 'wpan' ) ?></a>
	</span>
</div>
