<?php
/**
 * Annotation: MWP\WordPress\AdminPage
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

if ( class_exists( 'MWP\WordPress\AdminPage' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( { "METHOD", "CLASS" } )
 */
class AdminPage extends \MWP\Framework\Annotation
{
	/**
	 * @var string
	 * @Required
	 */
	public $title;
	
	/**
	 * @var string
	 * @Required
	 */
	public $menu;
	
	/**
	 * @var string
	 */
	public $menu_submenu;
	
	/**
	 * @var string
	 * @Required
	 */
	public $slug;
	
	/**
	 * @var mixed
	 */
	public $capability = 'manage_options';
	
	/**
	 * @var string
	 */
	public $icon = 'none';
	
	/**
	 * @var int
	 */
	public $position;
	
	/**
	 * @var string
	 */
	public $type = 'menu';
	
	/**
	 * @var string
	 */
	public $parent;
	
	/**
	 * @var string
	 */
	public $for = 'site';
	
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
		$annotation = $this;
		$page_callback = function() use ( $annotation, $instance, $method )
		{
			$add_page_func = 'add_' . $annotation->type . '_page';
			if ( is_callable( $add_page_func ) )
			{
				switch( $annotation->type ) {
					case 'menu':
						call_user_func( $add_page_func, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, array( $instance, $method->name ), $annotation->icon, $annotation->position );
						if ( $annotation->menu_submenu ) {
							add_submenu_page( $annotation->slug, $annotation->title, $annotation->menu_submenu, $annotation->capability, $annotation->slug, function(){} );
						}
						break;
					
					case 'submenu':
						call_user_func( $add_page_func, $annotation->parent, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, array( $instance, $method->name ), $annotation->position );
						break;
					
					default:
						call_user_func( $add_page_func, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, array( $instance, $method->name ), $annotation->position );
						break;
				}
			}
		};
		
		if ( in_array( $annotation->for, [ 'site', 'all' ] ) ) {
			mwp_add_action( 'admin_menu', $page_callback );
		}
		
		if ( in_array( $annotation->for, [ 'network', 'all' ] ) ) {
			mwp_add_action( 'network_admin_menu', $page_callback );
		}
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
		$annotation = $this;
		$page_callback = function() use ( $annotation, $instance ) 
		{
			$add_page_func = 'add_' . $annotation->type . '_page';
			if ( is_callable( $add_page_func ) )
			{
				$output = '';

				/* Output controller screen */
				$router_callback = function() use ( $instance, &$output ) {
					echo $output;
				};

				switch( $annotation->type ) {
					case 'menu':
						$page_hook = call_user_func( $add_page_func, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, $router_callback, $annotation->icon, $annotation->position );
						if ( $annotation->menu_submenu ) {
							add_submenu_page( $annotation->slug, $annotation->title, $annotation->menu_submenu, $annotation->capability, $annotation->slug, function(){} );
						}
						break;
					
					case 'submenu':
						$page_hook = call_user_func( $add_page_func, $annotation->parent, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, $router_callback, $annotation->position );
						break;
					
					default:
						$page_hook = call_user_func( $add_page_func, $annotation->title, $annotation->menu, $annotation->capability, $annotation->slug, $router_callback, $annotation->position );
						break;
				}
				
				/* Run Controller */
				add_action( 'load-' . $page_hook, function() use ( $instance, &$output ) { 
					ob_start();
					if ( is_callable( array( $instance, 'init' ) ) ) { 
						call_user_func( array( $instance, 'init' ) ); 
					} 
					$action = isset( $_REQUEST[ 'do' ] ) ? $_REQUEST[ 'do' ] : 'index';
					if( is_callable( array( $instance, 'do_' . $action ) ) ) {
						$output .= call_user_func( array( $instance, 'do_' . $action ) );
					} else {
						$output .= '<div class="notice notice-error"><p><strong>Controller Error:</strong><br><br>Implement a "do_' . $action . '()" method on this controller to generate the output of this page.</p></div>';
					}
					$buffered_output = ob_get_clean();
					$output = $buffered_output . $output;
				});
			}
		};
		
		if ( in_array( $annotation->for, [ 'site', 'all' ] ) ) {
			mwp_add_action( 'admin_menu', $page_callback );
		}
		
		if ( in_array( $annotation->for, [ 'network', 'all' ] ) ) {
			mwp_add_action( 'network_admin_menu', $page_callback );
		}
		
	}
	
}
