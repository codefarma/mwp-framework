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

use MWP\WordPress\AdminPage;

/**
 * Active Record Controller
 */
class ActiveRecordController
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
	 * @var	array
	 */
	public $options = array();
	
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
	 * Default controller configuration
	 *
	 * @return	array
	 */
	public function getDefaultConfig()
	{
		$recordClass = $this->recordClass;
		
		$sequence_col = isset( $recordClass::$sequence_col ) ? $recordClass::$prefix . $recordClass::$sequence_col : NULL;
		$parent_col = isset( $recordClass::$parent_col ) ? $recordClass::$prefix . $recordClass::$parent_col : NULL;
		
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
	 * @return	void
	 */
	public function __construct( $recordClass, $options=array() )
	{
		$this->recordClass = $recordClass;
		$pluginClass = $recordClass::$plugin_class;
		$this->setPlugin( $pluginClass::instance() );
		$this->options = array_merge( apply_filters( 'mwp_controller_default_config', $this->getDefaultConfig(), $recordClass ), $options );		
	}
	
	/**
	 * @var	MWP\WordPress\AdminPage
	 */
	public $adminPage;
	
	/**
	 * Register the controller as an admin page
	 *
	 * @param	array			$options			Admin page options
	 */
	public function registerAdminPage( $options=array() )
	{
		$recordClass = $this->recordClass;
		$adminPage = new AdminPage;
		
		$adminPage->title = isset( $options['title'] ) ? $options['title'] : ( isset( $recordClass::$lang_plural ) ? __( $recordClass::$lang_plural ) : array_pop( explode( '\\', $this->recordClass ) ) . ' Management' );
		$adminPage->menu  = isset( $options['menu'] ) ? $options['menu'] : $adminPage->title;
		$adminPage->slug  = isset( $options['slug'] ) ? $options['slug'] : sanitize_title( str_replace( '\\', '-', $this->recordClass ) );
		$adminPage->capability = isset( $options['capability'] ) ? $options['capability'] : $adminPage->capability;
		$adminPage->icon = isset( $options['icon'] ) ? $options['icon'] : $adminPage->icon;
		$adminPage->position = isset( $options['position'] ) ? $options['position'] : NULL;
		$adminPage->type = isset( $options['type'] ) ? $options['type'] : $adminPage->type;
		$adminPage->parent = isset( $options['parent'] ) ? $options['parent'] : $adminPage->parent;
		
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
		
		return array( 
			'new' => array(
				'title' => __( $recordClass::$lang_create . ' ' . $recordClass::$lang_singular ),
				'params' => array( 'do' => 'new' ),
				'attr' => array( 'class' => 'btn btn-primary' ),
			)
		);
	}
	
	/**
	 * Get the action menu for this controller
	 *
	 * @return	string
	 */
	public function getActionsHtml( $actions=null )
	{
		$actions = $actions ?: $this->getActions();
		
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
		$options     = array_merge( ( isset( $this->options['tableConfig'] ) ? $this->options['tableConfig'] : array() ), $table_options );
		$table_args  = array_merge( array( 'ajax' => true ), ( isset( $options['constructor'] ) ? $options['constructor'] : array() ) );
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
		}
		else
		{
			foreach( $recordClass::$columns as $key => $opts ) {
				if ( is_array( $opts ) ) {
					$table->columns[ $recordClass::$prefix . $key ] = ucwords( str_replace( '_', ' ', $key ) );
				} else {
					$table->columns[ $recordClass::$prefix . $opts ] = ucwords( str_replace( '_', ' ', $opts ) );
				}
			}
			$table->columns['_row_actions'] = __( 'Actions', 'mwp-framework' );
			$table->actionsColumn = '_row_actions';
		}
		
		if ( isset( $options['sortable'] ) ) {
			$table->sortableColumns = $options['sortable'];
		}
		
		if ( isset( $options['searchable'] ) ) {
			$table->searchableColumns = $options['searchable'];
		}
		
		if ( isset( $options['bulk_actions'] ) ) {
			$table->bulkActions = $options['bulk_actions'];
		} else {
			$table->bulkActions = array(
				'delete' => 'Delete'
			);
		}
		
		if ( isset( $options['sort_by'] ) ) {
			$table->sortBy = $options['sort_by'];
		}
		
		if ( isset( $options['sort_order'] ) ) {
			$table->sortOrder = $options['sort_order'];
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
		
		return $table;
	}
	
	/**
	 * Get the controller url
	 *
	 * @param	array			$args			Optional query args
	 * @return	string
	 */
	public function getUrl( $args=array() )
	{
		return add_query_arg( $args, menu_page_url( $this->adminPage->slug, false ) );
	}
	
	/**
	 * Index Page
	 * 
	 * @return	string
	 */
	public function do_index()
	{
		$table = $this->createDisplayTable();
		$where = isset( $this->options['tableConfig']['where'] ) ? $this->options['tableConfig']['where'] : array('1=1');
		
		$table->read_inputs();
		$table->prepare_items( $where );
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/records/table_wrapper', array( 'plugin' => $this->getPlugin(), 'controller' => $this, 'table' => $table ) );
	}
	
	/**
	 * View an active record
	 * 
	 * @param	ActiveRecord			$record				The active record
	 * @return	void
	 */
	public function do_view( $record=NULL )
	{
		$class = $this->recordClass;
		
		if ( ! $record )
		{
			try
			{
				$record = $class::load( $_REQUEST[ 'id' ] );
			}
			catch( \OutOfRangeException $e ) {
 				echo $this->getPlugin()->getTemplateContent( 'component/error', array( 'message' => __( 'The record could not be loaded. Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ), 'mwp-framework' ) ) );
				return;
			}
		}
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/records/view', array( 'title' => $record->viewTitle(), 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record ) );
	}

	/**
	 * Create a new active record
	 * 
	 * @param	ActiveRecord			$record				The active record id
	 * @return	void
	 */
	public function do_new( $record=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		$record = $record ?: new $class;
		
		$form = $class::getForm( $record );
		$save_error = NULL;
		
		if ( $form->isValidSubmission() ) 
		{
			$record->processForm( $form->getValues() );			
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
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/records/create', array( 'title' => $class::createTitle(), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'error' => $save_error ) );
	}
	
	/**
	 * Edit an active record
	 * 
	 * @param	ActiveRecord|NULL			$record				The active record
	 * @return	void
	 */
	public function do_edit( $record=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		
		if ( ! $record ) {
			try
			{
				$record = $class::load( $_REQUEST['id'] );
			}
			catch( \OutOfRangeException $e ) { 
 				echo $this->getPlugin()->getTemplateContent( 'component/error', array( 'message' => __( 'The record could not be loaded. Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ), 'mwp-framework' ) ) );
				return;
			}
		}
		
		$form = $class::getForm( $record );
		$save_error = NULL;
		
		if ( $form->isValidSubmission() ) 
		{
			$record->processForm( $form->getValues() );			
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

		echo $this->getPlugin()->getTemplateContent( 'views/management/records/edit', array( 'title' => $record->editTitle(), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record, 'error' => $save_error ) );
	}

	/**
	 * Delete an active record
	 * 
	 * @param	ActiveRecord|NULL			$record				The active record
	 * @return	void
	 */
	public function do_delete( $record=NULL )
	{
		$controller = $this;
		$class = $this->recordClass;
		
		if ( ! $record ) {
			try
			{
				$record = $class::load( $_REQUEST['id'] );
			}
			catch( \OutOfRangeException $e ) { 
 				echo $this->getPlugin()->getTemplateContent( 'component/error', array( 'message' => __( 'The record could not be loaded. Class: ' . $this->recordClass . ' ' . ', ID: ' . ( (int) $_REQUEST['id'] ), 'mwp-framework' ) ) );
				return;
			}
		}
		
		$form = $record->createDeleteForm();
		
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
	
		echo $this->getPlugin()->getTemplateContent( 'views/management/records/delete', array( 'title' => $record->deleteTitle(), 'form' => $form, 'plugin' => $this->getPlugin(), 'controller' => $this, 'record' => $record ) );
	}	
	
}
