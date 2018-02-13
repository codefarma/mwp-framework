<?php
/**
 * Database Helpers (Singleton)
 * 
 * Created:    Nov 20, 2016
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.0.0
 */

namespace MWP\Framework;

use \MWP\Framework\Pattern\Singleton;
use \MWP\Framework\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Provides helper functions for managing the database
 */
class _DbHelper extends Singleton
{
	protected static $_instance;
	
	/**
	 * @var	wpdb	Wordpress database
	 */
	public $db;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	protected function __construct()
	{
		$this->db = Framework::instance()->db();
	}

	/**
	 * Get the table definition for an existing table
	 *
	 * @param	string		$table			Table Name
	 * @param	bool		$ms_context		Whether the table should be created in the context of the primary site or the current multisite
	 * @return	array
	 * @throws	\ErrorException
	 */
	public function getTableDefinition( $table, $ms_context=FALSE )
	{
		$prefix = $ms_context ? $this->db->prefix : $this->db->base_prefix;
		
		/* Init table definition */
		$definition = array(
			'name'		=> $table,
			'columns' 	=> array(),
		);
	
		/* Check that table exists */
		if( ! $this->tableExists( $table, $ms_context ) )
		{
			throw new \ErrorException;
		}
		
		$columns = $this->db->get_results( "SHOW FULL COLUMNS FROM `{$prefix}" . esc_sql( $table ) . '`', ARRAY_A );
		
		foreach ( $columns as $row )
		{
			/* Init column definition */
			$columnDefinition = array(
				'name' 		=> $row['Field'],
				'type'		=> '',
				'length'	=> 0,
				'decimals'	=> NULL,
				'values'	=> array()
			);
			
			if ( isset( $row['Collation'] ) )
			{
				$columnDefinition['collation'] = $row['Collation'];
			}
			
			/* Determine the field type */
			if( mb_strpos( $row['Type'], '(' ) !== FALSE )
			{
				preg_match( '/(.+?)\((.+?)\)/', $row['Type'], $matches );
				$options = $matches[2];
				$type = preg_replace( '/(.+?)\((.+?)\)/', "$1(___TEMP___)", $row['Type'] );
				$typeInfo = explode( ' ', $type );
				$typeInfo[0] = str_replace( "___TEMP___", $options, $typeInfo[0] );

				preg_match( '/(.+?)\((.+?)\)/', $typeInfo[0], $matches );
				
				$columnDefinition['type'] = mb_strtoupper( $matches[1] );
				
				if( $columnDefinition['type'] === 'ENUM' or $columnDefinition['type'] === 'SET' )
				{
					preg_match_all( "/'(.*?)'/", $matches[2], $enum );
					$columnDefinition['values'] = $enum[1];
				}
				else
				{						
					$lengthInfo = explode( ',', $matches[2] );
					$columnDefinition['length'] = intval( $lengthInfo[0] );
					if( isset( $lengthInfo[1] ) )
					{
						$columnDefinition['decimals'] = intval( $lengthInfo[1] );
					}
				}
			}
			else
			{
				$typeInfo = explode( ' ', $row['Type'] );

				$columnDefinition['type'] = mb_strtoupper( $typeInfo[0] );
				$columnDefinition['length'] = 0;
			}
			
			/* Is it unsigned? */
			$columnDefinition['unsigned'] = in_array( 'unsigned', $typeInfo );
			
			/* Is it zerofill? */
			$columnDefinition['zerofill'] = in_array( 'zerofill', $typeInfo );
			
			/* Is it binary? */
			$columnDefinition['binary'] = ( $row['Collation'] === 'utf8_bin' );
			
			/* Allow NULL values? */
			$columnDefinition['allow_null'] = ( $row['Null'] === 'YES' );
			
			/* Column default value */
			$columnDefinition['default'] = $row['Default'];
			if ( $columnDefinition['default'] === NULL and !$columnDefinition['allow_null'] and mb_strpos( $row['Extra'], 'auto_increment' ) === FALSE )
			{
				$columnDefinition['default'] = '';
			}
			
			/* Auto increment */
			$columnDefinition['auto_increment'] = mb_strpos( $row['Extra'], 'auto_increment' ) !== FALSE;
			
			/* Add it in the defintion */
			ksort( $columnDefinition );
			$definition['columns'][ $columnDefinition['name'] ] = $columnDefinition;
		}
		
		/* Look at table indexes */
		$indexes = array();
		$results = $this->db->get_results( "SHOW INDEXES FROM `{$prefix}" . esc_sql( $table ) . '`', ARRAY_A );
		
		foreach ( $results as $row )
		{
			$length = ( isset( $row['Sub_part'] ) AND ! empty( $row['Sub_part'] ) ) ? intval( $row['Sub_part'] ) : null;
			
			if( isset( $indexes[ $row['Key_name'] ] ) )
			{
				$indexes[ $row['Key_name'] ]['length'][] = $length;
				$indexes[ $row['Key_name'] ]['columns'][] = $row['Column_name'];
			}
			else
			{
				$type = 'key';
				if( $row['Key_name'] === 'PRIMARY' )
				{
					$type = 'primary';
				}
				elseif( $row['Index_type'] === 'FULLTEXT' )
				{
					$type = 'fulltext';
				}
				elseif( !$row['Non_unique'] )
				{
					$type = 'unique';
				}
				
				$indexes[ $row['Key_name'] ] = array(
					'type'		=> $type,
					'name'		=> $row['Key_name'],
					'length'	=> array( $length ),
					'columns'	=> array( $row['Column_name'] )
					);
			}
		}
		
		$definition['indexes'] = $indexes;
		
		/* Return */
		return $definition;
	}
	
	/**
	 * Build create table sql for wp dbDelta()
	 *
	 * @param	array		$data			Table definition data
	 * @param	bool		$ms_context		Whether the table should be created in the context of the primary site or the current multisite
	 * @return	string
	 */
	public function buildTableSQL( $data, $ms_context=FALSE )
	{
		$prefix = $ms_context ? $this->db->prefix : $this->db->base_prefix;
		$query = "CREATE TABLE {$prefix}{$data[ 'name' ]} (" . "\n";
		
		foreach( $data[ 'columns' ] as $column )
		{
			$query .= trim( $this->buildColumnSQL( $column ) ) . ",\n";
		}
		
		$index_count = count( $data[ 'indexes' ] );
		$i = 1;
		foreach( $data[ 'indexes' ] as $index )
		{
			$query .= $this->buildIndexSQL( $index );
			if ( $i < $index_count )
			{
				$query .= ",\n";
			}
			$i++;
		}
		
		$query .= "\n) " . $this->db->get_charset_collate();
		
		return $query;
	}
	
	/**
	 * Build column sql from definition data
	 *
	 * @param	array	$data		Column data
	 * @return	string
	 */
	public function buildColumnSQL( $data )
	{
		/* Specify name and type */
		$definition = "{$data['name']} " . \strtolower( $data['type'] );
		
		/* Some types specify length */
		if(
			in_array( \strtoupper( $data['type'] ), array( 'VARCHAR', 'VARBINARY' ) )
			or
			(
				isset( $data['length'] ) and $data['length']
				and
				in_array( \strtoupper( $data['type'] ), array( 'BIT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC', 'CHAR', 'BINARY' ) )
			)
		) {
			$definition .= "({$data['length']}";
			
			/* And some of those specify decimals (which may or may not be optional) */
			if( in_array( \strtoupper( $data['type'] ), array( 'REAL', 'DOUBLE', 'FLOAT' ) ) or ( in_array( \strtoupper( $data['type'] ), array( 'DECIMAL', 'NUMERIC' ) ) and isset( $data['decimals'] ) ) )
			{
				$definition .= ',' . $data['decimals'];
			}
			
			$definition .= ')';
		}
		
		$definition .= ' ';
		
		/* Numeric types can be UNSIGNED and ZEROFILL */
		if( in_array( \strtoupper( $data['type'] ), array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) )
		{
			if( isset( $data['unsigned'] ) and $data['unsigned'] === TRUE )
			{
				$definition .= 'UNSIGNED ';
			}
			if( isset( $data['zerofill'] ) and $data['zerofill'] === TRUE )
			{
				$definition .= 'ZEROFILL ';
			}
		}
		
		/* ENUM and SETs have values */
		if( in_array( \strtoupper( $data['type'] ), array( 'ENUM', 'SET' ) ) )
		{
			$values = array();
			foreach ( $data['values'] as $v )
			{
				$values[] = "'" . esc_sql( $v ) . "'";
			}
			
			$definition .= '(' . implode( ',', $values ) . ') ';
		}
				
		/* Some types can be binary or not */
		if( isset( $data['binary'] ) and $data['binary'] === TRUE and in_array( \strtoupper( $data['type'] ), array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT' ) ) )
		{
			$definition .= 'BINARY ';
		}
		
		/* Text types specify a character set and collation */
		if( in_array( \strtoupper( $data['type'] ), array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'ENUM', 'SET' ) ) )
		{
			//$definition .= "CHARACTER SET utf8 COLLATE utf8mb4_unicode_ci ";
		}
		
		/* Auto increment */
		if( isset( $data['auto_increment'] ) and $data['auto_increment'] === TRUE )
		{
			$definition .= 'AUTO_INCREMENT ';
		}
		/* Default value */
		else
		{
			/* Default value */
			if( isset( $data['default'] ) and !in_array( \strtoupper( $data['type'] ), array( 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BLOB', 'MEDIUMBLOB', 'BIGBLOB', 'LONGBLOB' ) ) )
			{
				if( $data['type'] == 'BIT' )
				{
					$definition .= "DEFAULT {$data['default']} ";
				}
				else
				{
					$defaultValue = in_array( \strtoupper( $data['type'] ), array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) ? floatval( $data['default'] ) : ( ! in_array( $data['default'], array( 'CURRENT_TIMESTAMP', 'BIT' ) ) ? '\'' . esc_sql( $data['default'] ) . '\'' : $data['default'] );
					$definition .= "DEFAULT {$defaultValue} ";
				}
			}
		}
		
		/* NULL? */
		if( isset( $data['allow_null'] ) and $data['allow_null'] === FALSE )
		{
			$definition .= 'NOT NULL ';
		}
		else
		{
			$definition .= 'NULL ';
		}
		
		
		/* Index? */
		if( isset( $data['primary'] ) )
		{
			$definition .= 'PRIMARY KEY ';
		}
		elseif( isset( $data['unique'] ) )
		{
			$definition .= 'UNIQUE ';
		}
		if( isset( $data['key'] ) )
		{
			$definition .= 'KEY ';
		}
		
		/* Return */
		return $definition;
	}
	
	/**
	 * Build index sql from definition data
	 *
	 * @param	array		$data		Index data
	 * @return	string
	 */
	public function buildIndexSQL( $data )
	{
		$definition = '';
		
		/* Specify type */
		switch ( \strtolower( $data['type'] ) )
		{
			case 'primary':
				$definition .= 'PRIMARY KEY  ';
				break;
				
			case 'unique':
				$definition .= "UNIQUE KEY `{$data['name']}` ";
				break;
				
			case 'fulltext':
				$definition .= "FULLTEXT KEY `{$data['name']}` ";
				break;
				
			default:
				$definition .= "KEY {$data['name']} ";
				break;
		}
		
		/* Specify columns */
		$definition .= '(' . implode( ',', array_map( function ( $val, $len )
		{
			return ( ! empty( $len ) ) ? "{$val} ({$len})" : "{$val}";
		}, 
		$data['columns'], ( ( isset( $data['length'] ) AND is_array( $data['length'] ) ) ? $data['length'] : array_fill( 0, count( $data['columns'] ), null ) ) ) ) . ')';
		
		/* Return */
		return $definition;
	}

	/**
	 * Check if a table exists
	 *
	 * @param	string		$table		Table name
	 * @param	bool		$ms_context		Whether the table should be created in the context of the primary site or the current multisite
	 * @return	bool
	 */
	public function tableExists( $table, $ms_context=FALSE )
	{
		$prefix = $ms_context ? $this->db->prefix : $this->db->base_prefix;
		return ( count( $this->db->get_results( "SHOW TABLES LIKE '". esc_sql( "{$prefix}{$table}" ) . "'" ) ) > 0 );
	}
}
