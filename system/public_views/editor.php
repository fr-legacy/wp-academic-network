<?php
$action = isset($action) ? $action : '';
$title = isset($title) ? $title : '';
$content = isset($content) ? $content : '';
?>

<?php do_action('teachblog_editor_before_form') ?>
	<form action="<?php esc_attr_e(Teachblog_Form::post_url(array('id'))) ?>" method="post">
		<div class="teachblog editor">
			<?php if (isset($id)): ?>
				<input type="hidden" name="id" value="<?php esc_attr_e($id) ?>" />
			<?php endif ?>

			<?php do_action('teachblog_editor_before_notices') ?>
			<?php if (isset($notices) and is_array($notices) and count($notices) >= 1): ?>
			<div class="section notices">
				<?php foreach ($notices as $type => $items): ?>
					<div class="<?php esc_attr_e($type) ?>">
						<?php foreach ($items as $message): ?>
							<p> <?php esc_html_e($message) ?> </p>
						<?php endforeach ?>
					</div>
				<?php endforeach ?>
			</div>
			<?php endif ?>

			<?php wp_nonce_field('teachblog_front_editor', 'teachblog_check') ?>

			<?php do_action('teachblog_editor_before_status') ?>
			<div class="section status">
				<div class="<?php esc_attr_e($status[0]) ?>"> <?php esc_html_e($status[1]) ?> </div>
				<?php do_action('teachblog_editor_beside_status') ?>
			</div>

			<?php do_action('teachblog_editor_before_title') ?>
			<div class="section title">
				<label> <?php _e('Title', TEACHBLOG_I18N) ?> </label>
				<input type="text" name="title" value="<?php esc_attr_e(isset($title) ? $title : '') ?>"/>
				<?php do_action('teachblog_editor_beside_title') ?>
			</div>

			<?php do_action('teachblog_editor_before_blog_selector') ?>
			<?php if (count($assignable_blogs) >= 2): ?>
			<div class="section assignable">
				<label> <?php _e('Which blog should this post belong to?', TEACHBLOG_I18N) ?> </label>
				<select name="assign_to">
					<?php foreach ($assignable_blogs as $blog_id => $blog_name): ?>
						<option value="<?php esc_attr_e($blog_id) ?>"> <?php esc_html_e($blog_name) ?> </option>
					<?php endforeach ?>
				</select>
				<?php do_action('teachblog_editor_beside_blog_selector') ?>
			</div>
			<?php endif ?>

			<?php do_action('teachblog_editor_before_editor') ?>
			<div class="section content">
				<label> <?php _e('Content', TEACHBLOG_I18N) ?> </label>
				<?php wp_editor(isset($content) ? $content : '', 'teachblog-front-editor') ?>
				<?php do_action('teachblog_editor_beside_editor') ?>
			</div>

			<?php do_action('teachblog_editor_before_publish_controls') ?>
			<div class="section controls">
				<label><?php _e('Publishing options', TEACHBLOG_I18N) ?></label>
				<select name="publish_options">
					<option value="publish"><?php _e('Submit', TEACHBLOG_I18N) ?></option>
					<option value="save_draft"><?php _e('Save as Draft', TEACHBLOG_I18N) ?></option>
					<option value="discard"><?php _e('Discard', TEACHBLOG_I18N) ?></option>
				</select>
				<input type="submit" value="<?php esc_attr_e('Save/Update', TEACHBLOG_I18N) ?>" name="submit-teachblog-post"/>
				<?php do_action('teachblog_editor_beside_publish_controls') ?>
			</div>

		</div>
	</form>
<?php do_action('teachblog_editor_after_form') ?>