<?php foreach ( $tags as $tag ): ?>
	<span class="student-tag"
	      data-user_id="<?php echo esc_attr( $data['student_id'] ) ?>"
	      data-tag="<?php echo esc_attr( $tag ) ?>">
	          <?php echo esc_html( $tag ) ?>
	          <span class="remove"> <?php _ex( 'X', 'remove-tag', 'wpan' ) ?> </span>
	</span>
<?php endforeach ?>
