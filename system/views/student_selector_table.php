<tr class="form-field">
	<th scope="row" valign="top">
		<label> <?php esc_html_e(isset($label) ? $label : '') ?> </label>
	</th>
	<td>
		<select name="student_blog_owner">
			<option value="unset"> <?php esc_html_e('Unassigned', TEACHBLOG_I18N) ?> </option>
			<?php foreach ($students as $student): ?>
				<?php $selected = (isset($selected) and $selected === $student->ID) ? 'selected="selected"' : '' ?>
				<option value="<?php esc_attr_e($student->ID) ?>" <?php echo $selected ?>> <?php esc_html_e($student->user_login) ?> </option>
			<?php endforeach ?>
		</select>
		<br/>
		<?php if (isset($description)): ?>
			<span class="description"> <?php esc_html_e($description) ?> </span>
		<?php endif ?>
	</td>
</tr>