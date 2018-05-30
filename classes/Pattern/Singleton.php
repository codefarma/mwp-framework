<?php
/**
 * Singleton  
 *
 * Created:    Nov 20, 2016
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.0.0
 */

namespace MWP\Framework\Pattern;

/**
 * Implements singleton design pattern
 */
abstract class _Singleton
{
	/**
	 * @var	Instance Cache
	 */
	protected static $_instance = [];
	
	/**
     * Protected constructor to prevent creating a new instance
     * from outside of this class.
     */
    protected function __construct() 
	{
	}
	
	/**
     * Private clone method to prevent cloning
     *
     * @return void
     */
    private function __clone()
    {
    }
	
	/**
	 * Initialize
	 *
	 * @return    void
	 */
	protected function constructed()
	{
		// initialization routine when the instance is first created
	}
		
	/**
	 * Create Instance
	 *
	 * @return	self
	 */
	public static function instance()
	{
		/* Allow this pattern to be extended without overriding the $_instance property */
		if ( is_array( static::$_instance ) ) {
			if ( isset( static::$_instance[ get_called_class() ] ) ) {
				return static::$_instance[ get_called_class() ];
			} else {
				static::$_instance[ get_called_class() ] = new static;
				static::$_instance[ get_called_class() ]->constructed();
				return static::$_instance[ get_called_class() ];
			}
		}
		
		if ( static::$_instance === NULL ) {
			static::$_instance = new static;
			static::$_instance->constructed();
		}
		
		return static::$_instance;
	}
}
