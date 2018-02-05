<?php
/**
 * Annotation: Wordpress\Action  
 *
 * Created:    Nov 20, 2016
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.0.0
 */
 
namespace Wordpress;

/**
 * @Annotation 
 * @Target( "METHOD" )
 */
class Action extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 */
	public $for;
	
	/**
	 * @var integer
	 */
	public $priority = 10;
	
	/**
	 * @var integer
	 */
	public $args = 1;
	
	/**
	 * @var	bool
	 */
	public $output = false;
	
	/**
	 * Apply to Method
	 *
	 * @param	object					$instance		The object that the method belongs to
	 * @param	ReflectionMethod		$method			The reflection method of the object instance
	 * @param	array					$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToMethod( $instance, $method, $vars )
	{
		$callback = array( $instance, $method->name );
		
		// Auto output?
		if ( $this->output == true ) 
		{
			$callback = function() use ( $instance, $method ) {
				echo call_user_func_array( array( $instance, $method->name ), func_get_args() );
			};
		}
		
		mwp_add_action( $this->for, $callback, $this->priority, $this->args );
	}	
	
}
