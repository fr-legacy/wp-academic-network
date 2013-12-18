<?php
namespace WPAN\Hub;
use WPAN\Requests\Student;
use WPAN\Requests\Teacher;
use WPAN\Requests\Manager as Request_Manager;
use WPAN\View;


/**
 * Admin controller for requests being managed via the Hub.
 *
 * @package WPAN\Hub
 */
class Requests {
	protected $type_views = array();
	protected $current_type_view = '';
	protected $state_views = array();
	protected $current_state_view = '';
	protected $num_pages = 1;
	protected $current_page = 1;
	protected $results_per_page = 16;
	protected $request_objects = array();

	/**
	 * The current action being processed.
	 *
	 * @var string
	 */
	protected $current_action = '';

	/**
	 * List of items to act on.
	 *
	 * @var array
	 */
	protected $current_items = array();

	/**
	 * Used to contain any errors found during processing.
	 *
	 * @var array
	 */
	protected $errors = array();


	public static function controller() {
		$controller = new self();
		return $controller->get_output();
	}

	public function __construct() {
		$this->settings();
		$this->listen();
		$this->process();
		$this->setup_view();
	}

	protected function settings() {
		$this->results_per_page = apply_filters( 'wpan_requests_manager_results_per_page', $this->results_per_page );
	}

	/**
	 * Detect if a valid request action has been triggered (such as approving, placing on hold, etc).
	 */
	protected function listen() {
		// Has an item ID(s) been passed and is the nonce check satisfied?
		if ( ! isset( $_REQUEST['item'] ) || ! wp_verify_nonce( $_REQUEST['wpan_do_action'], 'wpan_request_action') ) return;

		// What action to look at (confirm we have an action)
		if ( isset( $_REQUEST['action_2'] ) && isset( $_REQUEST['action_request_2'] ) ) $action = $_REQUEST['action_2'];
		elseif ( isset( $_REQUEST['action'] ) ) $action = $_REQUEST['action'];

		if ( ! isset( $action ) ) {
			$this->errors[] = __( 'Ambiguous request: no action provided!', 'wpan' );
			return;
		}

		// Does the user have permission to do this?
		if ( ! current_user_can( 'wpan_handle_requests' ) ) {
			$this->errors[] = __( 'You do not have the necessary permissions to perform this action.', 'wpan' );
			return;
		}

		$this->current_action = $action;
		$this->current_items = (array) $_REQUEST['item'];
	}

	/**
	 * Handle any actions detected by listen().
	 */
	protected function process() {
		if ( empty( $this->current_action ) || empty( $this->current_items ) ) return;
		$request_manager = Request_Manager::object();

		foreach ( $this->current_items as $item_id ) {
			switch ( $this->current_action ) {
				case 'approve': $request_manager->change_status( $item_id, $request_manager::STATUS_APPROVED ); break;
				case 'hold': $request_manager->change_status( $item_id, $request_manager::STATUS_ON_HOLD ); break;
				case 'reject': $request_manager->change_status( $item_id, $request_manager::STATUS_REJECTED ); break;
				case 'fulfill': $request_manager->trigger_fulfillment( $item_id ); break;
				default: do_action( 'wpan_process_unknown_request_status_change', $item_id, $this->current_action ); break;
			}
		}
	}

	/**
	 * Load the appropriate data for this view.
	 */
	protected function setup_view() {
		$this->which_request_type();
		$this->which_report_state();
		$this->determine_pagination();
		$this->load_requests();
	}

	/**
	 * Does the user wish to view teacher or student reports, or both?
	 */
	protected function which_request_type() {
		$this->type_views = array(
			Request_Manager::TYPE_ALL => __( 'View all', 'wpan' ),
			Teacher::TYPE => __( 'Teacher requests', 'wpan' ),
			Student::TYPE => __( 'Student requests' ,'wpan' )
		);

		$this->current_type_view = Teacher::TYPE;
		if ( isset( $_GET['type'] ) && isset( $this->type_views[$_GET['type']] ) )
			$this->current_type_view = $_GET['type'];
	}

	/**
	 * Does the user wish to review only requests that have been submitted, approved, are
	 * on hold, etc?
	 */
	protected function which_report_state() {
		$this->state_views = array(
			Request_Manager::STATUS_ALL => __( 'All states', 'wpan' ),
			Request_Manager::STATUS_SUBMITTED => __( 'Submitted', 'wpan' ),
			Request_Manager::STATUS_ON_HOLD => __( 'On hold' ,'wpan' ),
			Request_Manager::STATUS_APPROVED => __( 'Approved' ,'wpan' ),
			Request_Manager::STATUS_REJECTED => __( 'Rejected' ,'wpan' )
		);

		$this->current_state_view = Request_Manager::STATUS_SUBMITTED;
		if ( isset( $_GET['state'] ) && isset( $this->state_views[$_GET['state']] ) )
			$this->current_state_view = $_GET['state'];
	}

	/**
	 * Figures out the pagination scheme in relation to the current request.
	 */
	protected function determine_pagination() {
		$manager = Request_Manager::object();
		$count = $manager->count_requests( $this->current_type_view, $this->current_state_view );
		$this->num_pages = (int) ceil( $count / $this->results_per_page );

		// Assume page 1 unless a page param been set ...
		$requested_page = 1;
		if ( isset( $_REQUEST['view_page'] ) && isset( $_REQUEST['results_page'] ) ) $requested_page = $_REQUEST['results_page'];
		if ( isset( $_REQUEST['view_page_2'] ) && isset( $_REQUEST['results_page_2'] ) ) $requested_page = $_REQUEST['results_page_2'];

		// Use the requested page but ensure we stay within logical bounds
		$this->current_page = absint( $requested_page );
		if ( $this->current_page > $this->num_pages ) $this->current_page = $this->num_pages;
		if ( $this->current_page < 1 ) $this->current_page = 1;
		if ( 0 === $this->num_pages ) $this->num_pages = 1;
	}

	/**
	 * Load requests meeting the determined criteria.
	 */
	protected function load_requests() {
		$manager = Request_Manager::object();
		$requests = (array) $manager->get_requests( $this->current_type_view, $this->current_state_view, $this->results_per_page, $this->current_page );
		$this->format_requests( $requests );
	}

	/**
	 * Accepts an array of request objects and forms them
	 * @param array $requests
	 */
	protected function format_requests( array $requests ) {
		$this->request_objects = $requests;
	}

	/**
	 * Return a view object containing our output.
	 *
	 * @return View
	 */
	public function get_output() {
		return View::admin( 'hub/requests', array(
			'type_views' => $this->type_views,
			'current_type_view' => $this->current_type_view,
			'state_views' => $this->state_views,
			'current_state_view' => $this->current_state_view,
			'num_pages' => $this->num_pages,
			'current_page' => $this->current_page,
			'requests' => $this->request_objects
		) );
	}
}