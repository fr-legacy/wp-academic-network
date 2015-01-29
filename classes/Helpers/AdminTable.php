<?php
namespace WPAN\Helpers;


/**
 * Utility class or view model used to generate HTML tables in the admin environment with a quasi-WordPress
 * look and feel.
 *
 * WP's own WP_List_Table class is not used as it is marked as private. So, this class is used instead
 * A) to avoid using an internal WP structure that may change in future releases and B) to better fit
 * our own paradigm.
 *
 * This class is only concerned with the preparation of a table view, it does not care or add particular
 * constraints as to what is added inside the table and it does not actively manage the table in the same
 * way as, for instance, WP_List_Table.
 *
 * @package WPAN\Helpers
 */
class AdminTable {
	/**
	 * A unique string that can be used to identify this table, used within filter/action hooks.
	 *
	 * @var string
	 */
	protected $id = 'wpan_admin_table';

	/**
	 * If the table should display with an initial checkbox column.
	 * @var bool
	 */
	protected $checkbox_column = true;

	/**
	 * Container for any bulk actions that may be exposed in the table nav header.
	 *
	 * @var array
	 */
	protected $bulk_actions = array();

	/**
	 * Container for any filters that might be set up. This will be an array of arrays, with each
	 * child array representing a particular filter dropdown.
	 *
	 * @var array
	 */
	protected $filter_actions = array();

	/**
	 * Marks the default filter options.
	 *
	 * @var array
	 */
	protected $filter_defaults = array();

	/**
	 * If a search field should be added.
	 *
	 * @var bool
	 */
	protected $has_search = false;

	/**
	 * The current search keyword(s) - maybe empty.
	 *
	 * @var string
	 */
	protected $current_search = '';

	/**
	 * Used to provide an initial setting for pagination controls.
	 *
	 * @var int
	 */
	protected $current_page = 1;

	/**
	 * Used to form pagination controls.
	 *
	 * @var int
	 */
	protected $num_pages = 1;

	/**
	 * The columns we wish to generate. Structured:
	 *
	 * [ (string) id => [ 'label' => (string) column_label ], ... ]
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Container for the actual content used to populate the rows. Should be an array of arrays.
	 *
	 * @var array
	 */
	protected $rows = array();


	/**
	 * Returns a new Admin_Table object. This might be used instead of directly instantiating
	 * the class as a matter of style, as it can be followed immediately with chainable methods.
	 *
	 * @param $id
	 * @return AdminTable
	 */
	public static function build( $id ) {
		return new self( $id );
	}

	/**
	 * Creates a new admin table helper object. The provided $id should be a unique, meaningful,
	 * slug-style string to help identify the table in the context of actions and filters.
	 *
	 * @param string $id
	 */
	public function __construct( $id ) {
		$id = trim( (string) $id );
		if ( ! empty( $id ) ) $this->id = $id;
		$this->base = 'wpan_admin_table_' . $this->id . '_';
		wp_enqueue_script( 'wpan-tables', WPAN_URL . 'resources/admin-table.js', array( 'jquery' ), false, true );
	}

	/**
	 * Adds a column to the table.
	 *
	 * @param $id
	 * @param $label
	 * @return $this
	 */
	public function add_column( $id, $label ) {
		$this->columns[$id] = array( 'label' => $label );
		return $this;
	}

	/**
	 * Removes a previously added column.
	 *
	 * @param $id
	 * @return $this
	 */
	public function remove_column( $id ) {
		if ( isset( $this->columns[$id] ) ) unset( $this->columns[$id] );
		return $this;
	}

	/**
	 * Switches the initial checkbox column on or off.
	 *
	 * @param bool $on_or_off
	 * @return $this
	 */
	public function use_checkbox( $on_or_off ) {
		$this->checkbox_column = (bool) $on_or_off;
		return $this;
	}

	/**
	 * Allows a basic search field to be added (or removed).
	 *
	 * @param  bool $on_off (defaults to false)
	 * @return $this
	 */
	public function has_search( $on_off = false ) {
		$this->has_search = (bool) $on_off;
		return $this;
	}

	/**
	 * Sets the current search keyword(s).
	 *
	 * @param  string $string
	 * @return $this
	 */
	public function set_search_terms( $string ) {
		$this->current_search = $string;
		return $this;
	}

	/**
	 * Adds a row of data to the table.
	 *
	 * If $row_content includes an element called 'row_id' this will be used as an identifier for the
	 * checkbox column etc. Otherwise each inner array should simply reflect column order.
	 *
	 * @param array $row_content
	 * @return $this
	 */
	public function add_row( array $row_content ) {
		$this->rows[] = $row_content;
		return $this;
	}

	/**
	 * Used to set the bulk actions menu in one operation. $actions should be an associative array
	 * of the name (option value) and label. This will override anything previously added to the
	 * bulk action list.
	 *
	 * @param array $actions
	 * @return $this
	 */
	public function set_bulk_actions( array $actions ) {
		$this->bulk_actions = $actions;
		return $this;
	}

	/**
	 * Adds an item to the bulk action menu.
	 *
	 * @param $name
	 * @param $label
	 * @return $this
	 */
	public function add_bulk_action( $name, $label ) {
		$this->bulk_actions[$name] = $label;
		return $this;
	}

	/**
	 * Removes an entry from the bulk actions list.
	 *
	 * @param $name
	 * @return $this
	 */
	public function remove_bulk_action( $name ) {
		if ( isset( $this->bulk_actions[$name] ) ) unset( $this->bulk_actions[$name] );
		return $this;
	}

	/**
	 * Adds a list of filter options for use in the table nav header. Optionally, a default
	 * choice can be provided for this setting.
	 *
	 * @param $name
	 * @param array $options
	 * @param string $default
	 * @return $this
	 */
	public function set_filter_action( $name, array $options, $default = '' ) {
		$this->filter_actions[$name] = $options;
		$this->filter_defaults[$name] = $default;
		return $this;
	}

	/**
	 * Removes an existing filter action.
	 *
	 * @param $name
	 * @return $this
	 */
	public function remove_filter_action( $name ) {
		if ( isset( $this->filter_actions[$name] ) ) unset( $this->filter_actions[$name] );
		return $this;
	}

	/**
	 * Sets the current page of results being viewed. Used when rendering the pagination controls.
	 *
	 * @param $page
	 * @return $this
	 */
	public function set_page( $page ) {
		$this->current_page = absint( $page );
		return $this;
	}

	/**
	 * Sets the total number of pages of results available to view. Used when rendering the pagination
	 * controls.
	 *
	 * @param $total
	 * @return $this
	 */
	public function set_total_pages( $total ) {
		$this->num_pages = absint( $total );
		return $this;
	}

	/**
	 * Returns the table output as a string.
	 *
	 * @return string
	 */
	public function as_string() {
		ob_start();
		$this->render();
		return ob_get_clean();
	}

	/**
	 * Renders the table.
	 */
	public function render() {
		$this->prerender();

		$nav_params = array(
			'bulk_actions'   => $this->bulk_actions,
			'filter_actions' => $this->filter_actions,
			'filter_default' => $this->filter_defaults,
			'current_page'   => $this->current_page,
			'num_pages'      => $this->num_pages,
			'has_search'     => true,
			'current_search' => $this->current_search
		);

		echo View::admin( 'modules/admin_table/table', array(
			'id' => $this->id,
			'base' => $this->base,
			'checkbox_column' => $this->checkbox_column,
			'columns' => $this->columns,
			'rows' => $this->rows,
			'nav_header' => View::admin( 'modules/admin_table/nav_header' , $nav_params ),
			'nav_footer' => View::admin( 'modules/admin_table/nav_footer' , $nav_params )
		) );
	}

	/**
	 * Affords an opportunity for table properties to be manipulated and changed before
	 * rendering.
	 */
	protected function prerender() {
		$this->checkbox_column = (bool) apply_filters( $this->base . 'checkbox_column', $this->checkbox_column );
		$this->bulk_actions    = (array) apply_filters( $this->base . 'bulk_actions', $this->bulk_actions );
		$this->filter_actions  = (array) apply_filters( $this->base . 'filter_actions', $this->filter_actions );
		$this->columns         = (array) apply_filters( $this->base . 'columns', $this->columns );
		$this->rows            = (array) apply_filters( $this->base . 'rows', $this->rows );
		$this->has_search      = (bool) apply_filters( $this->base . 'has_search', $this->has_search );

		list( $this->current_page, $this->num_pages ) = (array) apply_filters( $this->base . 'pagination', array( $this->current_page, $this->num_pages ) );
	}

	/**
	 * Helper to retrieve the requested page of results.
	 *
	 * @return int
	 */
	public function get_page_num() {
		if ( isset( $_REQUEST['new_search'] ) )
			$page = 1;

		elseif ( isset( $_REQUEST['view_page_2'] ) && isset( $_REQUEST['results_page_2'] ) )
			$page = absint( $_REQUEST['results_page_2'] );

		else if ( isset( $_REQUEST['results_page'] ) )
			$page = absint( $_REQUEST['results_page'] );

		if ( ! isset( $page ) )
			$page = 1;

		return $page;
	}

	/**
	 * Tries to set the value of the pagination controls automatically. Equivalent to calling
	 * set_page() and passing it the result of get_page_num().
	 */
	public function auto_set_page() {
		$this->set_page( $this->get_page_num() );
	}
}