<?php
/**
 * Annotation: MWP\WordPress\PostPage
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

if ( class_exists( 'MWP\WordPress\PostPage' ) ) {
	return;
}

use MWP\Framework\Framework;

/**
 * @Annotation 
 * @Target( { "CLASS" } )
 */
class PostPage extends \MWP\Framework\Annotation
{
	/**
	 * @var int
	 */
	public $post_id;
	
	/**
	 * @var	string
	 */
	public $post_getter = 'getPost';
	
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
		if ( is_callable( array( $instance, $this->post_getter ) ) ) {
			$post_id = call_user_func( array( $instance, $this->post_getter ) );
			if ( $post_id instanceof WP_Post ) {
				$post_id = $post_id->ID;
			}
			$this->post_id = $post_id;
		}

		if ( $this->post_id ) {
			add_action( 'wp', function( $wp ) use ( $annotation, $instance ) {
				if ( $post = get_post() and $post->ID == $annotation->post_id ) {
					/* Initialize the controller early when viewing the post page */
					if ( is_callable( array( $instance, 'init' ) ) ) { 
						call_user_func( array( $instance, 'init' ) ); 
					}
					
					ob_start();
					$action = Framework::instance()->getRequest()->get( 'do', 'index' );
					if( is_callable( array( $instance, 'do_' . $action ) ) ) {
						$output = call_user_func( array( $instance, 'do_' . $action ) );
					} else {
						$output = '<strong>Controller Error:</strong><br><br>Implement a "do_' . $action . '()" method on this controller to generate the output of this page.</p>';
					}
					$buffered_output = ob_get_clean();
					
					$output = $buffered_output . $output;
					
					/* Add the filter to control the page content output */
					add_filter( 'the_content', function( $content ) use ( $output ) {
						if( is_singular() && is_main_query() ) {
							return $output;
						}
						
						return $content;
					});
				}
			});
		}
		
	}
	
}
