<?php
echo $before_widget;

echo $before_title . $title . $after_title;

echo '<ul>';

if (0 === count($blogs)) {
	echo '<li class="empty">' . __('No blogs found', 'teachblog') . '</li>';
}
else foreach ($blogs as $blog) {
	// Try to grab the blog link, but skip to the next one if we encounter an error
	$link = get_term_link((int) $blog->term_id, Teachblog_Student_Content::TEACHBLOG_BLOG_TAXONOMY);
	if (is_wp_error($link)) continue;

	echo '<li class="student_blog_' . esc_attr($blog->term_id) . '">'
		. '<a href="' . $link . '">'
		. '<span class="title">' . esc_html($blog->name) . '</span>'
		. '<span class="count"> (' . esc_html($blog->count) . ') </span> </li>';
}

echo '</ul>';

echo $after_widget;