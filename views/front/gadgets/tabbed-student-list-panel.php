<?php
/**
 * @var array $students
 * @var bool  $admin_user
 */

echo '<ul>';

foreach ( $students as $student ) {
	echo '<li>';

	$url = esc_url( $student->blog->details->siteurl );
	$blog = esc_html( $student->blog->details->blogname );
	$user = esc_html( $student->user->display_name );

	$blog_details = $student->blog->supervised || $admin_user
		? '&rarr; <a href="' . $url . '" target="_blank">' . $blog . '</a>'
		: '<span class="unapproved">' . __( '(blog is unavailable/unapproved)', 'wpan' ) . '</span>';

	echo "$user $blog_details";
	echo '</li>';
}

echo '</ul>';