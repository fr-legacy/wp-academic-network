<?php
$action = isset($action) ? $action : '';
$title = isset($title) ? $title : '';
$content = isset($content) ? $content : '';
?>

<?php do_action('teachblog_editor_before_form') ?>
	<form action="<?php esc_attr_e(Teachblog_Form::post_url(array('id'))) ?>" method="post">
		<div class="teachblog editor">

			<?php wp_nonce_field('teachblog_front_editor', 'teachblog_check') ?>

			<?php do_action('teachblog_editor_before_title') ?>
			<div>
				<label> <?php _e('Title', TEACHBLOG_I18N) ?> </label>
				<input type="text" name="title" value="<?php esc_attr_e(isset($title) ? $title : '') ?>"/>
				<?php do_action('teachblog_editor_beside_title') ?>
			</div>

			<?php do_action('teachblog_editor_before_editor') ?>
			<div>
				<label> <?php _e('Content', TEACHBLOG_I18N) ?> </label>
				<?php wp_editor(isset($content) ? $content : '', 'teachblog-front-editor') ?>
				<?php do_action('teachblog_editor_beside_editor') ?>
			</div>

			<?php do_action('teachblog_editor_before_publish_controls') ?>
			<div>
				<label><?php _e('Publishing options', TEACHBLOG_I18N) ?></label>
				<select name="publish_options">
					<option value="publish"><?php _e('Publish', TEACHBLOG_I18N) ?></option>
					<option value="save_draft"><?php _e('Save as Draft', TEACHBLOG_I18N) ?></option>
					<option value="discard"><?php _e('Discard', TEACHBLOG_I18N) ?></option>
				</select>
				<input type="submit" value="<?php esc_attr_e('Save', TEACHBLOG_I18N) ?>" name="Save"/>
				<?php do_action('teachblog_editor_beside_publish_controls') ?>
			</div>

		</div>
	</form>
<?php do_action('teachblog_editor_after_form') ?>