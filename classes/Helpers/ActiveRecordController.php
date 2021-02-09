<?php
/**
 * Plugin Class File
 *
 * Created:   March 2, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.2.4
 */
namespace MWP\Framework\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Pattern\Controller;
use MWP\WordPress\AdminPage;
use MWP\WordPress\PostPage;

/**
 * Active Record Controller
 */
class _ActiveRecordController extends Controller
{
	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
	/**
	 * @var	string
	 */
	public $recordClass;
	
	/**
	 * @var string
	 */
	protected $output_wrapper = 'views/management/records/output_wrapper';
	
	/**
	 * Set Output
	 *
	 * @return	void
	 */
	public function setOutputWrapper( $template )
	{
		$this->output_wrapper = $template;
	}
	
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	public function getOutputWrapper()
	{
		return $this->output_wrapper;
	}
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin;
		return $this;
	}
	
	/**
	 * Initialization (runs only when a controller page is being viewed)
	 *
	 */
	public function init()
	{
		parent::init();
		
		/* Front End */ 
		if ( ! is_admin() ) 
		{
			/* Enqueue styles/functions to support wp list tables */
			add_action( 'wp_enqueue_scripts', function() { 
				wp_enqueue_style( 'list-tables' );
				wp_enqueue_style( 'common' );
				wp_enqueue_script( 'common' );
			});
			
			/* Stop WP from redirecting paged=X requests to the /page/X url */
			remove_filter( 'template_redirect', 'redirect_canonical' );
		}		
	}
	
	/**
	 * Default controller configuration
	 *
	 * @return	array
	 */
	public function getDefaultConfig()
	{
		$recordClass = $this->recordClass;
		$prefix = $recordClass::_getPrefix();
		
		$sequence = $recordClass::_getSequenceCol();
		$parent = $recordClass::_getParentCol();
		
		$sequence_col = isset( $sequence ) ? $prefix . $sequence : NULL;
		$parent_col = isset( $parent ) ? $prefix . $parent : NULL;
		
		return array(
			'tableConfig' => array(
				'sequencingColumn' => $sequence_col,
				'parentColumn' => $parent_col,
			),
		);
	}
	
	/**
	 * Constructor
	 *
	 * @param	string		$recordClass			The active record class
	 * @param	array		$options				Optional configuration options
	 * @throws 	ErrorException
	 * @return	void
	 */
	protected function constructed()
	{
		if ( ! isset( $this->config['recordClass'] ) ) {
			throw new \ErrorException( 'Active Record Controllers must have a valid recordClass specified.' );
		}
		
		$this->recordClass = $recordClass = $this->config['recordClass'];
		$pluginClass = $recordClass::_getPluginClass();
		$this->setPlugin( $pluginClass::instance() );
		$this->config = apply_filters( 'mwp_active_record_controller_config', array_replace_recursive( $this->getDefaultConfig(), $this->config ), $recordClass );
	}
	
	/**
	 * Register the controller as an admin page
	 *
	 * @param	array			$options			Admin page options
	 * @return	AdminPage
	 */
	public function registerAdminPage( $options=array() )
	{
		$recordClass = $this->recordClass;
		$adminPage = new AdminPage;
		$adminPage->slug = isset( $options['slug'] ) ? $options['slug'] : sanitize_title( str_replace( '\\', '-', $this->recordClass ) );

		// Automatically generate a custom capability and give default access to admins
		if ( ! isset( $options['capability'] ) ) {
			$options['capability'] = 'admin_' . $adminPage->slug;
			$role = get_role('administrator');
			if ( ! $role->has_cap( $options['capability'] ) ) {
				$role->add_cap( $options['capability'] );
			}
		}
		
		$adminPage->title = isset( $options['title'] ) ? $options['title'] : ( isset( $recordClass::$lang_plural ) ? __( $recordClass::$lang_plural ) : array_pop( explode( '\\', $this->recordClass ) ) . ' Management' );
		$adminPage->menu  = isset( $options['menu'] ) ? $options['menu'] : $adminPage->title;
		$adminPage->capability = isset( $options['capability'] ) ? $options['capability'] : $adminPage->capability;
		$adminPage->icon = isset( $options['icon'] ) ? $options['icon'] : $adminPage->icon;
		$adminPage->position = isset( $options['position'] ) ? $options['position'] : NULL;
		$adminPage->type = isset( $options['type'] ) ? $options['type'] : $adminPage->type;
		$adminPage->parent = isset( $options['parent'] ) ? $options['parent'] : $adminPage->parent;
		$adminPage->menu_submenu = isset( $options['menu_submenu'] ) ? $options['menu_submenu'] : null;
		$adminPage->for = isset( $options['for'] ) ? $options['for'] : $adminPage->for;
		
		$adminPage->applyToObject( $this, array() );
		
		$this->adminPage = $adminPage;
		return $this->adminPage;
	}
	
	/**
	 * Get action buttons
	 *
	 * @return	array
	 */
	public function getActions()
	{
		$recordClass = $this->recordClass;
		
		$actions = array( 
			'new' => array(
				'title' => __( $recordClass::$lang_create . ' ' . $recordClass::$lang_singular ),
				'params' => array( 'do' => 'new' ),
			)
		);
		
		if ( isset( $this->config['getActions'] ) and is_callable( $this->config['getActions'] ) ) {
			$actions = call_user_func( $this->config['getActions'], $actions, $this );
		}
		
		return is_array( $actions ) ? $actions : array();
	}
	
	/**
	 * Get the action menu for this controller
	 *
	 * @return	string
	 */
	public function getActionsHtml( $actions=null )
	{
		$actions = $actions !== NULL ? $actions : $this->getActions();
		
		return $this->getPlugin()->getTemplateContent( 'views/management/records/table_actions', array( 'plugin' => $this->getPlugin(), 'controller' => $this, 'actions' => $actions ) );
	}
	
	/**
	 * Get the active record display table
	 *
	 * @param	array			$table_options					Table options that override default configuration
	 * @return	MWP\Framework\Helpers\ActiveRecordTable
	 */
	public function createDisplayTable( $table_options=array() )
	{
		$options     = array_replace( ( isset( $this->config['tableConfig'] ) ? $this->config['tableConfig'] : array() ), $table_options );
		$table_args  = array_replace( array( 'ajax' => false ), ( isset( $options['constructor'] ) ? $options['constructor'] : array() ) );
		$recordClass = $this->recordClass;
		$table       = $recordClass::createDisplayTable( $table_args );
		$plugin      = $this->getPlugin();
		$controller  = $this;
		
		$table->setController( $controller );
		
		if ( isset( $options['viewModel'] ) ) {
			$table->viewModel = $options['viewModel'];
		}
		
		if ( isset( $options['columns'] ) ) {
			$table->columns = $options['columns'];
			if ( isset( $options['actionsColumn'] ) ) {
				$table->actionsColumn = $options['actionsColumn'];
			} else {
				$table->columns['_row_actions'] = __( 'Actions', 'mwp-framework' );
				$table->actionsColumn = '_row_actions';				
			}
		}
		else
		{
			$prefix = $recordClass::_getPrefix();
			foreach( $recordClass::_getColumns() as $key => $opts ) {
				if ( is_array( $opts ) ) {
					$table->columns[ $prefix . $key ] = ucwords( str_replace( '_', ' ', $key ) );
				} else {
					$table->columns[ $prefix . $opts ] = ucwords( str_replace( '_', ' ', $opts ) );
				}
			}
			$table->columns['_row_actions'] = __( 'Actions', 'mwp-framework' );
			$table->actionsColumn = '_row_actions';
		}
		
		if ( isset( $options['sortableColumns'] ) ) {
			$table->sortableColumns = $options['sortableColumns'];
		}
		
		if ( isset( $options['sortable'] ) ) {
			$table->sortableColumns = $options['sortable'];
		}
		
		if ( isset( $options['searchableColumns'] ) ) {
			$table->searchableColumns = $options['searchableColumns'];
		}
		
		if ( isset( $options['searchable'] ) ) {
			$table->searchableColumns = $options['searchable'];
		}

		if ( isset( $options['placeholder'] ) ) {
			$table->searchPlaceholder = $options['placeholder'];
		}
		
		if ( isset( $options['extras'] ) ) {
			$table->extras = $options['extras'];
		}
		
		if ( isset( $options['bulkActions'] ) ) {
			$table->bulkActions = $options['bulkActions'];
		} else {
			$table->bulkActions = array(
				'delete' => 'Delete'
			);
		}
		
		if ( isset( $options['orderby'] ) ) {
			$table->sortBy = $options['orderby'];
		}
		
		if ( isset( $options['order'] ) ) {
			$table->sortOrder = $options['order'];
		}
		
		if ( isset( $options['sort_by'] ) ) {
			$table->sortBy = $options['sort_by'];
		}
		
		if ( isset( $options['sort_order'] ) ) {
			$table->sortOrder = $options['sort_order'];
		}
		
		if ( isset( $options['sortBy'] ) ) {
			$table->sortBy = $options['sortBy'];
		}
		
		if ( isset( $options['sortOrder'] ) ) {
			$table->sortOrder = $options['sortOrder'];
		}
		
		if ( isset( $options['perPage'] ) ) {
			$table->perPage = $options['perPage'];
		}
		
		if ( isset( $options['sequencingColumn'] ) ) {
			$table->sequencingColumn = $options['sequencingColumn'];
			if ( isset( $options['parentColumn'] ) ) {
				$table->parentColumn = $options['parentColumn'];
			}
			$table->sortBy = $options['sequencingColumn'];
			$table->sortOrder = 'ASC';
		}
		
		if ( isset( $options['handlers'] ) ) {
			$table->handlers = array_merge( $table->handlers, $options['handlers'] );
		}
		
		/** Templates **/
		if ( isset( $options['tableTemplate'] ) ) {
			$table->tableTemplate = $options['tableTemplate'];
		}

		if ( isset( $options['rowTemplate'] ) ) {
			$table->rowTemplate = $options['rowTemplate'];
		}

		if ( isset( $options['rowActionsTemplate'] ) ) {
			$table->rowActionsTemplate = $options['rowActionsTemplate'];
		}
		
		if ( isset( $options['hardFilters'] ) and is_array( $options['hardFilters'] ) ) {
			foreach( $options['hardFilters'] as $hardFilter ) {
				$table->hardFilters[] = $hardFilter;
			}
		}
		
		if ( isset( $options['displayTopNavigation'] ) ) {
			$table->displayTopNavigation = $options['displayTopNavigation'];
		}
		
		if ( isset( $options['displayBottomNavigation'] ) ) {
			$table->displayBottomNavigation = $options['displayBottomNavigation'];
		}
		
		if ( isset( $options['displayTopHeaders'] ) ) {
			$table->displayTopNavigation = $options['displayTopHeaders'];
		}
		
		if ( isset( $options['displayBottomHeaders'] ) ) {
			$table->displayBottomHeaders = $options['displayBottomHeaders'];
		}
		
		
		return $table;
	}
	
	/**
	 * Index Page
	 * 
	 * @return	string
	 */
	public function do_index()
	{
		$table = $this->createDisplayTable();
		$where = isset( $this->config['tableConfig']['default_where'] ) ? $this->config['tableConfig']['default_where'] : array('1=1');
		
		$table->read_inputs();
		$table->prepare_items( $where );
		
		$output = $this->getPlugin()->getTemplateContent( 'views/management/records/index', array( 'plugin' => $this->getPlugin(), 'controller' => $this, 'table' => $table ) );
		
		echo $this->wrap( $this->adminPage->title, $output, [ 'classes' => 'index', 'record' => NULL ] );
	}
	
	/**
	 * View an active record
	 * 
	 * @param	ActiveRecord			$record				The active record, or NULL to load by request param
	 * @return	void
	 */
	public function do_view( $record=NULL )
	{
		$class = $this->recordClass;
		
		if ( ! $record ) {
			try {
				$record = $class::load( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : 0 );
			}
			catch( \OutOfRangeException $e ) {
 				echo $this->error( __( 'The record could not be loaded.', 'mwp-framework' ) . ' Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ) );
				return;
			}
		}
		
		$output = $this->getPlugin()->getTemplateContent( 'views/management/records/view', array( 'title' => $record->_getViewTitle(), 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record ) );
		
		echo $this->wrap( $record->_getViewTitle(), $output, [ 'classes' => 'view', 'record' => $record ] );
	}

	/**
	 * Create a new active record
	 * 
	 * @param	ActiveRecord			$record				The new active record, or NULL to auto create
	 * @return	void
	 */
	public function do_new( $record=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		$record = $record ?: new $class;
		
		$form = $record->getForm( 'edit' );
		$save_error = NULL;
		
		if ( $form->isValidSubmission() ) 
		{
			$record->processForm( $form->getValues(), 'edit' );
			$result = $record->save();
			
			if ( ! is_wp_error( $result ) ) {
				$form->processComplete( function() use ( $controller, $record ) {
					wp_redirect( $controller->getUrl() );
					exit;
				});
			} else {
				$save_error = $result;
			}
		}
		
		$output = $this->getPlugin()->getTemplateContent( 'views/management/records/create', array( 'title' => $class::_getCreateTitle(), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'error' => $save_error ) );
		
		echo $this->wrap( $class::_getCreateTitle(), $output, [ 'classes' => 'create', 'record' => $record ] );
	}
	
	/**
	 * Edit an active record
	 * 
	 * @param	ActiveRecord			$record				The active record, or NULL to load by request param
	 * @param	string					$type				The type of edit to build and perform
	 * @return	void
	 */
	public function do_edit( $record=NULL, $type=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		
		if ( ! $type ) {
			$type = isset( $_REQUEST['edit'] ) ? $_REQUEST['edit'] : 'edit';
		}
		
		if ( ! $record ) {
			try	{
				$record = $class::load( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : 0 );
			}
			catch( \OutOfRangeException $e ) { 
 				echo $this->error( __( 'The record could not be loaded.', 'mwp-framework' ) . ' Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ) );
				return;
			}
		}
		
		$form = $record->getForm( $type );
		$save_error = NULL;
		
		if ( $form->isValidSubmission() ) 
		{
			$record->processForm( $form->getValues(), $type );			
			$result = $record->save();
			
			if ( ! is_wp_error( $result ) ) {
				$form->processComplete( function() use ( $controller ) {
					wp_redirect( $controller->getUrl() );
					exit;
				});	
			} else {
				$save_error = $result;
			}
		}

		$output = $this->getPlugin()->getTemplateContent( 'views/management/records/edit', array( 'title' => $record->_getEditTitle( $type ), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record, 'error' => $save_error ) );
		
		echo $this->wrap( $record->_getEditTitle( $type ), $output, [ 'classes' => $type, 'record' => $record ] );
	}

	/**
	 * Delete an active record
	 * 
	 * @param	ActiveRecord			$record				The active record, or NULL to load by request param
	 * @return	void
	 */
	public function do_delete( $record=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		
		if ( ! $record ) {
			try	{
				$record = $class::load( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : 0 );
			}
			catch( \OutOfRangeException $e ) { 
 				echo $this->error( __( 'The record could not be loaded.', 'mwp-framework' ) . ' Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ) );
				return;
			}
		}
		
		$form = $record->getForm( 'delete' );
		
		if ( $form->isValidSubmission() )
		{
			if ( $form->getForm()->getClickedButton()->getName() === 'confirm' ) {
				$record->delete();
			}
			
			$form->processComplete( function() use ( $controller ) {
				wp_redirect( $controller->getUrl() );
				exit;
			});
		}
	
		$output = $this->getPlugin()->getTemplateContent( 'views/management/records/delete', array( 'title' => $record->_getDeleteTitle(), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record ) );
		
		echo $this->wrap( $record->_getDeleteTitle(), $output, [ 'classes' => 'delete', 'record' => $record ] );
	}
	
	/**
	 * Send wrapped output
	 *
	 * @param	string			$title			The output title
	 * @param	string			$output			The output to wrap
	 * @param	array			$params			Additional params to send to template
	 * @return	void
	 */
	public function wrap( $title, $output, $params=[] ) {
		return $this->getPlugin()->getTemplateContent( $this->getOutputWrapper(), array_merge( array(
			'title' => $title,
			'output' => $output,
			'controller' => $this,
		), $params ));
	}
	
	/**
	 * Send error output
	 *
	 * @param	string				$message					The error message
	 * @param	string				$code						The error code
	 * @return	string
	 */
	public function error( $message, $code='' )
	{
 		return $this->getPlugin()->getTemplateContent( 'component/error', array( 
			'message' => $message,
			'code' => $code,
		));
	}
	
}
