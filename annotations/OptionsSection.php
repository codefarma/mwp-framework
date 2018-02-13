<?php
/**
 * Annotation: MWP\WordPress\Options\Section  
 *
 * Created:    Feb 9, 2018
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      2.0.0
 */

namespace MWP\WordPress\Options;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( class_exists( 'MWP\WordPress\Options\Section' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( "CLASS" )
 */
class Section extends \MWP\Framework\Annotation
{	
	/**
	 * @var string
	 * @Required
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $description;
    
	/**
	 * Apply to Object
	 *
	 * @param	object		$instance		The object which is documented with this annotation
	 * @param	array		$vars			Persisted variables returned by previous annotations
	 * @return	void
	 */
	public function applyToObject( $instance, $vars )
	{
		extract( $vars );
		
		if ( $instance instanceof \MWP\Framework\Plugin\Settings and isset( $page_id ) )
		{
			$section_id = md5( $this->title );
			$self = $this;
			add_action( 'admin_init', function() use ( $instance, $section_id, $page_id, $self )
			{
				add_settings_section( $section_id, $self->title, function() use ( $instance, $self ) { 
					if ( $self->description ) {
						if ( is_callable( array( $instance, $self->description ) ) ) {
							echo call_user_func( array( $instance, $self->description ) );
						} else {
							echo $self->description;
						}
					}
				}, $page_id );
			});
			
			return array( 'section_id' => $section_id );
		}
	}
	
}
