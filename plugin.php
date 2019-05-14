<?php
/**
 * Plugin Name: MWP Application Framework for WordPress
 * Version: 2.1.7
 * Description: Provides an object oriented utility framework for building plugins/applications using WordPress.
 * Author: Kevin Carwile
 * Author URI: http://www.codefarma.com/
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
		if ( file_exists( $activating_plugin_path . '/framework/mwp-framework.php' ) ) {
			include_once $activating_plugin_path . '/framework/plugin.php';
		}
	}
	
	/**
	 * Framework initialization routine
	 *
	 * Only attempt to load the framework which is the most up to date after
	 * all plugins and themes have been included, and had a chance to report 
	 * their bundled framework version.
	 *
	 * Also: If we are in development mode, then we should never load any
	 * other version than the standalone copy.
	 *
	 * @return	void
	 */
	$framework_init = function() use ( $plugin_meta, &$_mwp_fw_latest )
	{
		// Let's always skip including bundled frameworks if we are in development
		$in_development = ( defined( 'MWP_FRAMEWORK_DEV' ) and \MWP_FRAMEWORK_DEV );
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
				require_once __DIR__ . '/vendor/autoload.php';
			}

			/* Include global functions */
			require_once 'includes/mwp-global-functions.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			/* Optional development config */
			if ( basename( __DIR__ ) == 'mwp-framework' and file_exists( __DIR__ . '/dev_config.php' ) ) {
				include_once __DIR__ . '/dev_config.php'; 
			}
			
			class MWPFramework
			{
				protected static $extensions = array();
				
				/**
				 * Register an extension directory
				 *
				 * @param	string			$namespace			The namespace used by classes in the directory
				 * @param	string			$path				The directory where the extensions are located
				 * @return	void
				 */
				public static function register_extensions( $namespace, $path ) {
					if ( $namespace and is_dir( $path ) ) {
						foreach( glob( $path . '/*.php' ) as $file ) {
							$bunchedClassname = substr( basename( $file ), 0, -4 );
							if ( $bunchedClassname ) {
								if ( ! isset( static::$extensions[ $bunchedClassname ] ) ) {
									static::$extensions[ $bunchedClassname ] = array();
								}
								
								static::$extensions[ $bunchedClassname ][] = array( 'namespace' => $namespace, 'file' => $file );
							}
						}
					}
				}
				
				/**
				 * Initialize framework and all plugins
				 * 
				 * @return	void
				 */
				public static function init()
				{
					/* Register extension directories */					
					foreach( apply_filters( 'mwp_framework_extension_dirs', array() ) as $dir ) {
						if ( isset( $dir['namespace'] ) and isset( $dir['path'] ) ) {
							static::register_extensions( $dir['namespace'], $dir['path'] );
						}
					}
					
					$extensions = static::$extensions;
					
					/* Extensible Class Autoloader */
					spl_autoload_register( function( $class ) use ( $extensions ) {
						$pieces = explode( '\\', $class );
						$classname = array_pop( $pieces );
						$namespace = implode( '\\', $pieces );
						if ( substr( $classname, 0, 1 ) != '_' ) {
							if ( class_exists( $namespace . '\\_' . $classname ) ) {
								$reflectionClass = new \ReflectionClass( $namespace . '\\_' . $classname );
								if ( ! $reflectionClass->isFinal() and ! $reflectionClass->isTrait() ) {
									$classOrInterface = $reflectionClass->isInterface() ? 'interface' : 'class';
									$classType = $reflectionClass->isAbstract() ? 'abstract' : '';
									$bunchedClassname = str_replace( '\\', '', $class );
									$latestClassname = $namespace . '\\_' . $classname;
									if ( isset( $extensions[ $bunchedClassname ] ) and is_array( $extensions[ $bunchedClassname ] ) ) {
										foreach( $extensions[ $bunchedClassname ] as $extension ) {
											if ( isset( $extension['namespace'] ) and isset( $extension['file'] ) and file_exists( $extension['file'] ) ) {
												eval( "namespace {$extension['namespace']}; {$classType} {$classOrInterface} _{$bunchedClassname} extends \\{$latestClassname} {}" );
												include_once $extension['file'];
												if ( class_exists( $extension['namespace'] . '\\' . $bunchedClassname ) ) {
													$latestClassname = $extension['namespace'] . '\\' . $bunchedClassname;
												}
											}
										}
									}
									eval( "namespace {$namespace}; {$classType} {$classOrInterface} {$classname} extends \\{$latestClassname} {}" );
								}
							}
						}
					});
					
					$annotationRegistry = 'Doctrine\Common\Annotations\AnnotationRegistry';
					$annotationRegistry::registerFile( __DIR__ . "/annotations/Inherit.php" );
					$annotationRegistry::registerFile( __DIR__ . "/annotations/Override.php" );
					$annotationRegistry::registerFile( __DIR__ . "/annotations/AdminPage.php" );
					$annotationRegistry::registerFile( __DIR__ . "/annotations/PostPage.php" );
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
	};
	
	/**
	 * Under normal conditions, the framework should init after the theme has been
	 * included to allow the theme to register extensions.
	 *
	 * But in the case of uninstalling the plugin, the framework will need to be
	 * manually initialized from the uninstall.php file.
	 */
	add_action( 'after_setup_theme', $framework_init, 1 );
	add_action( 'mwp_framework_manual_init', $framework_init );

});
