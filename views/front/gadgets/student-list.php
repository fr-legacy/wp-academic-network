<?php
/**
 * The following are normally expected to be passed into this view.
 *
 * @var $before_widget
 * @var $after_widget
 * @var $before_title
 * @var $after_title
 * @var $title
 * @var $processing
 */

echo $before_widget;

// Add the title wrappers only if we have a title to display
if ( ! empty( $title ) ) echo $before_title . esc_html( $title ) . $after_title;

echo 'Hello';

echo $after_widget;