<?php
/**
 * @var array $students
 */

echo '<ul>';

foreach ( $students as $student ) {
	echo '<li>';

	esc_html_e( $student->user->display_name );

	echo '</li>';
}

echo '</ul>';