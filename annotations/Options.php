<?php
/**
 * Annotation: MWP\WordPress\Options  
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

if ( class_exists( 'MWP\WordPress\Options' ) ) {
	return;
}

/**
 * @Annotation 
 * @Target( "CLASS" )
 */
class Options extends \MWP\Framework\Annotation
{
    /**
     * @var string
     */
    public $menu;
	
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $capability = 'manage_options';
	
	/**
	 * Apply to Object
	 *
	 * @param	object		$instance		The object which is documented with this annotation
	 * @param	array		$vars			Persisted variables returned by previous annotations
	 * @return	array|NULL
	 */
	public function applyToObject( $instance, $vars=[] )
	{
		if ( $instance instanceof \MWP\Framework\Plugin\Settings )
		{
			$menu 	= $this->menu ?: $instance->getPlugin()->name;
			$title 	= $this->title ?: $menu . ' ' . __( 'Options' );
			$capability = $this->capability;
			$page_id = $instance->getStorageId();
			
			add_action( $instance->isNetworkGlobal && is_multisite() ? 'network_admin_menu' : 'admin_menu', function() use ( $menu, $title, $capability, $page_id, $instance ) {
				if ( $instance->isForNetwork ) {
					/* Implementation sourced from: https://vedovini.net/2015/10/04/using-the-wordpress-settings-api-with-network-admin-pages/ */

					/* Create settings page */
					add_submenu_page( 'settings.php', $title, $menu, $capability, $page_id, function() use ( $title, $page_id, $instance ) {
						echo $instance->getPlugin()->getTemplateContent( 'admin/settings/form', array( 
							'title' => $title, 
							'page_id' => $page_id, 
							'action' => 'edit.php?action=update_' . $page_id,
						));
					});

					/* Handle submitted settings form */
					add_action( 'network_admin_edit_update_' . $page_id, function() use ( $page_id ) {
						check_admin_referer( $page_id . '-options' );

						global $new_whitelist_options;
						$options = $new_whitelist_options[ $page_id ];

						foreach ( $options as $option ) {
							if ( isset( $_POST[ $option ] ) ) {
								update_site_option( $option, $_POST[ $option ] );
							}
						} 

						// Redirect back to our options page.
						wp_redirect( add_query_arg( array( 
							'page' => $page_id,
							'updated' => 'true'
						), network_admin_url('settings.php') ));
						
						exit;
					});

				} else {
					add_options_page( $title, $menu, $capability, $page_id, function() use ( $title, $page_id, $instance ) {
						echo $instance->getPlugin()->getTemplateContent( 'admin/settings/form', array( 
							'title' => $title, 
							'page_id' => $page_id,
							'action' => 'options.php',
						));
					});
				}
			});
			
			add_action( 'admin_init', function() use ( $page_id, $instance ) {
				register_setting( $page_id, $page_id, array( $instance, 'validate' ) );
			});
			
			return array( 'page_id' => $page_id );
		}		
	}
}
