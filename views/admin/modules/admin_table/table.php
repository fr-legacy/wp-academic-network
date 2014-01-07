<?php
/**
 * This template expects a number of vars to be in scope.
 *
 * @var $id
 * @var $base
 * @var $checkbox_column
 * @var $columns
 * @var $rows
 */

echo isset( $nav_header ) ? $nav_header : '';

$table_classes = (array) apply_filters( $base . 'table_classes', array( 'wpan', 'widefat', $id ) );
$table_classes = implode( ' ', $table_classes );
echo '<table class="' . esc_attr( $table_classes ) . '">';

// Table headers
foreach ( array( 'thead', 'tfoot' ) as $header ) {
	// Open the table header
	$header_count = isset( $header_count ) ? ++$header_count : 1;
	echo "<$header>";

	// Optionally display a (header) checkbox column
	if ( $checkbox_column ) {
		echo '<th scope="col" class="manage-column column-cb check-column">';
		echo '<input type="checkbox" id="cb-select-all-' . $header_count .'" />';
		echo '</th>';
	}

	// Render all other column headers
	foreach ( $columns as $id => $attributes) {
		$label = $attributes['label'];
		echo '<th scope="col">' . esc_attr( $label ) . '</th>';
	}

	echo "</$header>";

} // endforeach

// Table body
echo '<tbody>';
$row_count = 0;

foreach ( $rows as $row_entry ) {
	// Get the row ID then unset it
	$id = isset( $row_entry['row_id'] ) ? $row_entry['row_id'] : 'unknown';
	unset( $row_entry['row_id'] );

	// Stripe the row?
	$alternate = isset( $alternate ) ? ! $alternate : true;
	$row_class = $alternate ? ' class="alternate"' : '';
	echo '<tr' . $row_class . '>';

	// Optionally display a (header) checkbox column
	if ( $checkbox_column ) {
		echo '<th scope="row" class="check-column">';
		echo '<input type="checkbox" id="cb-select-' . esc_attr( $id ) . '" name="item[]" value="' . esc_attr( $id ) . '"/>';
		echo '</th>';
	}

	// We don't escape each $column entry so as to allow the populating model flexibility to add whatever
	// markup it likes
	foreach ( $row_entry as $name => $column ) {
		$class = ( 'title' === $name ) ? ' class="column-title"' : '';
		echo '<td' . $class . '>' . $column . '</td>';
	}

	// Increment the row count
	$row_count++;
}

// Did we have anything to display?
if ( 0 === $row_count) {
	$columns = count( $columns );
	if ( $checkbox_column ) $columns++;

	echo '<tr class="alternate">'
		. '<td colspan="' . esc_attr( $columns ) . '">'
		. '<strong>' . _x( 'No data to display', 'Empty admin table', 'wpan' ) . '</strong>'
		. '<div class="row-actions always-show">' . _x( 'Consider revising your search criteria or filter parameters.', 'Empty admin table', 'wpan' )
		. '</div </tr>';
}

// Close off
echo '</tbody>';
echo '</table>';

echo isset( $nav_footer ) ? $nav_footer : '';