<?php
$selected = isset($selected) ? (array)$selected : array();
?>
<tr class="form-field">
	<th scope="row" valign="top">
		<label> <?php esc_html_e(isset($label) ? $label : '') ?> </label>
	</th>
	<td>
		<select name="student_blog_owner">
			<option value="unset"> <?php esc_html_e('Unassigned', 'teachblog') ?> </option>
			<?php foreach ($students as $student): ?>
				<?php $selected_attr = (in_array($student->ID, $selected)) ? 'selected="selected"' : '' ?>
				<option
					value="<?php esc_attr_e($student->ID) ?>" <?php echo $selected_attr ?>> <?php esc_html_e($student->user_login) ?> </option>
			<?php endforeach ?>
		</select>
		<br/>
		<?php if (isset($description)): ?>
			<span class="description"> <?php esc_html_e($description) ?> </span>
		<?php endif ?>
	</td>
</tr>