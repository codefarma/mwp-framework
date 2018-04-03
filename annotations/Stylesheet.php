<?php
/**
 * Annotation: MWP\WordPress\Stylesheet  
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

if ( class_exists( 'MWP\WordPress\Stylesheet' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( "PROPERTY" )
 */
class Stylesheet extends \MWP\Framework\Annotation
{
	/**
	 * @var array
	 */
	public $deps = array();
	
	/**
	 * @var mixed
	 */
	public $ver = false;
	
	/**
	 * @var string
	 */
	public $media = 'all';
	
	/**
	 * @var boolean
	 */
	public $always = false;
    
	/**
	 * @var	string
	 */
	public $handle;
	
	/**
	 * Apply to Property
	 *
	 * @param	object					$instance		The object that the property belongs to
	 * @param	ReflectionProperty		$property		The reflection property of the object instance
	 * @param	array					$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToProperty( $instance, $property, $vars=[] )
	{	
		if ( $instance instanceof \MWP\Framework\Plugin or is_callable( array( $instance, 'getPlugin' ) ) )
		{
			$plugin = ( $instance instanceof \MWP\Framework\Plugin ) ? $instance : $instance->getPlugin();
			$fileUrl = $plugin->fileUrl( $instance->{$property->name} );
			$annotation = $this;
			
			$register_callback = function() use ( $plugin, $fileUrl, $annotation, $instance, $property )
			{
				if ( isset( $annotation->handle ) )
				{
					wp_register_style( $annotation->handle, $fileUrl, $annotation->deps, $annotation->ver, $annotation->media );
					$plugin::$scriptHandles[ md5( $fileUrl ) ] = $annotation->handle;
				}
				else
				{
					wp_register_style( md5( $fileUrl ), $fileUrl, $annotation->deps, $annotation->ver, $annotation->media );
				}
				
				if ( $annotation->always )
				{
					$plugin->useStyle( $instance->{$property->name} );
				}
			};
			
			mwp_add_action( 'wp_enqueue_scripts', $register_callback );
			mwp_add_action( 'admin_enqueue_scripts', $register_callback );
			mwp_add_action( 'login_enqueue_scripts', $register_callback );
		}
	}
	
}
