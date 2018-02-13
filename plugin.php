<?php
/**
 * Plugin Name: MWP Application Framework for WordPress
 * Version: 2.0.0
 * Description: Provides an object oriented utility framework for building plugins/applications using WordPress.
 * Author: Kevin Carwile
 * Author URI: http://www.millermedia.io/
 * License: GPL2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/* Optional development config */
if ( basename( __DIR__ ) == 'mwp-framework' and file_exists( __DIR__ . '/dev_config.php' ) ) {
	include_once __DIR__ . '/dev_config.php'; 
}

/**
 * Executing code in an anonymous function to scope the variables
 *
 * @return	void
 */
call_user_func( function() {

	global $_mwp_fw_latest;
	$plugin_meta = array();
	
	/**
	 * Keep the $_mwp_fw_latest global variable up to date with the most recent framework version found
	 */
	if ( file_exists( __DIR__ . '/data/plugin-meta.php' ) ) {
		$data = include __DIR__ . '/data/plugin-meta.php';
		$plugin_meta = json_decode( $data, true );
		if ( isset( $plugin_meta[ 'version' ] ) ) {
			if ( empty( $_mwp_fw_latest ) or version_compare( $_mwp_fw_latest, $plugin_meta[ 'version' ] ) === -1 ) {
				$_mwp_fw_latest = $plugin_meta[ 'version' ];
			}
		}
	}
	
	/** 
	 * When activating a new plugin, check if it has a bundled mwp application framework and attempt
	 * to include it now because it might resolve as the most current version, and be needed for the plugin
	 * to activate successfully.
	 */
	global $pagenow;
	if ( $pagenow == 'plugins.php' and isset( $_REQUEST['action'] ) and $_REQUEST['action'] == 'activate' and ! did_action( 'plugins_loaded' ) ) {
		$activating_plugin = $_REQUEST['plugin'];
		$activating_plugin_path = dirname( WP_PLUGIN_DIR . '/' . plugin_basename( trim( $activating_plugin ) ) );
		if ( file_exists( $activating_plugin_path . '/framework/mwp-framework.php' ) )
		{
			include_once $activating_plugin_path . '/framework/plugin.php';
		}
	}
	
	/**
	 * Only attempt to load the framework which is the most up to date after
	 * all plugins have had a chance to report their bundled framework version.
	 *
	 * Also: If we are in mwp development mode, then we should never load any
	 * other version than that.
	 *
	 * @return	void
	 */
	add_action( 'plugins_loaded', function() use ( $plugin_meta, &$_mwp_fw_latest )
	{
		// Let's always skip including bundled frameworks if we are in development
		$in_development = ( defined( 'MWP_FRAMEWORK_DEBUG' ) and \MWP_FRAMEWORK_DEBUG );
		if ( $in_development and basename( __DIR__ ) != 'mwp-framework' ) {
			return;
		}

		// Let's skip loading framework versions that are not the newest we know we have, unless we are in development
		if (
			! $in_development and
			! empty( $_mwp_fw_latest ) and 
			version_compare( $_mwp_fw_latest, $plugin_meta[ 'version' ] ) === 1 
		)
		{
			return;
		}

		/* Load Only Once, Ever */
		if ( ! class_exists( 'MWPFramework' ) )
		{
			/* Include packaged autoloader if present */
			if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
				require_once 'vendor/autoload.php';
			}
			
			/* Include global functions */
			require_once 'includes/mwp-global-functions.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			/* Optional development config */
			if ( basename( __DIR__ ) == 'mwp-framework' and file_exists( __DIR__ . '/dev_config.php' ) ) {
				include_once __DIR__ . '/dev_config.php'; 
			}
			
			$annotationRegistry = 'Doctrine\Common\Annotations\AnnotationRegistry';
			$annotationRegistry::registerFile( __DIR__ . "/annotations/AdminPage.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/AjaxHandler.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Plugin.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Action.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Filter.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/MetaBox.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Shortcode.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Options.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/OptionsSection.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/OptionsField.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/PostType.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/RestRoute.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Stylesheet.php" );
			$annotationRegistry::registerFile( __DIR__ . "/annotations/Script.php" );

			class MWPFramework
			{
				public static function init()
				{
					/* FAAP: Framework As A Plugin :) */
					$framework = \MWP\Framework\Framework::instance();		
					$framework->setPath( rtrim( plugin_dir_path( __FILE__ ), '/' ) );
					$framework->attach( $framework );
					
					if ( is_admin() ) {
						$framework->attach( \MWP\Framework\Controller\Tasks::instance() );
					}
					
					$ajaxHandlers = \MWP\Framework\AjaxHandlers::instance();
					$framework->attach( $ajaxHandlers );

					$settings = \MWP\Framework\Settings::instance();
					$framework->addSettings( $settings );
					$framework->attach( $settings );
					
					do_action( 'mwp_framework_init', $framework );
				}		
			}

			MWPFramework::init();
		}
	}, 1 );

});
