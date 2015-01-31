<?php
/**
 * The following are normally expected to be passed into this view.
 *
 * @var $id
 * @var $before_widget
 * @var $after_widget
 * @var $before_title
 * @var $after_title
 * @var $title
 * @var $tabs
 * @var $first_panel
 */

$panel_count = 0;

echo $before_widget;

// Add the title wrappers only if we have a title to display
if ( ! empty( $title ) ) echo $before_title . esc_html( $title ) . $after_title;

echo '<div id="' . $id . '" class="wpan_tabbed_student_list_gadget">';

if ( empty( $tabs ) ) {
	_ex( 'Nothing to display', 'empty list', 'wpan' );
	echo '</div> <!-- #' . $id . ' -->';
	return;
}

echo '<ul class="tabs">';

// Form tabs
foreach ( $tabs as $tab ) {
	$tab_header = esc_attr( $tab->header );
	echo '<li> <a href="#' . $id . '-' . $tab_header . '">' . $tab_header . '</a> </li>';
}

echo '</ul> <!-- .tabs -->';

// Form corresponding panels
foreach ( $tabs as $tab ) {
	$panel_count++;
	$tab_header = esc_attr( $tab->header );

	$panel_html    = ( 1 === $panel_count ) ? $first_panel : '';
	$set_populated = ( 1 === $panel_count ) ? 'data-populated="1"' : '';

	echo '<div id="' . $id . '-' . $tab_header . '" ' . $set_populated . '>' . $panel_html . '</div>';
}

echo '</div> <!-- #' . $id . ' -->';

echo $after_widget;