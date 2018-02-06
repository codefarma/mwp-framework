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
