<div class="tablenav top">
	<div class="alignleft actions">
		<?php
		if (!empty($actions)) {
			echo '<select name="actions-top">';
			echo '<option value="">' . __('Bulk Actions', 'teachblog') . '</option>';

			foreach ($actions as $title => $slug)
				echo '<option value="' . esc_attr($slug) . '">' . esc_html($title) . '</option>';

			echo '</select>';
			echo '<input id="doaction" class="button action main-action" type="submit" value="' . _x('Apply &amp; Update', 'table-actions', 'teachblog') . '" name="">';
		}
		?>

	</div>

</div>

<table class="<?php esc_attr_e($classes) ?>">
	<?php
	// Print the table header/footer sections
	foreach (array('thead', 'tfoot') as $section) {
		echo "<$section>";

		if ($checkbox) echo '<th scope="col" class="check-column"> <input type="checkbox" name="check_all" /> </th>';
		foreach ($columns as $column) echo '<th> ' . esc_html($column) . ' </th>';

		echo "</$section>";
	}

	// Print the rows
	foreach ($data as $id => $columns) {
		echo '<tr>';

		if ($checkbox) echo '<th scope="row" class="check-column"> <input type="checkbox" name="check_row[]" value="' . esc_attr($id) . '" /> </th>';

		$count = 0;

		foreach ($columns as $column) {
			$count++;

			if ($count === 1) {
				// @todo insert first column row actions
				$row_actions = '<span class="row-actions"> </span>';
			}
			echo '<td> ' . $column . ' ' . $row_actions . ' </td>';
		}

		echo '</tr>';
	}
	?>
</table>