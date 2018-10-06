<?php
/**
 * Table Helper
 *
 * Adapted from, Worpress Administration API: WP_List_Table class
 * /wp-admin/includes/class-wp-list-table.php
 *
 * @package WordPress
 * @subpackage List_Table
 * @since 3.1.0
 */

/**
 * Base class for displaying a list of items in an ajaxified HTML table.
 *
 * @since 3.1.0
 * @access private
 */

namespace MWP\Framework\Helpers; 

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WP_Screen' ) ) {
	require_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
}

if ( ! function_exists ( 'convert_to_screen' ) ) {
	require_once ABSPATH . '/wp-admin/includes/template.php';
}

if ( ! function_exists ( 'get_column_headers' ) ) {
	require_once ABSPATH . '/wp-admin/includes/screen.php';
}

use MWP\Framework\Framework;

/**
 * Used to create tables to display and manage active records
 */
class _ActiveRecordTable extends \WP_List_Table
{

	/**
	 * Various information about the current table.
	 *
	 */
	public $_args;

	/**
	 * Various information needed for displaying the pagination.
	 *
	 */
	public $_pagination_args = array();

	/**
	 * The current screen.
	 *
	 */
	public $screen;

	/**
	 * Cached bulk actions.
	 *
	 */
	public $_actions;

	/**
	 * Cached pagination output.
	 *
	 */
	public $_pagination;

	/**
	 * The view switcher modes.
	 *
	 */
	public $modes = array();

	/**
	 * Stores the value returned by ->get_column_info().
	 *
	 */
	public $_column_headers;
	
	/**
	 * @var string
	 */
	public $searchPhrase;
	
	/**
	 * @var string			Active Record Classname
	 */
	public $activeRecordClass;
	
	/**
	 * @var	array  {
	 *     An associative array of the columns to display in the table.
	 *     $columns = [
	 *         'column_name' => 'Name of Column',
	 *     ];
	 * }
	 */
	public $columns = array();
	
	/**
	 * @var array {
	 *     Custom display handler callbacks for column data.
	 *     $handlers = [
	 *         'column_name' => function( $row ) {
	 *             return '<strong>' . $row['column_name'] . '</strong>';
	 *         }
	 *     ];
	 * }			
	 */
	public $handlers = array();
	
	/**
	 * @var array {
	 *     Custom filter callbacks to modify the query.
	 *     $extras = [
	 *         'my_extra' => array(
	 *             'init' => function( $table ) {
	 *                 if ( isset( $_REQUEST['extra_filter'] ) and $_REQUEST['extra_filter'] == 'yes' ) {
	 *                     $table->addFilter( array( 'column_name=%s', 'value to filter' ) );
	 *                 }
	 *             },
	 *             'output' => function( $table ) {
	 *                 echo 'Filter By Custom: <select name="extra_filter"><option value="no">No</option><option value="yes">Yes</option></select>';
	 *             }
	 *         }
	 *     ];
	 * }			
	 */
	public $extras = array();
	
	/**
	 * @var	array  {
	 *     An associative array of available bulk actions.
	 *     $bulkActions = [
	 *         'delete' => 'Delete',
	 *     ];
	 * }
	 */
	public $bulkActions = array();
	
    /**
	 * @var array {
	 *     A list of columns that can be used for sorting.
	 *     $sortableColumns = [
	 *         'column_one' => 'column_one',         // Initially ascending
	 *         'column_two' => ['column_two', true], // Initially descending
	 *     ];
	 * }
     */
	public $sortableColumns = array();
	
    /**
	 * @var array {
	 *     A list of columns that are searchable.
	 *     $searchableColumns = [
	 *         'column_name' => [
	 *             'type' => 'contains' or 'equals' // Search method
	 *             'combine_words' => 'and' or 'or' // Used with 'contains' search
	 *         ]
	 *     ];
	 * }
     */
	public $searchableColumns = array();
	
	/**
	 * @var	int  Number of records to show per page
	 */
	public $perPage = 50;
	
	/**
	 * @var	int	 The current page number
	 */
	public $current_page = 1;
	
	/**
	 * @var	string  Default column to sort by
	 */
	public $sortBy;
	
	/**
	 * @var string	Default sort order
	 */
	public $sortOrder = 'DESC';
	
	/**
	 * @var	string	Where clauses to use as hard filters for table results
	 */
	public $hardFilters = array();
	
	/**
	 * @var	bool
	 */
	public $displayTopNavigation = true;
	
	/**
	 * @var	bool
	 */
	public $displayBottomNavigation = true;
	
	/**
	 * @var	bool
	 */
	public $displayTopHeaders = true;
	
	/**
	 * @var	bool
	 */
	public $displayBottomHeaders = false;
	
	/**
	 * @var	MWP\Framework\Plugin
	 */
	protected $plugin;
	
	/**
	 * @var	string
	 */
	public $tableTemplate = 'views/management/records/table';
	
	/**
	 * @var	string
	 */
	public $rowTemplate = 'views/management/records/table_row';
	
	/**
	 * @var	string
	 */
	public $rowActionsTemplate = 'views/management/records/row_actions';
	
	/**
	 * @var	ActiveRecordController
	 */
	protected $controller;
	
	/**
	 * @var	string
	 */
	public $viewModel = 'mwp-forms-controller';
	
	/**
	 * @var	string
	 */
	public $sequencingColumn;
	
	/**
	 * @var	string
	 */
	public $parentColumn;
	
	/**
	 * @var string
	 */
	public $actionsColumn;
	
	/**
	 * @var	array
	 */
	public $_args_raw = array();
	
	/**
	 * Set the controller
	 */
	public function setController( $controller )
	{
		$this->controller = $controller;
	}
	
	/**
	 * Get the controller
	 */
	public function getController()
	{
		return $this->controller;
	}
	
	/**
	 * Set the plugin
	 */
	public function setPlugin( $plugin )
	{
		$this->plugin = $plugin;
	}
	
	/**
	 * Get the plugin
	 */
	public function getPlugin()
	{
		if ( ! isset( $this->plugin ) ) {
			$recordClass = $this->activeRecordClass;
			$pluginClass = $recordClass::_getPluginClass();
			if ( class_exists( $pluginClass ) and is_subclass_of( $pluginClass, 'MWP\Framework\Plugin' ) ) {
				$this->plugin = $pluginClass::instance();
			}
		}
		
		return $this->plugin;
	}
	
    /**
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     */
    public function __construct( $args=array() )
	{
		if ( isset( $args['recordClass'] ) ) {
			$this->activeRecordClass = $args['recordClass'];
			unset( $args['recordClass'] );
		}
		
		$this->_args_raw = $args;
		
		if ( isset( $args['ajax'] ) and $args['ajax'] ) {
			add_action( 'admin_enqueue_scripts', function() {
				wp_enqueue_script( 'jquery-loading-overlay' );
			});
		}
		
		//Set parent defaults
		parent::__construct( $args );		
    }
	
	/**
	 * Add a hard query filter
	 */
	public function addFilter( $filter )
	{
		if ( is_string( $filter ) ) {
			$filter = array( $filter );
		}
		
		if ( is_array( $filter ) ) {
			$this->hardFilters[] = $filter;
		}
	}
	
	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @return array
	 */
	public function get_column_info() 
	{
		return parent::get_column_info();
	}
	
	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) 
	{
		$recordClass = $this->activeRecordClass;
		
		if ( $this->rowTemplate ) {
			echo $this->getPlugin()->getTemplateContent( $this->rowTemplate, array( 'table' => $this, 'item' => $item ) );
		} else {
			parent::single_row( $item );
		}
	}
	
	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param object $item The current item
	 */
	public function single_row_columns( $item ) {
		return parent::single_row_columns( $item );
	}
	
	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function no_items() {
		_e( 'No ' . ( isset( $this->_args_raw['plural'] ) ? strtolower( $this->_args_raw['plural'] ) : 'items' ) . ' found.' );
	}
	
	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) 
	{
		if ( $which == 'top' ) {
			foreach( $this->extras as $extra ) {
				if ( isset( $extra['output'] ) and is_callable( $extra['output'] ) ) {
					call_user_func( $extra['output'], $this );
				}
			}
		}
	}
	
	/**
	 * Display the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display() 
	{
		if ( $this->tableTemplate ) {
			echo $this->getPlugin()->getTemplateContent( $this->tableTemplate, array( 'table' => $this ) );
		} else {
			parent::display();
		}
	}
	
	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 * @param string $which
	 */
	public function display_tablenav( $which ) {
		return parent::display_tablenav( $which );
	}
	
	/**
	 * @var	array
	 */
	public $tableClasses = array( 'widefat', 'fixed', 'striped', 'active-record-table' );
	
	/**
	 * Get a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	public function get_table_classes() {
		return $this->tableClasses;
	}
	
	/**
	 * Add table css class
	 *
	 * @param	string		$classname			The classname to add
	 * @return	void
	 */
	public function addTableClass( $classname )
	{
		if ( ! in_array( $classname, $this->tableClasses ) ) {
			$this->tableClasses[] = $classname;
		}
	}
	
	/**
	 * Remove a table css class
	 *
	 * @param	string		$classname			The classname to remove
	 * @return	void
	 */
	public function removeTableClass( $classname )
	{
		$tableClasses = array_flip( $this->tableClasses );
		unset( $tableClasses[$classname] );
		$this->tableClasses = array_flip( $tableClasses );
	}
	
	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 4.3.0
	 * @access protected
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	public function handle_row_actions( $item, $column_name, $primary ) 
	{
		$default_row_actions = parent::handle_row_actions( $item, $column_name, $primary );
		
		if ( $this->getController() ) {		
			$button_col = $this->actionsColumn ?: $primary;
			if ( $column_name === $button_col and $this->getController() ) {
				$default_row_actions .= $this->getControllerActionsHTML( $item, $default_row_actions );
			}
		} 
		
		return $default_row_actions;
 	}
	
	/**
	 * Get the row actions for an item
	 *
	 * @param	array		$item 						The item being acted upon
	 * @param	string		$default_row_actions		The default provided core row actions
	 * @return	string
	 */
	public function getControllerActionsHTML( $item, $default_row_actions='' )
	{
		if ( $controller = $this->getController() ) {
			try {
				$recordClass = $this->activeRecordClass;
				$record = $recordClass::load( $item[ $recordClass::_getPrefix() . $recordClass::_getKey() ] );
				return $this->getPlugin()->getTemplateContent( $this->rowActionsTemplate, array( 
					'controller' => $controller, 
					'record' => $record, 
					'table' => $this, 
					'actions' => $record->getControllerActions(), 
					'default_row_actions' => $default_row_actions,
				));
			} catch( \OutOfRangeException $e ) { }
		}
	}
	
	/**
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title() 
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as 
	 * possible. 
	 * 
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 * 
	 * For more detailed insight into how columns are handled, take a look at 
	 * WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name )
	{
		if ( isset( $this->handlers[ $column_name ] ) and is_callable( $this->handlers[ $column_name ] ) )
		{
			return call_user_func( $this->handlers[ $column_name ], $item, $column_name );
		}
		
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}
	
	/**
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	public function column_cb( $item )
	{
		if ( ! empty( $this->bulkActions ) )
		{
			$class = $this->activeRecordClass;
			return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args[ 'singular' ], $item[ $class::_getPrefix() . $class::_getKey() ] );
		}
	}
	
	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="_bulk_action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false The action name or False if no action was selected
	 */
	public function current_action() {
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
			return false;

		if ( isset( $_REQUEST['_bulk_action'] ) && -1 != $_REQUEST['_bulk_action'] )
			return $_REQUEST['_bulk_action'];

		if ( isset( $_REQUEST['_bulk_action2'] ) && -1 != $_REQUEST['_bulk_action2'] )
			return $_REQUEST['_bulk_action2'];

		return false;
	}

	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value 
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 * 
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns()
	{
		$columns = array();
		$class = $this->activeRecordClass;
		$prefix = $class::_getPrefix();
		
		if ( ! empty( $this->bulkActions ) )
		{
			$columns[ 'cb' ] = '<input type="checkbox" />';
		}
		
		if ( ! empty( $this->columns ) )
		{
			$columns = array_merge( $columns, $this->columns );
			return $columns;
		}
		
		foreach( $class::_getColumns() as $key => $column )
		{
			$slug = NULL;
			$title = NULL;
			
			if ( is_array( $column ) ) {
				$slug = $prefix . $key;
				if ( isset( $column[ 'title' ] ) and is_string( $column[ 'title' ] ) ) {
					$title = $column[ 'title' ];
				}
			}
			elseif ( is_string( $column ) ) {
				$slug = $prefix . $column;
			}
			
			if ( ! $title ) {
				$title = str_replace( '_', ' ', $slug );
				$title = ucwords( $title );
			}
			
			$columns[ $slug ] = $title;
		}
		
		return $columns;
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return $this->sortableColumns;
	}
	
	/**
	 * Get searchable columns
	 * 
	 * @return	array
	 */
	public function get_searchable_columns() {
		return $this->searchableColumns;
	}
	
	/**
	 * Get the current page number
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	public function get_pagenum() {
		return $this->current_page;
	}
	
	/**
	 * Display the pagination.
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf( _n( '%s ' . ( strtolower( $this->_args_raw['singular'] ?: 'item' ) ), '%s ' . ( strtolower( $this->_args_raw['plural'] ?: 'items' ) ), $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = false;

 		if ( $current == 1 ) {
			$disable_first = true;
			$disable_prev = true;
 		}
		if ( $current == 2 ) {
			$disable_first = true;
		}
 		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
 		}
		if ( $current == $total_pages - 1 ) {
			$disable_last = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class = ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 * 
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 * 
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 * 
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	public function get_bulk_actions() 
	{
		return $this->bulkActions;
	}
	
	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 * 
	 * @see $this->prepare_items()
	 **************************************************************************/
	public function process_bulk_action() 
	{
		$action = $this->current_action();
		
		if ( isset( $_POST[ $this->_args['singular'] ] ) ) {		
			if ( $action and array_key_exists( $action, $this->bulkActions ) ) {
				$class = $this->activeRecordClass;
				$records = array_filter( 
					array_map( 
						function( $item_id ) use ( $class ) {
							try { return $class::load( $item_id ); }
							catch( \OutOfRangeException $e ) {}
						}, 
						(array) $_POST[ $this->_args['singular'] ] 
					)
				);
				
				/**
				 * Filter the records that are receiving the bulk action and process them
				 *
				 * @param   array    $records       An array of active records from the bulk request
				 * @param   string   $action        The requested bulk action
				 * @param   string   $class         The name of the active record class
				 * @return  array
				 */
				$records = apply_filters( 'mwp_fw_activerecord_bulk', $records, $action, $class );
				$class::processBulkAction( $action, $records );
			}
		}
	}
	
	/**
	 * Read inputs
	 *
	 * This method will read script input parameters to the script and set the state 
	 * of the table accordingly.
	 *
	 * @return	 void
	 */
	public function read_inputs()
	{
		if ( isset( $_REQUEST['orderby'] ) and in_array( $_REQUEST['orderby'], array_map( function( $arr ) { return is_array( $arr ) ? $arr[0] : $arr; }, $this->get_sortable_columns() ) ) ) {
			$this->sortBy = $_REQUEST['orderby'];
		}
		
		if ( isset( $_REQUEST['order'] ) and in_array( strtolower( $_REQUEST['order'] ), array( 'asc', 'desc' ) ) ) {
			$this->sortOrder = $_REQUEST['order'];
		}
		
		$this->current_page = isset( $_REQUEST['paged'] ) ? (int) $_REQUEST['paged'] : ( absint( get_query_var('paged') ) ?: 1 );
		
		foreach( $this->extras as $extra ) {
			if ( isset( $extra['init'] ) and is_callable( $extra['init'] ) ) {
				call_user_func( $extra['init'], $this );
			}
		}
		
		if ( $searchable_columns = $this->get_searchable_columns() ) 
		{
			if ( isset( $_REQUEST['s'] ) and $_REQUEST['s'] ) 
			{
				$this->searchPhrase = $phrase = $_REQUEST['s'];
				$clauses = array();
				$where = array();
				foreach( $searchable_columns as $column_name => $column_config ) 
				{
					$column_config = is_array( $column_config ) ? $column_config : array( 'type' => 'contains', 'combine_words' => 'OR' );
					$type = is_array( $column_config ) and isset( $column_config['type'] ) ? $column_config['type'] : 'contains';
					
					switch( $type ) 
					{
						case 'contains':
						
							if ( isset( $column_config['combine_words'] ) ) {
								$word_clauses = array();
								foreach( explode( ' ', $phrase ) as $word ) {
									$word_clauses[] = 'LOWER(' . $column_name . ') LIKE %s';
									$where[] = '%' . mb_strtolower( $word ) . '%';								
								}
								$clauses[] = '(' . implode( ( strtolower( $column_config['combine_words'] ) == 'or' ? ' OR ' : ' AND ' ), $word_clauses ) . ')';
							} else {
								$clauses[] = 'LOWER(' . $column_name . ') LIKE %s';
								$where[] = '%' . mb_strtolower( $phrase ) . '%';
							}
							break;
							
						case 'equals':
						
							$clauses[] = '$column_name = %s';
							$where[] = $phrase;
							break;
							
					}
				}
				array_unshift( $where, '(' . implode( ') OR (', $clauses ) . ')' );
				$this->hardFilters[] = $where;
			}
		}
	}
	
	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 * 
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	public function prepare_items( $where=array( '1=1' ) ) 
	{
		$class = $this->activeRecordClass;
		
		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$_sortable = array();
		foreach ( $sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			if ( !isset( $data[1] ) ) {
				$data[1] = false;
			}

			$_sortable[$id] = $data;
		}
		
		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $_sortable );
		
		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();
		
		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently 
		 * looking at. We'll need this later, so you should always include it in 
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();
		
		/**
		 * REQUIRED. Build the query and fetch the database results
		 */
		$db = $class::getDb();
		
		$sortBy        = isset( $this->sortBy ) ? $this->sortBy : $class::_getPrefix() . $class::_getKey();
		$sortOrder     = $this->sortOrder;
		$where_filters = array_merge( $this->hardFilters, array( $where ) );
		$compiled      = $class::compileWhereClause( $where_filters );
		$per_page      = $this->perPage;
		$start_at      = $current_page > 0 ? ( $current_page - 1 ) * $per_page : 0;
		$prefix        = $class::_getMultisite() ? $db->prefix : $db->base_prefix;
		$table         = $class::_getTable();
		
		$query          = "SELECT * FROM {$prefix}{$table} WHERE {$compiled['where']}";
		$prepared_query = ! empty( $compiled['params'] ) ? $db->prepare( $query, $compiled['params'] ) : $query;
		
		$total_items   = $db->get_var( str_replace( "SELECT * ", 'SELECT COUNT(*) ', $prepared_query ) );
		$this->items   = $db->get_results( $prepared_query . " ORDER BY {$sortBy} {$sortOrder} LIMIT {$start_at}, {$per_page}", ARRAY_A );
		
		/**
		 * Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->perPage,
			'total_pages' => ceil( $total_items / $per_page ),
			'orderby' => $sortBy,
			'order' => $sortOrder,
			'where' => $where_filters,
		) );
		
		/* If we are on a non-existant page, go to last page in set */
		if ( isset( $this->_pagination_args['total_pages'] ) && $current_page > $this->_pagination_args['total_pages'] ) {
			$this->current_page = $this->_pagination_args['total_pages'];
			$this->prepare_items( $where );
		}
	}

	/**
	 * Get the view model attr
	 */
	public function getViewModelAttr()
	{
		if ( $this->viewModel ) {
			return ' data-view-model="' . $this->viewModel . '"';
		}
		
		return '';
	}
	
	/**
	 * Get the sequencing data bind attribute 
	 * 
	 * @return	string
	 */
	public function getSequencingBindAttr( $func='sequenceableRecords', $options=array() )
	{
		if ( $this->sequencingColumn ) {
			return ' data-bind="' . $func . ': ' . esc_attr( json_encode( array( 
				'class' => $this->activeRecordClass, 
				'column' => $this->sequencingColumn, 
				'parent' => $this->parentColumn,
				'options' => $options ?: null,
			))) . 
			'"';
		}
		
		return '';
	}
	
	/**
	 * Get the table display and return it
	 *
	 * @return	string
	 */
	public function getDisplay()
	{
		ob_start();
		$this->display();
		return ob_get_clean();
	}
	
	/**
	 * Send required variables to JavaScript land
	 *
	 */
	public function _js_vars() {
		$args = array(
			'class'  => $this->activeRecordClass,
			'args' => $this->_pagination_args,
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			)
		);

		//printf( "<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode( $args ) );
	}	
}
