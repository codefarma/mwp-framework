<?php
/**
 * Annotation: MWP\WordPress\RestRoute
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

if ( class_exists( 'MWP\WordPress\RestRoute' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( { "METHOD" } )
 */
class RestRoute extends \MWP\Framework\Annotation
{	

	/**
	 * @var	string
	 */
	public $namespace;

	/**
	 * @var	string
	 */
	public $methods = "GET";
	 
	/**
	 * @var	string
	 * @Required
	 */
	public $route;

	/**
	 * @var	array
	 */
	public $args = array();

	/**
	 * @var	string
	 */
	public $permission_callback;

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
		mwp_add_action( 'rest_api_init', function() use ( $annotation, $instance, $method )
		{
			/* Use namespace provided in annotation, or use instance property, or use plugin slug, or default to 'mwp' */
			$namespace = $annotation->namespace ?: 
				( $instance->rest_namespace ?: 
					( is_callable( array( $instance, 'getPlugin' ) ) ? $instance->getPlugin()->pluginSlug() :
						'mwp' ) );
			
			$args = $annotation->args;
			foreach( $args as &$arg )
			{
				if ( isset( $arg['sanitize_callback'] ) ) {
					if ( is_callable( array( $instance, $arg['sanitize_callback'] ) ) ) {
						$arg['sanitize_callback'] = array( $instance, $arg['sanitize_callback'] );
					}
				}
				if ( isset( $arg['validate_callback'] ) ) {
					if ( is_callable( array( $instance, $arg['validate_callback'] ) ) ) {
						$arg['validate_callback'] = array( $instance, $arg['validate_callback'] );
					}
				}
			}
			
			$permission_callback = $annotation->permission_callback;
			if ( ! empty( $permission_callback ) ) {
				if ( is_callable( array( $instance, $permission_callback ) ) ) {
					$permission_callback = array( $instance, $permission_callback );
				}
			}
			
			register_rest_route( $namespace, $annotation->route, array(
				'methods'             => $annotation->methods,
				'callback'            => array( $instance, $method->name ),
				'args'                => $args,
				'permission_callback' => $permission_callback,
			));
		});
	}
	
}
