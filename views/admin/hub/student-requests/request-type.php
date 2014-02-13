<?php
use WPAN\Relationships;

switch ( $request->type ) {
	case Relationships::STUDENT_TEACHER_LINK:
		$type = _x( 'Connect', 'request type', 'wpan' );
		$icon = WPAN_URL . '/resources/icon_add.png';
	break;

	case Relationships::STUDENT_TEACHER_UNLINK:
		$type = _x( 'Disconnect', 'request type', 'wpan' );
		$icon = WPAN_URL . '/resources/icon_remove.png';
	break;

	default:
		$type = esc_html( $request->type );
		$icon = WPAN_URL . '/resources/icon_unknown.png';
	break;
}
?>

<img src="<?php echo esc_url( $icon ) ?>" class="alignleft type_icon" />
<strong> <?php echo $type ?> </strong>