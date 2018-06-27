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
abstract class _ActiveRecord
{
	/**
	 * @var	array		Multitons cache (needs to be defined in subclasses also)
	 */
	protected static $multitons = array();
	
	/**
	 * @var	string		Table name
	 */
	protected static $table;
	
	/**
	 * @var	array		Table columns
	 */
	protected static $columns = array();
	
	/**
	 * @var	string		Table primary key
	 */
	protected static $key;
	
	/**
	 * @var	string		Table column prefix
	 */
	protected static $prefix = '';
	
	/**
	 * @var bool		Site specific table? (for multisites)
	 */
	protected static $site_specific = FALSE;
	
	/**
	 * @var	string
	 */
	protected static $plugin_class = 'MWP\Framework\Framework';
	
	/**
	 * @var	string
	 */
	protected static $sequence_col;
	
	/**
	 * @var	string
	 */
	protected static $parent_col;

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
	 * @var array		Changed data
	 */
	protected $_changed = array();
	
	/**
	 * Get database table
	 *
	 * @return	string
	 */
	public static function _getTable()
	{
		return static::$table;
	}	
	
	/**
	 * Get database columns
	 *
	 * @return	array
	 */
	public static function _getColumns()
	{
		return static::$columns;
	}	
	
	/**
	 * Get column name prefix
	 *
	 * @return	string
	 */
	public static function _getPrefix()
	{
		return static::$prefix;
	}
	
	/**
	 * Get database row id column
	 *
	 * @return	string
	 */
	public static function _getKey()
	{
		return static::$key;
	}
	
	/**
	 * Get database table
	 *
	 * @return	string
	 */
	public static function _getMultisite()
	{
		return static::$site_specific;
	}
	
	/**
	 * Get database sequence column
	 *
	 * @return	string
	 */
	public static function _getSequenceCol()
	{
		return static::$sequence_col;
	}
	
	/**
	 * Get database parent column
	 *
	 * @return	string
	 */
	public static function _getParentCol()
	{
		return static::$parent_col;
	}
	
	/**
	 * Get the plugin class
	 *
	 * @return	string
	 */
	public static function _getPluginClass()
	{
		return static::$plugin_class;
	}
	
	/**
	 * Get the 'create record' page title
	 * 
	 * @return	string
	 */
	public static function _getCreateTitle()
	{
		return __( static::$lang_create . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'view record' page title
	 * 
	 * @return	string
	 */
	public function _getViewTitle()
	{
		return __( static::$lang_view . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'edit record' page title
	 * 
	 * @param	string			$type			The type of edit being performed
	 * @return	string
	 */
	public function _getEditTitle( $type=NULL )
	{
		return __( static::$lang_edit . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the 'delete record' page title
	 * 
	 * @return	string
	 */
	public function _getDeleteTitle()
	{
		return __( static::$lang_delete . ' ' . static::$lang_singular );
	}
	
	/**
	 * Get the singular name
	 * 
	 * @return	string
	 */
	public function _getSingularName()
	{
		return __( static::$lang_singular );
	}
	
	/**
	 * Get the plural name
	 * 
	 * @return	string
	 */
	public function _getPluralName()
	{
		return __( static::$lang_plural );
	}
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		$pluginClass = static::_getPluginClass();
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
		$columns = static::_getColumns();
		$prefix = static::_getPrefix();
		
		/* Ensure we are getting a defined property */
		if ( in_array( $property, $columns ) or array_key_exists( $property, $columns ) )
		{
			/* Proceed if we have a value to return */
			if ( array_key_exists( $prefix . $property, $this->_data ) )
			{
				/* Retrieve the value */
				$value = $this->_data[ $prefix . $property ];
				
				/* Check if there are any optional params assigned to this property */
				if ( array_key_exists( $property, $columns ) )
				{
					$options = $columns[ $property ];
					
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
		$columns = static::_getColumns();
		$prefix = static::_getPrefix();
		
		/* Ensure we are setting a defined property */
		if ( in_array( $property, $columns ) or array_key_exists( $property, $columns ) )
		{
			/* Check if there are any optional params assigned to this property */
			if ( array_key_exists( $property, $columns ) )
			{
				$options = $columns[ $property ];
				
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
								if ( $value instanceof ActiveRecord and is_a( $value, $class ) ) {
									$value = $value->id();
								}
								else {
									if ( ! $value instanceof ActiveRecord ) {
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
			$prop_key = $prefix . $property;
			if ( ! array_key_exists( $prop_key, $this->_data ) or $this->_data[ $prop_key ] !== $value ) {
				
				/* Save original persisted value for reference later */
				if ( ! array_key_exists( $prop_key, $this->_changed ) ) {
					$this->_changed[ $prop_key ] = array_key_exists( $prop_key, $this->_data ) ? $this->_data[ $prop_key ] : NULL;
				}
				
				/* Update the object property */
				$this->_data[ $prop_key ] = $value;
				
				/* Indicate that this property is not changed if it returns to the original persisted value */
				if ( $this->_changed[ $prop_key ] === $this->_data[ $prop_key ] ) {
					unset( $this->_changed[ $prop_key ] );
				}
			}
		}
	}
	
	/**
	 * Get any changed values
	 *
	 * @return	array
	 */
	public function _getChanged()
	{
		return $this->_changed;
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
		$prefix = static::_getPrefix();
		$key = static::_getKey();
		
		if ( isset( $this->_data[ $prefix . $key ] ) ) {
			return $this->_data[ $prefix . $key ];
		}
		
		return NULL;
	}
	
	/**
	 * Load record from row data
	 *
	 * @param	array		$row_data		Row data from the database
	 * @return	ActiveRecord
	 */
	public static function loadFromRowData( $row_data )
	{
		$prefix = static::_getPrefix();
		$key = static::_getKey();
		
		/* Look for cached record in multiton store */
		if ( isset( $row_data[ $prefix . $key ] ) and $row_data[ $prefix . $key ] ) {
			if ( isset( static::$multitons[ $row_data[ $prefix . $key ] ] ) ) {
				return static::$multitons[ $row_data[ $prefix . $key ] ];
			}
		}
		
		/* Build the record */
		$record = new static();
		foreach( $row_data as $column => $value ) {
			$record->_data[ $column ] = $value;
		}
		
		/* Cache the record in the multiton store */
		if ( isset( $row_data[ $prefix . $key ] ) and $row_data[ $prefix . $key ] ) {
			static::$multitons[ $row_data[ $prefix . $key ] ] = $record;
		}
		
		return $record;
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
		if ( ! $id ) {
			throw new \OutOfRangeException( 'Invalid ID' );
		}
		
		if ( isset( static::$multitons[ $id ] ) ) {
			return static::$multitons[ $id ];
		}
		
		$db = Framework::instance()->db();
		$db_prefix = static::_getMultisite() ? $db->prefix : $db->base_prefix;

		$row = $db->get_row( $db->prepare( "SELECT * FROM " . $db_prefix . static::_getTable() . " WHERE `" . static::_getPrefix() . static::_getKey() . "`=%d", $id ), ARRAY_A );

		if ( $row ) {
			$record = static::loadFromRowData( $row );
			$record->_wpdb_prefix = $db_prefix;
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
		
		$table = static::_getTable();
		$prefix = static::_getPrefix();
		$sequence_col = static::_getSequenceCol();
		
		$db = Framework::instance()->db();
		$db_prefix = static::_getMultisite() ? $db->prefix : $db->base_prefix;

		$results = array();
		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "SELECT * FROM " . $db_prefix . $table . " WHERE " . $compiled[ 'where' ];
		
		if ( $order !== NULL ) {
			$query .= " ORDER BY " . $order;
		} else {
			if ( isset( $sequence_col ) ) {
				$query .= " ORDER BY `" . $prefix . $sequence_col . "` ASC";
			}
		}
		
		if ( $limit !== NULL ) {
			if ( is_array( $limit ) ) {
				$query .= " LIMIT " . intval( $limit[0] ) . ", " . intval( $limit[1] );
			} else {
				$query .= " LIMIT " . intval( $limit );
			}
		}
		
		$prepared_query = ! empty( $compiled[ 'params' ] ) ? $db->prepare( $query, $compiled[ 'params' ] ) : $query;
		$rows = $db->get_results( $prepared_query, ARRAY_A );
		
		if ( ! empty( $rows ) )
		{
			foreach( $rows as $row ) {
				$record = static::loadFromRowData( $row );
				$record->_wpdb_prefix = $db_prefix;
				$results[] = $record;
			}
		}
		
		return $results;
	}
	
	/**
	 * Count records
	 *
	 * @param	array|string		$where 			Where clause with associated replacement values
	 * @return	int
	 */
	public static function countWhere( $where )
	{
		if ( is_string( $where ) ) {
			$where = array( $where );
		}
		
		$table = static::_getTable();
		$db = Framework::instance()->db();
		$db_prefix = static::_getMultisite() ? $db->prefix : $db->base_prefix;

		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "SELECT COUNT(*) FROM " . $db_prefix . $table . " WHERE " . $compiled[ 'where' ];
		$prepared_query = ! empty( $compiled[ 'params' ] ) ? $db->prepare( $query, $compiled[ 'params' ] ) : $query;
		$count = $db->get_var( $prepared_query );
		
		return (int) $count;
	}
	
	/**
	 * Delete records
	 *
	 * @param	array|string		$where 			Where clause with associated replacement values
	 * @return	int									Number of rows affected
	 */
	public static function deleteWhere( $where )
	{
		if ( is_string( $where ) ) {
			$where = array( $where );
		}
		
		$table = static::_getTable();
		$db = Framework::instance()->db();
		$db_prefix = static::$site_specific ? $db->prefix : $db->base_prefix;

		$compiled = static::compileWhereClause( $where );
		
		/* Get results of the prepared query */
		$query = "DELETE FROM " . $db_prefix . $table . " WHERE " . $compiled[ 'where' ];
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
			'view' => array(
				'title' => $this->_getViewTitle(),
				'icon' => 'glyphicon glyphicon-eye-open',
				'params' => array(
					'do' => 'view',
					'id' => $this->id(),
				),
			),
			'edit' => array(
				'title' => $this->_getEditTitle(),
				'icon' => 'glyphicon glyphicon-pencil',
				'params' => array(
					'do' => 'edit',
					'id' => $this->id(),
				),
			),
			'delete' => array(
				'separator' => true,
				'title' => $this->_getDeleteTitle(),
				'icon' => 'glyphicon glyphicon-trash',
				'attr' => array( 
					'class' => 'text-danger',
				),
				'params' => array(
					'do' => 'delete',
					'id' => $this->id(),
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
		$pluginClass = static::_getPluginClass();
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
		
		$columns = static::_getColumns();
		$id_column = static::_getKey();
		
		$timezone_string = get_option( 'timezone_string' );
		
		foreach( $columns as $k => $v ) {
			if ( is_numeric( $k ) and ! is_array( $v ) ) {
				$k = $v;
			}
			
			if ( $k !== $id_column ) 
			{
				$column_props = is_array( $v ) ? $v : [ 'type' => 'varchar', 'length' => 255 ];
				$column_props = array_combine( array_map( 'strtolower', array_keys( $column_props ) ), array_values( $column_props ) );
				
				if ( ! isset( $column_props['type'] ) ) {
					$column_props['type'] = 'varchar';
				}
				
				$field_type = 'text';				
				$field_options = array(
					'label' => isset( $column_props['title'] ) ? $column_props['title'] : ucwords( str_replace( '_', ' ', $k ) ),
					'data' => $this->_getDirectly( $k ),
				);
				
				if ( strstr( $column_props['type'], 'blob' ) > -1 ) {
					continue;
				}
				
				if ( strstr( $column_props['type'], 'binary' ) > -1 ) {
					continue;
				}
				
				if ( isset( $column_props['edit'] ) and $column_props['edit'] === false ) {
					continue;
				}
				
				switch( $column_props['type'] ) {
					case 'text':
					case 'tinytext':
					case 'mediumtext':
					case 'longtext':
						$field_type = 'textarea';
					case 'varchar':
					case 'char':
						$field_options['empty_data'] = isset( $column_props['allow_null'] ) && $column_props['allow_null'] ? NULL : '';
						break;
					case 'tinyint':
					case 'boolean':
					case 'bit':
						$field_type = 'checkbox';
						$field_options['data'] = (bool) $field_options['data'];
						break;
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'bigint':
						$field_type = 'integer';
						$field_options['data'] = (int) $field_options['data'];
						$field_options['attr']['step'] = 1;
						if ( isset( $column_props['unsigned'] ) && $column_props['unsigned'] ) {
							$field_options['attr']['min'] = 0;
						}
						break;
					case 'decimal':
					case 'float':
					case 'double':
						$field_type = 'number';
						$field_options['data'] = (float) $field_options['data'];
						if ( isset( $column_props['decimals'] ) ) {
							$field_options['scale'] = (int) $column_props['decimals'];
							if ( intval( $column_props['decimals'] ) ) {
								$field_options['attr']['step'] = 1 / ( intval( $column_props['decimals'] ) * 10 );
							}
							if ( isset( $column_props['unsigned'] ) && $column_props['unsigned'] ) {
								$field_options['attr']['min'] = 0;
							}
						}
						break;
					case 'year':
						$field_type = 'number';
						$field_options['attr'] = [ 'min' => 0, 'max' => 2155 ];
						if ( strtotime( $field_options['data'] ) === false ) {
							$field_options['data'] = NULL;
						}
						if ( isset( $timezone_string ) ) {
							$field_options['view_timezone'] = $timezone_string;
						}
						break;
					case 'time':
						$field_type = 'time';
						$field_options['input'] = 'string';
						if ( strtotime( $field_options['data'] ) === false ) {
							$field_options['data'] = NULL;
						}
						if ( isset( $timezone_string ) ) {
							$field_options['view_timezone'] = $timezone_string;
						}
						break;
					case 'date':
						$field_type = 'date';
						$field_options['input'] = 'string';
						if ( strtotime( $field_options['data'] ) === false ) {
							$field_options['data'] = NULL;
						}
						if ( isset( $timezone_string ) ) {
							$field_options['view_timezone'] = $timezone_string;
						}
						break;
					case 'datetime':
						$field_type = 'datetime';
						$field_options['input'] = 'string';
						if ( strtotime( $field_options['data'] ) === false ) {
							$field_options['data'] = NULL;
						}
						if ( isset( $timezone_string ) ) {
							$field_options['view_timezone'] = $timezone_string;
						}
						break;
					case 'timestamp':
						$field_type = 'datetime';
						$field_options['input'] = 'string';
						if ( isset( $timezone_string ) ) {
							$field_options['view_timezone'] = $timezone_string;
						}
						break;
					case 'enum':
						$choices = isset( $column_props['values'] ) && is_array( $column_props['values'] ) ? 
							array_combine( array_values( $column_props['values'] ), array_values( $column_props['values'] ) ) :
							array();
					
						$field_type = 'choice';
						$field_options['required'] = true;
						$field_options['choices'] = $choices;
						$field_options['expanded'] = true;
						$field_options['multiple'] = false;
						if ( count( $choices ) >= 5 ) {
							$field_options['expanded'] = false;
						}
						break;
					case 'set':
						$choices = isset( $column_props['values'] ) && is_array( $column_props['values'] ) ? 
							array_combine( array_values( $column_props['values'] ), array_values( $column_props['values'] ) ) :
							array();
					
						$field_type = 'choice';
						$field_options['required'] = true;
						$field_options['choices'] = $choices;
						$field_options['expanded'] = true;
						$field_options['multiple'] = true;
						$field_options['data'] = explode( ',', $field_options['data'] );
						
						if ( count( $choices ) >= 5 ) {
							$field_options['expanded'] = false;
						}
						break;
						
						
					default:
				}
				
				$form->addField( $k, $field_type, $field_options );				
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
		$form = static::createForm( 'delete', array( 'attr' => array( 'class' => 'container', 'style' => 'max-width: 600px; margin: 75px auto;' ) ) );
		
		$form->addHtml( 'delete_notice', $this->getPlugin()->getTemplateContent( 'views/management/records/notice_delete', [
			'record' => $this,
		]));
		
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
		$columns = static::_getColumns();
		$id_column = static::_getKey();
		
		foreach( $columns as $col => $opts ) {
			$col_key = is_array( $opts ) ? $col : $opts;
			if ( $col_key !== $id_column ) {
				$record_properties[] = $col_key;
			}
		}
		
		foreach( $values as $key => $value ) {
			if ( in_array( $key, $record_properties ) ) {
				if ( isset( $columns[ $key ]['type'] ) and strtolower( $columns[ $key ]['type'] ) == 'set' and is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				$this->_setDirectly( $key, $value );
			}
		}
	}
	
	/**
	 * Set internal data properties directly
	 *
	 * @param	string		$property			The property to set
	 * @param	mixed		$value				The value to set
	 * @param	bool		$detect_change		Update the records changed fields if value has changed
	 * @return	void
	 */
	public function _setDirectly( $property, $value )
	{
		$columns = static::_getColumns();
		$prefix = static::_getPrefix();
		
		$prop_key = $prefix . $property;
		$data_exists = array_key_exists( $prop_key, $this->_data );
		$change_exists = array_key_exists( $prop_key, $this->_changed );
		
		/* Ensure we are setting a defined property */
		if ( in_array( $property, $columns ) or array_key_exists( $property, $columns ) ) {
			// Ensure data has changed
			if ( ! $data_exists or $this->_data[ $prop_key ] !== $value ) { 
			
				// Save original value for reference later
				if ( ! $change_exists ) {
					$this->_changed[ $prop_key ] = isset( $this->_data[ $prop_key ] ) ? $this->_data[ $prop_key ] : NULL;
				}
				
				// Update the data
				$this->_data[ $prop_key ] = $value;
				
				// Clear change if data returns to original state
				if ( $this->_data[ $prop_key ] === $this->_changed[ $prop_key ] ) {
					unset( $this->_changed[ $prop_key ] );
				}				
			}
		}
	}
	
	/**
	 * Get internal data properties directly
	 *
	 * @param	string		$property		The property to set
	 * @return	void
	 */
	public function _getDirectly( $property )
	{
		$columns = static::_getColumns();
		$prefix = static::_getPrefix();
		
		/* Ensure we are getting a defined property */
		if ( in_array( $property, $columns ) or array_key_exists( $property, $columns ) ) {
			if ( array_key_exists( $prefix . $property, $this->_data ) ) {
				return $this->_data[ $prefix . $property ];
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
		
		$table = static::_getTable();
		$prefix = static::_getPrefix();
		$key = static::_getKey();
		
		$id_column = $prefix . $key;
		
		if ( ! isset( $this->_data[ $id_column ] ) or ! $this->_data[ $id_column ] ) {
			$format = array_map( function( $value ) use ( $self ) { return $self::dbFormat( $value ); }, $this->_data );
			
			if ( $db->insert( $this->get_db_prefix() . $table, $this->_data, $format ) === FALSE ) {
				return new \WP_Error( 'sql_error', $db->last_error );
			}

			$this->_data[ $id_column ] = $db->insert_id;
			static::$multitons[ $this->_data[ $id_column ] ] = $this;
			$this->_changed = [];
			return TRUE;
		}
		else
		{
			/* Only save updated data. 
			 * This reduces the chance of concurrent threads clobbering each 
			 * others updates if updating the same record at the same time. 
			 */
			
			$updated_data = [];
			foreach( $this->_changed as $key => $value ) {
				$updated_data[ $key ] = $this->_data[ $key ];
			}
			
			if ( ! empty( $updated_data ) ) {
				$format = array_map( function( $value ) use ( $self ) { return $self::dbFormat( $value ); }, $updated_data );
				$where_format = static::dbFormat( $this->_data[ $id_column ] );
				
				if ( $db->update( $this->get_db_prefix() . $table, $updated_data, array( $id_column => $this->_data[ $id_column ] ), $format, $where_format ) === FALSE ) {
					return new \WP_Error( 'sql_error', $db->last_error );
				}
				
				$this->_changed = [];
			}
			
			return TRUE;
		}
	}
	
	/**
	 * Flush a record from the multiton store
	 */
	public function flush()
	{
		$id_column = static::_getPrefix() . static::_getKey();
		$id = $this->_data[ $id_column ];
		
		unset( static::$multitons[ $id ] );
	}
	
	/**
	 * Delete a record
	 *
	 * @return	bool|WP_Error
	 */
	public function delete()
	{
		$table = static::_getTable();
		$prefix = static::_getPrefix();
		$key = static::_getKey();
		
		$id_column = $prefix . $key;
		
		if ( isset( $this->_data[ $id_column ] ) and $this->_data[ $id_column ] )
		{
			$db = Framework::instance()->db();
			$id = $this->_data[ $id_column ];
			$format = static::dbFormat( $id );
			
			if ( $db->delete( $this->get_db_prefix() . $table, array( $id_column => $id ), $format ) ) {
				unset( static::$multitons[ $id ] );
				return TRUE;
			}
			else {
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
	 * Get record db schema
	 *
	 * @return	array
	 */
	public static function getSchema()
	{
		$table = static::_getTable();
		$prefix = static::_getPrefix();
		$key = static::_getKey();
		$columns = static::_getColumns();
		
		$id_column = $prefix . $key;
		
		$record_schema = [
			'name' => $table,
			'columns' => [
				$id_column => [
					"allow_null" => false,
					"auto_increment" => true,
					"binary" => false,
					"decimals" => null,
					"default" => null,
					"length" => 20,
					"name" => $id_column,
					"type" => "BIGINT",
					"unsigned" => true,
					"values" => [],
					"zerofill" => false
				],
			],
			'indexes' => [
				'PRIMARY' => [
					"type" => "primary",
					"name" => "PRIMARY",
					"length" => [
						null
					],
					"columns" => [
						$id_column,
					],
				]
			],
		];
		
		foreach( $columns as $column => $properties ) {
			if ( is_array( $properties ) ) {
				if ( $column !== $key and isset( $properties['type'] ) ) {
					$record_schema['columns'][ $prefix . $column ] = array_merge( $properties, array( 'name' => $prefix . $column ) );
					if ( isset( $properties['index'] ) and $properties['index'] ) {
						$record_schema['indexes'][ $prefix . $column ] = array(
							'type' => 'key',
							'name' => $prefix . $column,
							'length' => [ null ],
							'columns' => [ $prefix . $column ],
						);
					}
				}
			} else {
				if ( $properties !== $key ) {
					$record_schema['columns'][ $prefix . $properties ] = array(
						'name' => $prefix . $properties,
						'type' => 'varchar',
						'length' => 255,
					);
				}
			}
		}

		return $record_schema;
	}
	
	/**
	 * Get the site db prefix for this record
	 *
	 * @return	string
	 */
	public function get_db_prefix()
	{
		if ( isset( $this->_wpdb_prefix ) ) {
			return $this->_wpdb_prefix;
		}
		
		$db = Framework::instance()->db();
		$this->_wpdb_prefix = static::_getMultisite() ? $db->prefix : $db->base_prefix;
		
		return $this->_wpdb_prefix;
	}
	
	/**
	 * Perform a bulk action on records
	 *
	 * @param	string			$action					The action to perform
	 * @param	array			$records				The records to perform the bulk action on
	 */
	public static function processBulkAction( $action, array $records )
	{
		foreach( $records as $record ) {
			if ( is_callable( array( $record, $action ) ) ) {
				call_user_func( array( $record, $action ) );
			}
		}
	}
	
}
