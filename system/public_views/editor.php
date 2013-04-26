<div class="teachblog edito">
	<label> <?php _e('Title', TEACHBLOG_I18N) ?> </label>
	<input type="text" name="title" value="<?php esc_attr_e(isset($title) ? $title : '') ?>" />

	<label> <?php _e('Content', TEACHBLOG_I18N) ?> </label>
	<?php wp_editor(isset($content) ? $content : '', 'teachblog-front-editor') ?>

</div>