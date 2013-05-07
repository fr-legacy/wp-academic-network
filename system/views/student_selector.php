<div class="form-field">
	<label> <?php esc_html_e(isset($label) ? $label : '') ?> </label>
	<select name="student_blog_owner">
		<option value="unset"> <?php esc_html_e('Unassigned', TEACHBLOG_I18N) ?> </option>
		<?php foreach ($students as $student): ?>
			<?php if (isset($selected) and $selected === $student->ID) $selected = 'selected="selected"' ?>
			<option value="<?php esc_attr_e($student->ID) ?>" <?php echo $selected ?>> <?php esc_html_e($student->user_login) ?> </option>
		<?php endforeach ?>
	</select>
	<?php if (isset($description)): ?>
		<p> <?php esc_html_e($description) ?> </p>
	<?php endif ?>
</div>