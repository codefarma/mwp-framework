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
abstract class Singleton
{
	/**
	 * @var	Instance Cache
	 */
	protected static $_instance;
	
	/**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct() 
	{
	}
	
	/**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }
	
	/**
	 * Create Instance
	 *
	 * @return	self
	 */
	public static function instance()
	{
		if ( static::$_instance === NULL )
		{
			$classname = get_called_class();
			static::$_instance = new $classname;
		}
		
		return static::$_instance;
	}
}
