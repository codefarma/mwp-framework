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
	 * @var string
	 */
	public $location = 'after';

	/**
	 * @var string
	 */
	public $placeholder = '[insert_content]';
	
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
					$output = is_callable( array( $instance, 'do_' . $action ) ) ? 
						call_user_func( array( $instance, 'do_' . $action ) ) :
						'<strong>Controller Error:</strong><br><br>Implement a "do_' . $action . '()" method on this controller to generate the output of this page.</p>';
					
					$output = ob_get_clean() . $output;
					
					/* Callback to return our controller output */
					$return_output = function( $content ) use ( $annotation, $output, &$return_output ) 
					{
						/**
						 * Try to protect against incorrectly returning our controller output when code uses 'the_content' filter for other 
						 * posts within the context of our controller page (such as widgets that use WP_Query loops)
						 */
						if ( $post = get_post() and $post->ID == $annotation->post_id ) { // double check
							/* Moreover, try to protect against if this post happens to appear within one of those said loops */
							if ( is_singular() && is_main_query() ) {
								/* Output the content */
								switch( $annotation->location ) {
									case 'after':
										return $content . $output;

									case 'before':
										return $output . $content;

									case 'replace':
										return $output;

									case 'insert':
										return str_replace($annotation->placeholder, $output, $content);
								}

								return 'Invalid location specified in PostPage config. Please use one of (after, before, replace, insert)';
							}
						}
						
						return $content;
					};
					
					/* Add the filter to control the page content output */
					add_filter( 'the_content', $return_output );
				}
			});
		}
		
	}
	
}
