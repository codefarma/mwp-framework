<?php
/**
 * Annotation: MWP\Annotations\Inherit  
 *
 * Created:    Feb 9, 2018
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      2.0.0
 */
 
namespace MWP\Annotations;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( class_exists( 'MWP\WordPress\Action' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( { "METHOD", "CLASS", "PROPERTY" } )
 */
class Inherit extends \MWP\Framework\Annotation
{

}
