<?php
/**
 * The following are normally expected to be passed into this view.
 *
 * @var $before_widget
 * @var $after_widget
 * @var $before_title
 * @var $after_title
 * @var $title
 * @var $students
 */

$show_unsupervised = isset( $show_unsupervised ) ? (bool) $show_unsupervised : false;
$count = 0;

echo $before_widget;

// Add the title wrappers only if we have a title to display
if ( ! empty( $title ) ) echo $before_title . esc_html( $title ) . $after_title;

echo '<ul>';

// Iterate through
foreach ( $students as $student ) {
	if ( ! $show_unsupervised && ! $student->blog->supervised ) continue;
	$count++;

	echo '<li>';
	$url = esc_url( $student->blog->details->siteurl );
	$blog = esc_html( $student->blog->details->blogname );
	$user = esc_html( $student->student->display_name );
	echo '<a href="' . $url . '">' . $blog . '</a> ' . $user;
	echo '</li>';
}

if ( 0 === $count )
	echo '<li>' . __( 'No student blogs found.', 'wpan' ) . '</li>';

echo '</ul>';

echo $after_widget;