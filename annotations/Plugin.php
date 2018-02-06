<?php
/**
 * Annotation: WordPress\Plugin  
 *
 * Created:    Nov 20, 2016
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.0.0
 */

namespace WordPress;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( class_exists( 'WordPress\Plugin' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( "METHOD" )
 */
class Plugin extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 * @Required
	 */
	public $on;
	
	/**
	 * @var string
	 */
	public $file = 'plugin.php';
	
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
		if ( $instance instanceof \MWP\Framework\Plugin or is_callable( array( $instance, 'getPlugin' ) ) )
		{
			$plugin = ( $instance instanceof \MWP\Framework\Plugin ) ? $instance : $instance->getPlugin();
			
			switch( $this->on )
			{
				case 'activation':
					register_activation_hook( $plugin->pluginSlug() . '/' . $this->file, array( $instance, $method->name ) );
					break;
					
				case 'deactivation':
					register_deactivation_hook( $plugin->pluginSlug() . '/' . $this->file, array( $instance, $method->name ) );
					break;
				
				case 'uninstall':
					register_uninstall_hook( $plugin->pluginSlug() . '/' . $this->file, array( $instance, $method->name ) );
					break;
			}
		}
	}
	
}
