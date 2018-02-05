<?php
/**
 * Annotation: Wordpress\AjaxHandler   
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
class AjaxHandler extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 */
	public $action;
	
	/**
	 * @var array
	 */
	public $for = array( 'users', 'guests' );
	
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
		if ( in_array( 'users', $this->for ) )
		{
			add_action( 'wp_ajax_' . $this->action, array( $instance, $method->name ) );
		}
		
		if ( in_array( 'guests', $this->for ) )
		{
			add_action( 'wp_ajax_nopriv_' . $this->action, array( $instance, $method->name ) );
		}
	}	
	
}
