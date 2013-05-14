<div class="teachblog user-post-list">
	<table>
		<thead>
			<tr>
				<th><?php _e('Post Title', 'teachblog') ?></th>
				<th><?php _e('Last Updated', 'teachblog') ?></th>
				<th><?php _e('Current Status', 'teachblog') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (empty($posts)): ?>
			<tr>
				<td colspan="3">
					<?php printf(__('You do not currently have any posts &ndash; <a href="%s">go ahead and create one!</a>', 'teachblog'),
						$shortcode->get_url('teachblog_editor')) ?>
				</td>
			</tr>
		<?php else: foreach ($posts as $post): ?>
			<tr>
				<td>
					<strong> <?php esc_html_e($post->post_title) ?> </strong> <br />
					<span class="actions">
						<a href="<?php esc_attr_e($shortcode->get_url('teachblog_editor', array('id' => $post->ID)) ) ?>"> <?php _e('Edit', 'teachblog') ?> </a>
					</span>
				</td>
				<td> <?php esc_html_e(mysql2date(get_option('date_format'), $post->post_modified)) ?> </td>
				<td> <span class="post state"> <?php esc_html_e($post->post_status) ?> </span> </td>
			</tr>
		<?php endforeach; endif ?>

		</tr>
		</tbody>
	</table>
</div>