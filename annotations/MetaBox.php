<?php
/**
 * Annotation: MWP\WordPress\MetaBox
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

if ( class_exists( 'MWP\WordPress\MetaBox' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( { "METHOD" } )
 */
class MetaBox extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 * @Required
	 */
	public $id; 
	 
	/**
	 * @var string
	 * @Required
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $screen;
	
	/**
	 * @var string
	 */
	public $context = 'advanced';
	
	/**
	 * @var string
	 */
	public $priority = 'default';
	
	/**
	 * @var mixed
	 */
	public $callback_args;
	
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
		mwp_add_action( 'add_meta_boxes', function() use ( $annotation, $instance, $method )
		{
			$callback_args = null;
			
			if ( isset( $this->callback_args ) )
			{
				$callback_args = (array) $this->callback_args;
				
				if ( is_string( $this->callback_args ) and is_callable( array( $instance, $this->callback_args ) ) )
				{
					$callback_args = call_user_func( array( $instance, $this->callback_args ), $annotation );
				}
			}
			
			add_meta_box( $annotation->id, $annotation->title, array( $instance, $method->name ), $annotation->screen, $annotation->context, $annotation->priority, $callback_args );
		});
	}
	
}
