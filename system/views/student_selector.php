<div class="form-field">
	<label> <?php esc_html_e(isset($label) ? $label : '') ?> </label>
	<select name="student_blog_owner">
		<option value="unset"> <?php esc_html_e('Unassigned', 'teachblog') ?> </option>
		<?php foreach ($students as $student): ?>
			<option value="<?php esc_attr_e($student->ID) ?>"> <?php esc_html_e($student->user_login) ?> </option>
		<?php endforeach ?>
	</select>
	<?php if (isset($description)): ?>
		<p> <?php esc_html_e($description) ?> </p>
	<?php endif ?>
</div>