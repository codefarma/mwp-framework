<?php
/**
 * Annotation: MWP\WordPress\Shortcode  
 *
 * Created:    Feb 9, 2018
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      2.0.0
 */

namespace MWP\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( class_exists( 'MWP\WordPress\Shortcode' ) ) {
	return;
}

use MWP\Framework\Framework;

/**
 * @Annotation 
 * @Target( { "METHOD", "CLASS" } )
 */
class Shortcode extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * Apply to Method
	 *
	 * @param	object					$instance		The object that the method belongs to
	 * @param	ReflectionMethod		$method			The reflection method of the object instance
	 * @param	array					$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToMethod( $instance, $method, $vars=[] )
	{
		add_shortcode( $this->name, array( $instance, $method->name ) );		
	}
	
	/**
	 * Apply to Object
	 *
	 * @param	object		$instance		The object which is documented with this annotation
	 * @param	array		$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToObject( $instance, $vars=[] )
	{
		/* Run Controller */
		add_shortcode( $this->name, function( $atts, $content, $name ) use ( $instance ) { 
			ob_start();
			$action = Framework::instance()->getRequest()->get( 'do', 'index' );
			if( is_callable( array( $instance, 'do_' . $action ) ) ) {
				$output = call_user_func( array( $instance, 'do_' . $action ), $atts, $content, $name );
			} else {
				$output = '<strong>Controller Error:</strong> Implement a "do_' . $action . '()" method on this controller to generate the output of this page.</p>';
			}
			$buffered_output = ob_get_clean();
			
			return $buffered_output . $output;
		});		
	}	
	
}
