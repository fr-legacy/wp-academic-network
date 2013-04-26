<table class="<?php esc_attr_e($classes) ?>">
	<?php
	// Print the table header/footer sections
	foreach (array('thead', 'tfoot') as $section) {
		echo "<$section>";

		if ($checkbox) echo '<th scope="col"> <input type="checkbox" name="check_all" /> </th>';
		foreach ($columns as $column) echo '<th> '.esc_html($column).' </th>';

		echo "</$section>";
	}
	?>

	<tr>
	<?php
	// Print the rows
	foreach ($data as $id => $columns) {
		if ($checkbox) echo '<th scope="row"> <input type="checkbox" name="check_row" value="'.esc_attr($id).'" /> </th>';

		$count = 0;

		foreach ($columns as $column) {
			$count++;

			if ($count === 1) {
				// @todo insert first column row actions
				$row_actions = '<span class="row-actions"> </span>';
			}
			echo '<td> '.$column.' '.$row_actions.' </td>';
		}
	}
	?>
	</tr>
</table>