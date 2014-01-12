<?php
/**
 * @var $days
 */

if ( ! is_numeric( $days ) ) {
	echo '<em>' . __( 'Unvalidated', 'wpan' ) . '</em>';
	return;
}

$days = (int) $days;

if ( $days > 1 ) printf( __( '%d days ago', 'wpan' ), $days );
elseif ( 1 === $days ) printf( __( '%d day ago', 'wpan' ), $days );
elseif ( 0 === $days ) _e( 'Today!', 'wpan' );
else __( 'Future date', 'wpan' );