<?php
/**
 * Form template file
 *
 * Created:   April 3, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/* Container id */
if ( ! empty( $id ) ) 
{
	echo "id=\"{$view->escape($id)}\" ";
}

/* Container attributes */
foreach ( $attr as $k => $v) 
{
	if ( in_array( $k, array( 'placeholder', 'title' ), true ) ) 
	{
		printf( '%s="%s" ', $view->escape( $k ), $view->escape( false !== $translation_domain ? $view[ 'translator' ]->trans( $v, array(), $translation_domain ) : $v ) );
	}
	elseif ( $v === true ) 
	{
		printf( '%s="%s" ', $view->escape( $k ), $view->escape( $k ) );
	}
	elseif ( $v !== false ) 
	{
		if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $view->escape( $k ), $view->escape( $v ) );
	}
}

