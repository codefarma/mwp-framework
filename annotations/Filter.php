<?php
/**
 * Annotation: MWP\WordPress\Filter  
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

if ( class_exists( 'MWP\WordPress\Filter' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( "METHOD" )
 */
class Filter extends \MWP\Framework\Annotation
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
	 * Apply to Method
	 *
	 * @param	object					$instance		The object that the method belongs to
	 * @param	ReflectionMethod		$method			The reflection method of the object instance
	 * @param	array					$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToMethod( $instance, $method, $vars=[] )
	{
		add_filter( $this->for, array( $instance, $method->name ), $this->priority, $this->args );
	}	
	
}
