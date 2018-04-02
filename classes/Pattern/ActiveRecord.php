<?php
/**
 * ActiveRecord Class
 *
 * Created:   December 18, 2016
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.0.1
 */
namespace MWP\Framework\Pattern;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Framework;
use MWP\Framework\Helpers\ActiveRecordController;
use MWP\Framework\Helpers\ActiveRecordTable;

/**
 * An active record design pattern
 *
 */
abstract class ActiveRecord
{
	/**
	 * @var	array		Multitons cache (needs to be defined in subclasses also)
	 */
	protected static $multitons = array();
	
	/**
	 * @var	string		Table name
	 */
	public static $table;
	
	/**
	 * @var	array		Table columns
	 */
	public static $columns = array();
	
	/**
	 * @var	string		Table primary key
	 */
	public static $key;
	
	/**
	 * @var	string		Table column prefix
	 */
	public static $prefix = '';
	
	/**
	 * @var bool		Site specific table? (for multisites)
	 */
	public static $site_specific = FALSE;
	
	/**
	 * @var	string
	 */
	public static $plugin_class = 'MWP\Framework\Framework';
	
	/**
	 * @var	string
	 */
	public static $sequence_col;
	
	/**
	 * @var	string
	 */
	public static $parent_col;

	/**
	 * @var	string
	 */
	public static $lang_singular = 'Record';
	
	/**
	 * @var	string
	 */
	public static $lang_plural = 'Records';
	
	/**
	 * @var	string
	 */
	public static $lang_view = 'View';

	/**
	 * @var	string
	 */
	public static $lang_create = 'Create';

	/**
	 * @var	string
	 */
	public static $lang_edit = 'Edit';
	
	/**
	 * @var	string
	 */
	public static $lang_delete = 'Delete';
	
	/**
	 * @var	string		WP DB Prefix of loaded record
	 */
	public $_wpdb_prefix;
	
	/**
	 * @var	array		Record data
	 */
	protected $_data = array();
	
	/**
	 * Get the 'create record' page title
	 * 
	 * @return	string
	 */
	public static function createTitle()
	{
		return __( static::$lang_create . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'view record' page title
	 * 
	 * @return	string
	 */
	public function viewTitle()
	{
		return __( static::$lang_view . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'edit record' page title
	 * 
	 * @return	string
	 */
	public function editTitle()
	{
		return __( static::$lang_edit . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'delete record' page title
	 * 
	 * @return	string
	 */
	public function deleteTitle()
	{
		return __( static::$lang_delete . ' ' . static::$lang_singular );
	}
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		$pluginClass = static::$plugin_class;
		return $pluginClass::instance();
	}
	
	/**
	 * Property getter
	 *
	 * @param	string		$property		The property to get
	 * @return	mixed
	 */
	public function __get( $property )
	{
		/* Ensure we are getting a defined property */
		if ( in_array( $property, static::$columns ) or array_key_exists( $property, static::$columns ) )
		{
			/* Proceed if we have a value to return */
			if ( array_key_exists( static::$prefix . $property, $this->_data ) )
			{
				/* Retrieve the value */
				$value = $this->_data[ static::$prefix . $property ];
				
				/* Check if there are any optional params assigned to this property */
				if ( array_key_exists( $property, static::$columns ) )
				{
					$options = static::$columns[ $property ];
					
					/* Special format conversion needed? */
					if ( isset( $options[ 'format' ] ) )
					{
						switch( $options[ 'format' ] )
						{
							case 'JSON':
								
								$value = json_decode( $value, true );
								break;
								
							case 'ActiveRecord':
								
								$class = $options[ 'class' ];
								try {
									$value = $class::load( $value );
								}
								catch( \OutOfRangeException $e ) {
									$value = NULL;
								}
								break;
						}
					}
				}
				
				/* Return the value */
				return $value;
			}
		}
		
		return NULL;
	}

	/**
	 * Property setter
	 *
	 * @param	string		$property		The property to set
	 * @param	mixed		$value			The value to set
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __set( $property, $value )
	{
		/* Ensure we are setting a defined property */
		if ( in_array( $property, static::$columns ) or array_key_exists( $property, static::$columns ) )
		{
			/* Check if there are any optional params assigned to this property */
			if ( array_key_exists( $property, static::$columns ) )
			{
				$options = static::$columns[ $property ];
				
				/* Special format conversion needed? */
				if ( isset( $options[ 'format' ] ) )
				{
					switch( $options[ 'format' ] )
					{
						case 'JSON':
							
							$value = json_encode( $value );
							break;
							
						case 'ActiveRecord':
							
							$class = $options[ 'class' ];
							
							if ( is_object( $value ) )
							{
								if ( $value instanceof ActiveRecord and is_a( $value, $class ) )
								{
									$value = $value->id();
								}
								else
								{
									if ( ! $value instanceof ActiveRecord )
									{
										throw new \InvalidArgumentException( 'Object is not a subclass of MWP\Framework\Pattern\ActiveRecord' );
									}
									throw new \InvalidArgumentException( 'Object expected to be an active record of type: ' . $class . ' but it is a: ' . get_class( $value ) );
								}
							}
							break;
					}
				}
			}
			
			/* Set the value */
			$this->_data[ static::$prefix . $property ] = $value;
		}
	}
	
	/**
	 * Check if a data property is set
	 *
	 * @return	bool
	 */
	public function __isset( $name )
	{
		return $this->__get( $name ) !== NULL;
	}
	
	/**
	 * Check if a data property is set
	 *
	 * @return	void
	 */
	public function __unset( $name )
	{
		unset( $this->_data[ $name ] );
	}
	
	/** 
	 * Get the active record id
	 *
	 * @return	int|NULL
	 */
	public function id()
	{
		if ( isset( $this->_data[ static::$prefix . static::$key ] ) )
		{
			return $this->_data[ static::$prefix . static::$key ];
		}
		
		return NULL;
	}
	
	/**
	 * Load record by id
	 *
	 * @param	int 	$id			Record id
	 * @return	ActiveRecord
	 * @throws	OutOfRangeException		Throws exception if record could not be located
	 */
	public static function load( $id )
	{
		if ( ! $id )
		{
			throw new \OutOfRangeException( 'Invalid ID' );
		}
		
		if ( isset( static::$multitons[ $id ] ) )
		{
			return static::$multitons[ $id ];
		}
		
		$db = Framework::instance()->db();
		$prefix = static::$site_specific ? $db->prefix : $db->base_prefix;

		$row = $db->get_row( $db->prepare( "SELECT * FROM " . $prefix . static::$table . " WHERE " . static::$prefix . static::$key . "=%d", $id ), ARRAY_A );

		if ( $row )
		{
			$record = static::loadFromRowData( $row );
			$record->_wpdb_prefix = $prefix;
			return $record;
		}
		
		throw new \OutOfRangeException( 'Unable to find a record with the id: ' . $id );
	}
	
	/**
	 * Load multiple records
	 *
	 * @param	array|string		$where 			Where clause with associated replacement values
	 * @param	string				$order			Order by ( include field + ASC or DESC ) ex. "field_name DESC"
	 * @param   int|array           $limit          Limit clause. If an int is provided, it should be the number of records to limit by
	 *                                              If an array is provided, the first number will be the start record and the second number will be the limit
	 * @return	array
	 */
	public static function loadWhere( $where, $order=NULL, $limit=NULL )
	{
		if ( is_string( $where ) ) {
			$where = array( $where );
		}
		
		$db = Framework::instance()->db();
		$prefix = static::$site_specific ? $db->prefix : $db->base_prefix;

		$results = array();
		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "SELECT * FROM " . $prefix . static::$table . " WHERE " . $compiled[ 'where' ];
		
		if ( $order !== NULL ) {
			$query .= " ORDER BY " . $order;
		}
		
		if ( $limit !== NULL ) {
			if ( is_array( $limit ) ) {
				$query .= " LIMIT " . $limit[0] . ", " . $limit[1];
			} else {
				$query .= " LIMIT " . $limit;
			}
		}
		
		$prepared_query = ! empty( $compiled[ 'params' ] ) ? $db->prepare( $query, $compiled[ 'params' ] ) : $query;
		$rows = $db->get_results( $prepared_query, ARRAY_A );
		
		if ( ! empty( $rows ) )
		{
			foreach( $rows as $row )
			{
				$record = static::loadFromRowData( $row );
				$record->_wpdb_prefix = $prefix;
				$results[] = $record;
			}
		}
		
		return $results;
	}
	
	/**
	 * Count records
	 *
	 * @param	array|string		$where 			Where clause with associated replacement values
	 * @return	array
	 */
	public static function countWhere( $where )
	{
		if ( is_string( $where ) )
		{
			$where = array( $where );
		}
		
		$db = Framework::instance()->db();
		$prefix = static::$site_specific ? $db->prefix : $db->base_prefix;

		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "SELECT COUNT(*) FROM " . $prefix . static::$table . " WHERE " . $compiled[ 'where' ];
		$prepared_query = ! empty( $compiled[ 'params' ] ) ? $db->prepare( $query, $compiled[ 'params' ] ) : $query;
		$count = $db->get_var( $prepared_query );
		
		return $count;
	}
	
	/**
	 * Delete records
	 *
	 * @param	array|string		$where 			Where clause with associated replacement values
	 * @return	int									Number of rows affected
	 */
	public static function deleteWhere( $where )
	{
		if ( is_string( $where ) )
		{
			$where = array( $where );
		}
		
		$db = Framework::instance()->db();
		$prefix = static::$site_specific ? $db->prefix : $db->base_prefix;

		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "DELETE FROM " . $prefix . static::$table . " WHERE " . $compiled[ 'where' ];
		$prepared_query = ! empty( $compiled[ 'params' ] ) ? $db->prepare( $query, $compiled[ 'params' ] ) : $query;
		return $db->query( $prepared_query );
	}
	
	/**
	 * Compile a where clause with params
	 *
	 * @param	array		$where			Where clauses
	 * @return	array
	 */
	public static function compileWhereClause( $where )
	{
		$params = array();
		$clauses = array();
		$compiled = array
		(
			'where' => "1=0",
			'params' => array(),
		);
		
		if ( ! is_array( $where[0] ) ) {
			$where = array( $where );
		}
		
		$called_class_slug = strtolower( str_replace( '\\', '_', get_called_class() ) );
		
		/* Apply filters */
		$where = apply_filters( 'mwp_active_record_where', $where, get_called_class() );
		$where = apply_filters( 'mwp_active_record_where_' . $called_class_slug, $where );
		
		/* Iterate the clauses to compile the query and replacement values */
		foreach( $where as $clause )
		{
			if ( empty( $clause ) ) {
				continue;
			}
			
			if ( is_array( $clause ) )
			{
				$clauses[] = array_shift( $clause );
				if ( ! empty( $clause ) )
				{
					$compiled[ 'params' ] = array_merge( $compiled[ 'params' ], $clause );
				}
			}
			else
			{
				$clauses[] = $clause;
			}
		}
		
		if ( ! empty( $clauses ) )
		{
			$compiled[ 'where' ] = '('. implode( ') AND (', $clauses ) . ')';
		}
		
		return $compiled;
	}
	
	/**
	 * Get controller actions
	 *
	 * @return	array
	 */
	public function getControllerActions()
	{
		return array(
			'edit' => array(
				'title' => '',
				'icon' => 'glyphicon glyphicon-pencil',
				'attr' => array( 
					'title' => __( static::$lang_edit . ' ' . static::$lang_singular ),
					'class' => 'btn btn-xs btn-default',
				),
				'params' => array(
					'do' => 'edit',
					'id' => $this->id,
				),
			),
			'view' => array(
				'title' => '',
				'icon' => 'glyphicon glyphicon-eye-open',
				'attr' => array( 
					'title' => __( static::$lang_view . ' ' . static::$lang_singular ),
					'class' => 'btn btn-xs btn-default',
				),
				'params' => array(
					'do' => 'view',
					'id' => $this->id,
				),
			),
			'delete' => array(
				'title' => '',
				'icon' => 'glyphicon glyphicon-trash',
				'attr' => array( 
					'title' => __( static::$lang_delete . ' ' . static::$lang_singular ),
					'class' => 'btn btn-xs btn-default',
				),
				'params' => array(
					'do' => 'delete',
					'id' => $this->id,
				),
			)
		);
	}
	
	/**
	 * @var  array    Controllers cache
	 */
	protected static $_controllers = array();
	
	/**
	 * @var string
	 */
	protected static $table_classes = array();
	
	/**
	 * @var string
	 */
	protected static $controller_classes = array();
	
	/**
	 * Set a custom table class for an active record
	 *
	 * @param   string      $class         The table class to use when creating tables
	 * @return  void
	 */
	public static function setTableClass( $class )
	{
		static::$table_classes[ get_called_class() ] = $class;
	}
	
	/**
	 * Get the table class for an active record
	 *
	 * @return  string
	 */
	public static function getTableClass()
	{
		$record_class = get_called_class();
		
		if ( isset( static::$table_classes[ $record_class ] ) ) {
			if ( is_subclass_of( static::$table_classes[ $record_class ], ActiveRecordTable::class ) ) {
				return static::$table_classes[ $record_class ];
			}
		}
		
		return ActiveRecordTable::class;
	}
	
	/**
	 * Set a custom controller class for an active record
	 *
	 * @param   string      $class         The controller class to use when creating controllers
	 * @return  void
	 */
	public static function setControllerClass( $class )
	{
		static::$controller_classes[ get_called_class() ] = $class;
	}
	
	/**
	 * Get the controller class for an active record
	 *
	 * @return  string
	 */
	public static function getControllerClass()
	{
		$record_class = get_called_class();
		
		if ( isset( static::$controller_classes[ $record_class ] ) ) {
			if ( is_subclass_of( static::$controller_classes[ $record_class ], ActiveRecordController::class ) ) {
				return static::$controller_classes[ $record_class ];
			}
		}
		
		return ActiveRecordController::class;
	}
	
	/**
	 * Create a table for viewing active records
	 *
	 * @param	array				$args			Table construct arguments
	 * @return	MWP\Framework\Helpers\ActiveRecordTable
	 */
	public static function createDisplayTable( $args=array() )
	{
		$tableClass = static::getTableClass();
		
		$table = new $tableClass( array_merge( array( 
			'recordClass' => get_called_class(),
			'singular' => strtolower( static::$lang_singular ),
			'plural' => strtolower( static::$lang_plural ),
		), $args ) );
		
		return $table;
	}
	
	/**
	 * Create a controller which can be used to interface with this active record class
	 *
	 * @param   string      $key          The controller key used to access this controller
	 * @param   array       $options      Optional configuration options passed to controller
	 * @return  ActiveRecordController
	 */
	public static function createController( $key, $options=array() )
	{
		$controllerClass = static::getControllerClass();
		
		if ( static::$sequence_col ) {
			$options = array_replace_recursive( array( 'tableConfig' => array( 'sequencingColumn' => static::$prefix . static::$sequence_col ) ), $options );
		}
		
		$controller = new $controllerClass( get_called_class(), $options );
		static::setController( $key, $controller );
		
		return $controller;
	}
	
	/**
	 * Set the cached controller for a class
	 *
	 * @param   string                    $key              The controller key
	 * @param   ActiveRecordController    $controller       The controller to cache
	 * @return	ActiveRecordController
	 */
	public static function setController( $key, ActiveRecordController $controller )
	{
		return static::$_controllers[ get_called_class() ][ $key ] = $controller;
	}
	
	/**
	 * Get a created controller by key
	 *
	 * @param   string      $key             The controller key to get
	 * @return  ActiveRecordController|NULL
	 */
	public static function getController( $key )
	{
		$record_class = get_called_class();
		
		if ( isset( static::$_controllers[ $record_class ][ $key ] ) ) {
			return static::$_controllers[ $record_class ][ $key ];
		}
		
		return NULL;
	}
	
	/**
	 * Create a new form
	 *
	 * @param   string        $type         An arbitrary value used to help identify the purpose of the form
	 * @param   array         $options      Options to pass to the created form
	 * @return  MWP\Framework\Helpers\Form
	 */
	public static function createForm( $type='', $options=[] )
	{
		$name = strtolower( str_replace( '\\', '_', get_called_class() ) ) . ( $type ? '_' . $type : '' ) . '_form';
		$pluginClass = static::$plugin_class;
		$plugin = $pluginClass::instance();
		
		$form = $plugin->createForm( $name, $options );
		
		return $form;
	}
	
	/**
	 * Get a specific type of form
	 *
	 * @param   string       $type           The type of form to get
	 * @return	MWP\Framework\Helpers\Form
	 */
	public function getForm( $type='edit' )
	{
		if ( $type ) {
			$buildFormMethod = 'build' . ucfirst( $type ) . 'Form';
			
			if ( is_callable( array( $this, $buildFormMethod ) ) ) {
				return call_user_func( array( $this, $buildFormMethod ) );
			}
		}
		
		return static::createForm( $type );
	}
	
	/**
	 * Get editing form
	 *
	 * @return	MWP\Framework\Helpers\Form
	 */
	protected function buildEditForm()
	{
		$form = static::createForm( 'edit' );
		
		foreach( static::$columns as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$k = $v;
			}
			
			if ( $k !== static::$key ) {
				$form->addField( $k, 'text', [
					'label' => ucwords( str_replace( '_', ' ', $k ) ),
					'data' => $this->$k,
				]);
			}
		}
		
		$form->addField( 'submit', 'submit', [ 'label' => 'Save', 'row_attr' => [ 'class' => 'text-center' ] ] );
		
		return $form;		
	}
	
	/**
	 * Confirm delete form
	 *
	 * @return	MWP\Framework\Helpers\Form
	 */
	protected function buildDeleteForm()
	{
		$form = static::createForm( 'delete', array( 'attr' => array( 'class' => 'container' ) ) );
		
		$form->addField( 'cancel', 'submit', array( 
			'label' => __( 'Cancel', 'mwp-framework' ), 
			'attr' => array( 'class' => 'btn btn-warning' ),
			'row_attr' => array( 'class' => 'col-xs-6 text-right' ),
		));
		
		$form->addField( 'confirm', 'submit', array( 
			'label' => __( 'Confirm Delete', 'mwp-framework' ), 
			'attr' => array( 'class' => 'btn btn-danger' ),
			'row_attr' => array( 'class' => 'col-xs-6 text-left' ),
		));
		
		return $form;
	}
	
	/**
	 * Process submitted form values 
	 *
	 * @param	array			$values				Submitted form values
	 * @param   string          $type               The type of the form values being processed
	 * @return	void
	 */
	public function processForm( $values, $type='edit' )
	{
		if ( $type ) {
			$processFormMethod = 'process' . ucfirst( $type ) . 'Form';
			
			if ( is_callable( array( $this, $processFormMethod ) ) ) {
				call_user_func( array( $this, $processFormMethod ), $values );
			}
		}
	}
	
	/**
	 * Process submitted form values 
	 *
	 * @param	array			$values				Submitted form values
	 * @return	void
	 */
	protected function processEditForm( $values )
	{
		$record_properties = array();
		
		foreach( static::$columns as $col => $opts ) {
			$col_key = is_array( $opts ) ? $col : $opts;
			if ( $col_key !== static::$key ) {
				$record_properties[] = $col_key;
			}
		}
		
		foreach( $values as $key => $value ) {
			if ( in_array( $key, $record_properties ) ) {
				$this->$key = $value;
			}
		}
	}
	
	/**
	 * Load record from row data
	 *
	 * @param	array		$row_data		Row data from the database
	 * @return	ActiveRecord
	 */
	public static function loadFromRowData( $row_data )
	{
		/* Look for cached record in multiton store */
		if ( isset( $row_data[ static::$prefix . static::$key ] ) and $row_data[ static::$prefix . static::$key ] ) {
			if ( isset( static::$multitons[ $row_data[ static::$prefix . static::$key ] ] ) ) {
				return static::$multitons[ $row_data[ static::$prefix . static::$key ] ];
			}
		}
		
		/* Build the record */
		$record = new static;
		foreach( $row_data as $column => $value ) {
			if ( static::$prefix and substr( $column, 0, strlen( static::$prefix ) ) == static::$prefix ) {
				$column = substr( $column, strlen( static::$prefix ) );
			}
			
			$record->setDirectly( $column, $value );
		}
		
		/* Cache the record in the multiton store */
		if ( isset( $row_data[ static::$prefix . static::$key ] ) and $row_data[ static::$prefix . static::$key ] ) {
			static::$multitons[ $row_data[ static::$prefix . static::$key ] ] = $record;
		}
		
		return $record;
	}
	
	/**
	 * Set internal data properties directly
	 *
	 * @param	string		$property		The property to set
	 * @param	mixed		$value			The value to set
	 * @return	void
	 */
	public function setDirectly( $property, $value )
	{
		/* Ensure we are setting a defined property */
		if ( in_array( $property, static::$columns ) or array_key_exists( $property, static::$columns ) )
		{
			$this->_data[ static::$prefix . $property ] = $value;
		}
	}
	
	/**
	 * Get internal data properties directly
	 *
	 * @param	string		$property		The property to set
	 * @return	void
	 */
	public function getDirectly( $property )
	{
		/* Ensure we are setting a defined property */
		if ( in_array( $property, static::$columns ) or array_key_exists( $property, static::$columns ) )
		{
			if ( array_key_exists( static::$prefix . $property, $this->_data ) )
			{
				return $this->_data[ static::$prefix . $property ];
			}
		}
		
		return NULL;
	}
	
	/**
	 * Save the record
	 *
	 * @return	bool|WP_Error
	 */
	public function save()
	{
		$db = Framework::instance()->db();
		$self = get_called_class();
		$row_key = static::$prefix . static::$key;
		
		if ( ! isset( $this->_data[ $row_key ] ) or ! $this->_data[ $row_key ] )
		{
			$format = array_map( function( $value ) use ( $self ) { return $self::dbFormat( $value ); }, $this->_data );
			
			if ( $db->insert( $this->get_db_prefix() . static::$table, $this->_data, $format ) === FALSE )
			{
				return new \WP_Error( 'sql_error', $db->last_error );
			}
			else
			{
				$this->_data[ $row_key ] = $db->insert_id;
				static::$multitons[ $this->_data[ $row_key ] ] = $this;
				return TRUE;
			}
		}
		else
		{
			$format = array_map( function( $value ) use ( $self ) { return $self::dbFormat( $value ); }, $this->_data );
			$where_format = static::dbFormat( $this->_data[ $row_key ] );
			
			if ( $db->update( $this->get_db_prefix() . static::$table, $this->_data, array( $row_key => $this->_data[ $row_key ] ), $format, $where_format ) === FALSE )
			{
				return new \WP_Error( 'sql_error', $db->last_error );
			}
			
			return TRUE;
		}
	}
	
	/**
	 * Flush a record from the multiton store
	 */
	public function flush()
	{
		$row_key = static::$prefix . static::$key;
		$id = $this->_data[ $row_key ];
		
		unset( static::$multitons[ $id ] );
	}
	
	/**
	 * Delete a record
	 *
	 * @return	bool|WP_Error
	 */
	public function delete()
	{
		$row_key = static::$prefix . static::$key;
		
		if ( isset( $this->_data[ $row_key ] ) and $this->_data[ $row_key ] )
		{
			$db = Framework::instance()->db();
			$id = $this->_data[ $row_key ];
			$format = static::dbFormat( $id );
			
			if ( $db->delete( $this->get_db_prefix() . static::$table, array( $row_key => $id ), $format ) )
			{
				unset( static::$multitons[ $id ] );
				return TRUE;
			}
			else
			{
				return new \WP_Error( 'sql_error', $db->last_error );
			}
		}
	}
	
	/**
	 * Get an array of the internal record data
	 *
	 * @return	array
	 */
	public function dataArray()
	{
		return apply_filters( 'mwp_record_data_array', $this->_data, $this );
	}
	
	/**
	 * Get the database placeholder format for a value type
	 *
	 * @param	mixed		$value			The value to check
	 * @return	string	
	 */
	public static function dbFormat( $value )
	{
		if ( is_int( $value ) ) {
			return '%d';
		}
		
		if ( is_float( $value ) ) {
			return '%f';
		}
		
		return '%s';
	}
	
	/**
	 * Get the site db prefix for this record
	 *
	 * @return	string
	 */
	public function get_db_prefix()
	{
		if ( isset( $this->_wpdb_prefix ) )
		{
			return $this->_wpdb_prefix;
		}
		
		$db = Framework::instance()->db();
		$this->_wpdb_prefix = static::$site_specific ? $db->prefix : $db->base_prefix;
		
		return $this->_wpdb_prefix;
	}
	
}
