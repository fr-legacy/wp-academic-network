<?php
/**
 * Builds the request manager admin page.
 *
 * This view expectes the following to be in scope:
 *
 * @var $type_views
 * @var $current_type_view
 * @var $state_views
 * @var $current_state_view
 * @var $num_pages
 * @var $current_page
 * @var $requests
 */
use WPAN\Helpers\AdminTable;
use WPAN\Requests\Manager;
use WPAN\Requests\Student;
use WPAN\Requests\Teacher;

// Form the action list
$bulk_actions = array(
	'select' => __( 'Bulk action', 'wpan' ),
	'approve' => __( 'Approve', 'wpan' ),
	'hold' => __( 'Place on hold', 'wpan' ),
	'reject' => __( 'Reject', 'wpan' )
);

// Dispense of unsuitable actions
switch ( $current_state_view ) {
	case Manager::STATUS_APPROVED: unset( $bulk_actions['approve'], $bulk_actions['hold'], $bulk_actions['reject'] ); break;
	case Manager::STATUS_ON_HOLD: unset( $bulk_actions['hold'] ); break;
	case Manager::STATUS_REJECTED: unset( $bulk_actions['reject'] ); break;
}

$table = AdminTable::build( 'hub_requests' )
	->add_column( 'title', __( 'Summary', 'wpan' ) )
	->add_column( 'type', __( 'Type', 'wpan' ) )
	->add_column( 'status', __( 'Status', 'wpan' ) )
	->add_column( 'dated', __( 'Dated', 'wpan' ) )
	->set_bulk_actions( $bulk_actions )
	->set_filter_action( 'type', $type_views, $current_type_view )
	->set_filter_action( 'state', $state_views, $current_state_view )
	->set_total_pages( $num_pages )
	->set_page( $current_page );

$type_map = array(
	Teacher::TYPE => __( 'Teacher', 'wpan' ),
	Student::TYPE => __( 'Student', 'wpan' )
);

$state_map = array(
	Manager::STATUS_SUBMITTED => __( 'Submitted', 'wpan' ),
	Manager::STATUS_ON_HOLD => __( 'On hold', 'wpan' ),
	Manager::STATUS_APPROVED => __( 'Approved', 'wpan' ),
	Manager::STATUS_REJECTED => __( 'Rejected', 'wpan' ),
);

$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );

/**
 * Generates action links for each table row.
 *
 * @param $available_actions
 * @param $id
 * @param $request_state
 * @return string
 */
$row_actions = function( $available_actions, $id, $request_state ) {
	// If the status is approved *only*, present a fulfillment option
	if ( Manager::STATUS_APPROVED === $request_state) $available_actions[ __( 'Fulfill now', 'wpan' ) ] = add_query_arg( array( 'action' => 'fulfill' ) );

	foreach ( $available_actions as $description => &$link ) {
		$link = add_query_arg( 'item', $id, $link );
		$link = wp_nonce_url( $link, 'wpan_request_action', 'wpan_do_action' );
	}

	$action_links = '';
	$action_count = 0;

	foreach ( $available_actions as $action => $url ) {
		if ( ++$action_count > 1 ) $action_links .= ' | ';
		$action_links .= '<a href="' . esc_url( $url ) . '">' . $action . '</a> ';
	}

	return $action_links;
};

// Build the action links list
$action_links_list = array();
unset( $bulk_actions['select'] ); // Don't need this one any more

foreach ( $bulk_actions as $possible_action => $description )
	$action_links_list[$description] = add_query_arg( array( 'action' => $possible_action ) );

// Build the request row data
foreach ( $requests as $request ) {
	$identifier = isset( $request->email ) ? $request->email : 'No email address supplied' ; // We may want a different property for students
	$proposal = 'New site'; // Modfiy to suit

	$representation = array();
	$representation['row_id'] = $request->id;
	$representation['title'] = '<strong>' . $identifier . '</strong>'
		.'<div class="row-actions">' . $row_actions( $action_links_list, $request->id, $request->state ) . '</div>';
	$representation['type'] = isset( $type_map[$request->type] ) ? $type_map[$request->type] : __( 'Unknown', 'wpan' );
	$representation['status'] = isset( $state_map[$request->state] ) ? $state_map[$request->state] : __( 'Unknown', 'wpan' );
	$representation['dated'] = $request->created->format( "$date_format $time_format" );
	$table->add_row( $representation );
}

$table->render();
wp_nonce_field( 'wpan_request_action', 'wpan_do_action' );
