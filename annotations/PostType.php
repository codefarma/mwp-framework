<?php
/**
 * Annotation: Wordpress\PostType  
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
 * @Target( { "PROPERTY", "METHOD" } )
 */
class PostType extends \MWP\Framework\Annotation
{
    /**
     * @var string
     */
    public $name;
	
	/**
	 * Apply to Property
	 *
	 * @param	object					$instance		The object that the property belongs to
	 * @param	ReflectionProperty		$property		The reflection property of the object instance
	 * @param	array					$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToProperty( $instance, $property, $vars )
	{	
		$annotation = $this;
		add_action( 'init', function() use ( $annotation, $instance, $property )
		{
			/* Register Post Type */
			register_post_type( $annotation->name, $instance->{$property->name} );			
		});	
	}
	
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
		$annotation = $this;
		add_action( 'init', function() use ( $annotation, $instance, $method )
		{
			/* Register Post Type */
			register_post_type( $annotation->name, call_user_func( array( $instance, $method->name ) ) );			
		});	
	}
}
