<?php
/**
 * MWP Application Framework Global Functions
 *
 * @package		MWP Application Framework
 * @author		Kevin Carwile
 * @since		Dec 10, 2016
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Common entry point to wordpress add_action
 *
 * @param	string			$action			The action that the callback should be executed for
 * @param	callable		$callback		String, array, or function that can be called back
 * @param	int				$priority		The callback prioirty
 * @param	int				$args			The number of arguments the callback should receive
 * @return	true
 */
if ( ! function_exists( 'mwp_add_action' ) ) 
{
	function mwp_add_action( $action, $callback, $priority=10, $args=1 )
	{
		/* Allow other plugins to decorate or modify this hook */
		$action_params = apply_filters( 'mwp_action_' . $action, array(
			'callback'  => $callback,
			'action'    => $action,
			'priority'  => $priority,
			'args'      => $args,
		) );
		
		if ( $priority == -10 ) {
			print_r( $action_params );
			exit;
		}
		return add_action( $action_params[ 'action' ], $action_params[ 'callback' ], $action_params[ 'priority' ], $action_params[ 'args' ] );
	}
}

/**
 * Get the url to access a particular menu page based on the slug it was registered with
 * on the network admin in a multisite install.
 *
 * If the slug hasn't been registered properly no url will be returned
 *
 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu)
 * @param bool $echo Whether or not to echo the url - default is true
 * @return string the url
 */
if ( ! function_exists( 'network_menu_page_url' ) ) {
	function network_menu_page_url( $menu_slug, $echo = true ) {
		global $_parent_pages;
		if ( isset( $_parent_pages[$menu_slug] ) ) {
			$parent_slug = $_parent_pages[$menu_slug];
			if ( $parent_slug && ! isset( $_parent_pages[$parent_slug] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = network_admin_url( 'admin.php?page=' . $menu_slug );
		}

		$url = esc_url( $url );

		if ( $echo )
			echo $url;

		return $url;
	}	
}
