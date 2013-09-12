<?php
echo $before_widget;

echo $before_title . $title . $after_title;

echo '<ul>';

while ($query->have_posts()) {
	$query->the_post();
	echo '<li> <a href="' . get_permalink(get_the_ID()) . '">' . get_the_title() . '</li>';
}

echo '</ul>';

echo $after_widget;