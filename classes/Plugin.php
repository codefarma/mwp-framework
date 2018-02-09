<?php
/**
 * Plugin Base Class (Singleton)
 *
 * Created:    Nov 20, 2016
 *
 * @package   MWP Application Framework
 * @author    Kevin Carwile
 * @since     1.0.0
 */

namespace MWP\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Framework;
use MWP\Framework\Pattern\Singleton;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Templating\DelegatingEngine;

/**
 * All mwp application framework plugins should extend this class.
 */
abstract class Plugin extends Singleton
{
	/**
	 * @var string 	Plugin path
	 */
	protected $path;
	
	/**
	 * @var array 	Settings cache
	 */
	protected $settings = array();
	
	/**
	 * @var array	Script Handles Cache
	 */
	public static $scriptHandles = array();	 
	
	/**
	 * @var array	Script Handles Cache
	 */
	public static $styleHandles = array();
	
	/** 
	 * Set the base plugin path
	 *
	 * @api
	 *
	 * @param	string		$path		The plugin base path
	 * @return	void
	 */
	public function setPath( $path )
	{
		$this->path = $path;
	}
	
	/**
	 * Get the base plugin path
	 *
	 * @api
	 *
	 * @return	string
	 */
	public function getPath()
	{
		return $this->path;
	}
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		return $this;
	}
	
	/**
	 * Check if plugin version has been updated
	 *
	 * @Wordpress\Action( for="init" )
	 *
	 * @return	void
	 */
	public function _versionUpdateCheck()
	{
		if ( ! defined('DIR_TESTDATA') and ! Framework::instance()->isDev() )
		{
			$plugin_meta = $this->data( 'plugin-meta' );
			if ( is_array( $plugin_meta ) and isset( $plugin_meta[ 'version' ] ) and $plugin_meta[ 'version' ] )
			{
				$install = $this->data( 'install-meta' );
				if ( ! is_array( $install ) or version_compare( $install[ 'version' ], $plugin_meta[ 'version' ] ) == -1 )
				{
					update_site_option( 'mwp_cache_latest', time() );
					$this->versionUpdated();
				}
			}
		}
	}
	
	/**
	 * Run updates when new plugin version is uploaded
	 *
	 * @return	void
	 */
	public function versionUpdated()
	{
		$plugin_meta = $this->data( 'plugin-meta' );
		$build_meta = $this->data( 'build-meta' );
		$install = $this->data( 'install-meta' ) ?: array();
		
		/* Update table definitions in database if needed */
		$this->updateSchema();
		
		/* Update installed version number */
		$install[ 'version' ] = $plugin_meta[ 'version' ];
		
		/* Update install meta */
		$this->setData( 'install-meta', $install );
		
		/* Clear the annotations cache */
		Framework::instance()->clearAnnotationsCache();
	}

	/**
	 * Update the schema for this plugin
	 * 
	 * @param	bool		$execute		Whether to execute the database changes or not
	 * @return	array						Strings containing the results of the various update queries
	 */
	public function updateSchema( $execute=TRUE )
	{
		global $wpdb;
		$build_meta = $this->data( 'build-meta' );
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$dbHelper = \MWP\Framework\DbHelper::instance();
		
		$delta_updates = array();
		
		/* Update global table definitions in database if needed */
		if ( isset( $build_meta[ 'tables' ] ) and is_array( $build_meta[ 'tables' ] ) )
		{
			foreach( $build_meta[ 'tables' ] as $table )
			{
				$tableSql = $dbHelper->buildTableSQL( $table, FALSE );
				$updates = dbDelta( $tableSql, $execute );
				if ( $updates ) {
					$delta_updates[ $wpdb->base_prefix . $table['name'] ] = $updates;
				}
			}
		}
		
		/* Update multisite specific table definitions in database if needed */
		if ( isset( $build_meta[ 'ms_tables' ] ) and is_array( $build_meta[ 'ms_tables' ] ) )
		{
			if ( function_exists( 'is_multisite' ) and is_multisite() )
			{
				// Update tables in site specific contexts
				$sites_func = function_exists( 'get_sites' ) ? 'get_sites' : 'wp_get_sites';
				if ( function_exists( $sites_func ) )
				{
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
					
					$sites = call_user_func( $sites_func );
					$plugin_path = $this->pluginSlug() . '/plugin.php';
					
					foreach( $sites as $site )
					{
						$site_data = (array) $site;
						$site_id = (int) $site_data['blog_id'];
						switch_to_blog( $site_id );
						
						if ( is_plugin_active_for_network( $plugin_path ) or is_plugin_active( $plugin_path ) )
						{
							// Create tables for this site
							foreach( $build_meta[ 'ms_tables' ] as $table )
							{
								$tableSql = $dbHelper->buildTableSQL( $table, TRUE );
								$updates = dbDelta( $tableSql, $execute );
								if ( $updates ) {
									$delta_updates[ $wpdb->prefix . $table ] = $updates;
								}
							}
						}
						
						restore_current_blog();
					}
				}
			}
			else
			{
				// Update tables under global context
				foreach( $build_meta[ 'ms_tables' ] as $table )
				{
					$tableSql = $dbHelper->buildTableSQL( $table, FALSE );
					$updates = dbDelta( $tableSql, $execute );
					if ( $updates ) {
						$delta_updates[ $wpdb->base_prefix . $table ] = $updates;
					}					
				}
			}
		}
		
		return $delta_updates;
	}
	
	/**
	 * Uninstall routine
	 *
	 * @return	void
	 */
	public function uninstall()
	{
		$build_meta = $this->data( 'build-meta' ) ?: array();
		$db = Framework::instance()->db();
		
		// Remove global tables on uninstall
		if ( is_array( $build_meta[ 'tables' ] ) ) {
			foreach( $build_meta[ 'tables' ] as $table ) {
				$db->query( "DROP TABLE IF EXISTS {$db->base_prefix}{$table['name']}" );
			}
		}
		
		// Remove multisite specific tables on uninstall
		if ( isset( $build_meta[ 'ms_tables' ] ) and is_array( $build_meta[ 'ms_tables' ] ) )
		{
			if ( function_exists( 'is_multisite' ) and is_multisite() )
			{
				// Update tables in site specific contexts
				$sites_func = function_exists( 'get_sites' ) ? 'get_sites' : 'wp_get_sites';
				if ( function_exists( $sites_func ) )
				{
					$sites = call_user_func( $sites_func );
					
					foreach( $sites as $site )
					{
						$site_data = (array) $site;
						switch_to_blog( (int) $site_data['blog_id'] );
						
						// Drop tables
						foreach( $build_meta[ 'ms_tables' ] as $table )
						{
							$db->query( "DROP TABLE IF EXISTS {$db->prefix}{$table['name']}" );
						}
						
						restore_current_blog();
					}
				}
			}
			else
			{
				// Update tables under global context
				foreach( $build_meta[ 'ms_tables' ] as $table )
				{
					$db->query( "DROP TABLE IF EXISTS {$db->base_prefix}{$table['name']}" );
				}
			}
		}		
		
		$this->setData( 'install-meta', NULL );
	}
	
	/**
	 * Add a settings store to the plugin
	 *
	 * @api
	 *
	 * @param	Settings		$settings		The settings object
	 * @return	void
	 */
	public function addSettings( $settings )
	{
		$this->settings[ $settings->key ] = $settings;
		$settings->setPlugin( $this );
	}
	
	/** 
	 * Get one or more plugin settings stores
	 *
	 * @api
	 *
	 * @param	string|NULL		$key		The settings access key
	 * @return	Settings|array|NULL
	 */
	public function getSettings( $key='main' )
	{
		/* Return a specific settings id */
		if ( $key !== NULL ) {
			if ( isset( $this->settings[ $key ] ) ) {
				return $this->settings[ $key ];
			}
			
			return NULL;
		}
		
		/* Return all settings */
		return $this->settings;
	}
	
	/**
	 * Get a plugin setting by name (search if necessary)
	 *
	 * @api
	 *
	 * @param	string		$name		Setting name
	 * @param	string		$key		Optional key of settings to look in
	 * @return	mixed|NULL
	 */
	public function getSetting( $name, $key=NULL )
	{
		/* Get from specific settings page */
		if ( $key !== NULL ) {
			if ( $settings = $this->getSettings( $key ) ) {
				return $settings->getSetting( $name );
			}
			
			return NULL;
		}
		
		/* Search all settings */
		foreach( $this->getSettings( NULL ) as $settings ) {
			$value = $settings->getSetting( $name );
			if ( $value !== NULL ) {
				return $value;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Set the value of a settings option
	 *
	 * @api
	 *
	 * @param	string		$name			Setting name
	 * @param	mixed		$val			Setting value
	 * @param	string		$key			Settings key
	 * @return	Settings
	 * @throws	ErrorException
	 */
	public function setSetting( $name, $val, $key='main' )
	{
		if ( $settings = $this->getSettings( $key ) )
		{
			$settings->setSetting( $name, $val );
			return $settings;
		}
		else
		{
			throw new ErrorException( 'Invalid settings key "' . $key . '". Does not exist!' );
		}
	}
	
	/**
	 * Get data from persistent store
	 * 
	 * @api
	 *
	 * @param	string		$key		The data key to load
	 * @param	string|NULL	$subdir		The subdirectory to load the data key from or NULL for base /data dir
	 * @return	mixed|NULL
	 */
	public function getData( $key, $subdir=NULL )
	{
		if ( file_exists( $this->getPath() . '/data/' . $key . '.php' ) )
		{
			$data = include $this->getPath() . '/data/' . $key . '.php';
			if ( $data )
			{
				return json_decode( $data, TRUE );
			}
		}
		
		return NULL;
	}
	
	/**
	 * Alias for getData()
	 *
	 * @param	string		$key		The data key to load
	 * @param	string|NULL	$subdir		The subdirectory to load the data key from or NULL for base /data dir
	 * @return	mixed|NULL
	 */ 
	public function data( $key, $subdir=NULL )
	{
		return $this->getData( $key, $subdir );
	}
	
	/**
	 * Save data to persistent store
	 *
	 * @api
	 *
	 * @param	string		$key		The data key to save
	 * @param	mixed		$data		The data to save
	 * @param	string|NULL	$subdir		The subdirectory to save the data to or NULL for base /data dir
	 * @return	mixed|NULL
	 * @throws 	\ErrorException
	 */
	public function setData( $key, $data, $subdir=NULL )
	{
		$data_dir = $this->getPath() . '/data' . ( $subdir !== NULL ? '/' . $subdir : '' );
		if ( ! is_dir( $data_dir ) )
		{
			if ( mkdir( $data_dir ) === FALSE )
			{
				throw new \ErrorException( 'Unable to create the plugin data directory.' );
			}
		}
		
		file_put_contents( $data_dir . '/' . $key . '.php', "<?php\nreturn <<<'JSON'\n" . json_encode( $data, JSON_PRETTY_PRINT ) . "\nJSON;\n" );
	}
	
	/**
	 * Get the plugin slug (dirname)
	 *
	 * @api
	 *
	 * @return	string
	 */
	public function pluginSlug()
	{
		return basename( $this->getPath() );
	}
	
	/**
	 * Get a plugin file path
	 *
	 * @api
	 *
	 * @param	string				$pathfile		The file path and name relative to the plugin path
	 * @param	string|FALSE		$type			The file type or NULL if file type is included in $pathfile
	 * @return	string
	 */
	public function pluginFile( $pathfile, $type=NULL )
	{
		return $this->getPath() . '/' . $pathfile . ( $type ? ( '.' . $type ) : '' );
	}
	
	/**
	 * Get a plugin file url
	 *
	 * @api
	 *
	 * @param	string		$filename		The file path and name (including extension)
	 * @return	string
	 */
	public function fileUrl( $filename )
	{
		return plugins_url( $filename, $this->getPath() . '/dir' );
	}
	
	/**
	 * Use a registered script
	 *
	 * @api
	 *
	 * @param	string		$script				The script to use
	 * @param	array		$localization		Localization data to pass to the script
	 * @return	void
	 */
	public function useScript( $script, $localization=array() )
	{
		static $usedScripts = array();
		static $existingLocalization = FALSE;
		
		$fileHash = md5( $this->fileUrl( $script ) );	
		$handle = isset( static::$scriptHandles[ $fileHash ] ) ? static::$scriptHandles[ $fileHash ] : $fileHash;
		
		if ( ! isset( $usedScripts[ $handle ] ) )
		{			
			if ( ! empty( $localization ) )
			{
				wp_localize_script( $handle, 'mw_localized_data', $localization );
				$existingLocalization = TRUE;
			}
			
			if ( empty( $localization ) and $existingLocalization )
			{
				wp_localize_script( $handle, 'mw_localized_data', array() );
				$existingLocalization = FALSE;
			}				
			
			wp_enqueue_script( $handle );
			$usedScripts[ $handle ] = TRUE;
		}
	}
	
	/**
	 * Use a registered stylesheet
	 *
	 * @api
	 *
	 * @param	string		$style				The stylesheet to use
	 * @return	void
	 */
	public function useStyle( $style )
	{
		static $usedStyles = array();
		$fileHash = md5( $this->fileUrl( $style ) );
		$handle = isset( static::$styleHandles[ $fileHash ] ) ? static::$styleHandles[ $fileHash ] : $fileHash;
		
		if ( ! isset( $usedStyles[ $fileHash ] ) )
		{
			wp_enqueue_style( $fileHash );
			$usedStyles[ $fileHash ] = TRUE;
		}
	}

	/**
	 * Get the location of a template file
	 *
	 * @api
	 *
	 * @param	string		$template 			Plugin template to look for (without file extension)
	 * @return	string
	 */
	public function getTemplate( $template )
	{
		if ( $overridden_template = locate_template( $this->pluginSlug() . '/' . $template . '.php' ) ) 
		{
			// locate_template() returns path to file
			// if either the child theme or the parent theme have overridden the template
			return $overridden_template;
		} 
		else 
		{
			// If neither the child nor parent theme have overridden the template,
			// we load the template from the 'templates' directory of this plugin,
			// or alternatively fall back to the mwp application framework template
			$templateFile = $this->pluginFile( 'templates/' . $template, 'php' );
			
			if ( file_exists( $templateFile ) )
			{			
				return $templateFile;
			}
			
			return Framework::instance()->pluginFile( 'templates/' . $template, 'php' );
		}
	}

	/**
	 * Get the content of a template
	 *
	 * @api
	 *
	 * @param	string		$template 			Plugin template to load (without file extension)
	 * @param	array		$vars				Variables to extract and make available to template
	 * @return	string
	 */
	public function getTemplateContent( $template, $vars=array() )
	{
		$templateFile = $this->getTemplate( $template );
		
		if ( ! file_exists( $templateFile ) )
		{
			return "[missing template file: {$this->pluginSlug()}/templates/{$template}]";
		}
		
		if ( is_array( $vars ) )
		{
			extract( $vars, EXTR_SKIP );
		}

		ob_start();
		include $templateFile;
		$templateContent = ob_get_clean();
		
		return apply_filters( 'mwp_fw_tmpl', $templateContent, $this->pluginSlug(), $template, $vars );
	}
	
	/**
	 * Create a new form
	 *
	 * @param	string			$name				The form name
	 * @param	array|NULL		$data				Default form data
	 * @param	array			$options			Form options
	 * @param	string			$implementation		The form implementation to use
	 * @return	Form
	 */
	public function createForm( $name, $data=null, $options=array(), $implementation=null )
	{
		$formImplementation = apply_filters( 'mwp_fw_form_implementation', $implementation, $name, $this, $data, $options );
		$formClass = apply_filters( 'mwp_fw_form_class', 'MWP\Framework\Helpers\Form\SymfonyForm', $name, $this, $data, $options, $formImplementation );
		
		$form = new $formClass( $name, $this, $data, $options );
		
		return $form->applyFilters( 'create', $form, $data, $options );		
	}
	
	/**
	 * Ensure that the framework task runner is set up
	 *
	 * @Wordpress\Plugin( on="activation", file="plugin.php" )
	 *
	 * @return	void
	 */
	public function _pluginActivated()
	{
		wp_clear_scheduled_hook( 'mwp_framework_queue_run' );
		wp_clear_scheduled_hook( 'mwp_framework_queue_maintenance' );
		wp_schedule_event( time(), 'minutely', 'mwp_framework_queue_run' );
		wp_schedule_event( time(), 'hourly', 'mwp_framework_queue_maintenance' );
		$this->updateSchema();
	}

	/**
	 * Clear the queue schedule on framework deactivation
	 *
	 * @Wordpress\Plugin( on="deactivation", file="plugin.php" )
	 *
	 * @return	void
	 */
	public function _pluginDeactivated()
	{
		
	}
	
	/**
	 * Internal: Framework Plugin Finder
	 *
	 * @Wordpress\Filter( for="mwp_framework_plugins" )
	 *
	 * @param	array		$plugins		Found plugins
	 * @return	array
	 */
	public function _rollCall( $plugins )
	{
		$plugins[] = $this;
		return $plugins;
	}
	
	/**
	 * Create a new plugin build
	 *
	 * @param	string			$slug					The plugin slug
	 * @param	array			$options				Build options
	 * @return	string									Build filename
	 * @throws	ErrorException
	 */
	public static function createBuild( $slug, $options=array() )
	{
		if ( ! $slug or ! is_dir( WP_PLUGIN_DIR . '/' . $slug ) ) {
			throw new \ErrorException( 'Plugin directory is not valid: ' . $slug );
		}
		
		$ignorelist = array(
			'data/install-meta.php',
			'data/instance-meta.php'
		);
		
		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/.buildignore' ) ) {
			$fh = fopen( WP_PLUGIN_DIR . '/' . $slug . '/.buildignore', 'r' );
			while( $line = fgets( $fh ) ) {
				if ( $line ) {
					$ignorelist[] = str_replace( '\\', '/', trim( $line ) );
				}
			}
			fclose( $fh );
		}
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/builds' ) ) {
			if ( ! mkdir( WP_PLUGIN_DIR . '/' . $slug . '/builds' ) ) {
				throw new \ErrorException( 'Unable to create the /builds directory' );
			}
		}
		
		$plugin_version = "0.0.0";
		$meta_data = array();
		
		/* Create data directory if needed */
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/data' ) ) {
			if ( ! mkdir( WP_PLUGIN_DIR . '/' . $slug . '/data' ) ) {
				throw new \ErrorException( 'Unable to create the /data directory to store plugin meta data.' );
			}
		}
		
		/* Read existing metadata */
		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php' ) ) {
			$meta_data = json_decode( include WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php', TRUE );
			if ( isset( $meta_data[ 'version' ] ) and $meta_data[ 'version' ] ) {
				$plugin_version = $meta_data[ 'version' ];
			}
		}
		
		/* Work out the new version number if needed */
		if ( isset( $options[ 'version-update' ] ) and $options[ 'version-update' ] )
		{
			$version_parts = explode( '.', $plugin_version );
			switch( $options[ 'version-update' ] )
			{
				case 'major':
					$version_parts[0]++;
					$plugin_version = $version_parts[0] . '.0.0';
					break;
					
				case 'minor':
					$version_parts[1]++;
					$plugin_version = $version_parts[0] . '.' . $version_parts[1] . '.0';
					break;
					
				case 'point':
					$version_parts[2]++;
					$plugin_version = $version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2];
					break;
				
				case 'patch':
					
					$plugin_version = $version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2] . '.' . ( isset( $version_parts[3] ) ? $version_parts[3] + 1 : 1 );
					break;
				
				default:
					$plugin_version = $options[ 'version-update' ];
			}
		}
		
		/**
		 * Create build meta data
		 */
		if ( ! isset( $options[ 'skip_db_dump' ] ) or ! $options[ 'skip_db_dump' ] ) 
		{
			$build_meta = array();
			$dbHelper = \MWP\Framework\DbHelper::instance();
			
			/* Update table schema data file */
			if ( isset( $meta_data[ 'tables' ] ) and $meta_data[ 'tables' ] )
			{
				$build_meta['tables'] = array();
				
				$tables = explode( ',', $meta_data[ 'tables' ] );
				foreach( $tables as $table )
				{
					// trim spaces from table names
					$table = trim( $table );
					
					try {
						$build_meta[ 'tables' ][] = $dbHelper->getTableDefinition( $table, FALSE );
					}
					catch( \ErrorException $e ) { }
				}
			}
		
			/* Update table schema data file */
			if ( isset( $meta_data[ 'ms_tables' ] ) and $meta_data[ 'ms_tables' ] )
			{
				$build_meta['ms_tables'] = array();
				
				$tables = explode( ',', $meta_data[ 'ms_tables' ] );
				foreach( $tables as $table )
				{
					// trim spaces from table names
					$table = trim( $table );
					
					try {
						$build_meta[ 'ms_tables' ][] = $dbHelper->getTableDefinition( $table, FALSE );
					}
					catch( \ErrorException $e ) { }
				}
			}
			
			/* Save the build meta */
			file_put_contents( WP_PLUGIN_DIR . '/' . $slug . '/data/build-meta.php', "<?php\nreturn <<<'JSON'\n" . json_encode( $build_meta, JSON_PRETTY_PRINT ) . "\nJSON;\n" );
		}
		
		$bundle = ( ( isset( $options['bundle'] ) and $options['bundle'] ) or ( ! isset( $options[ 'nobundle' ] ) or ! $options[ 'nobundle' ] ) );
		
		// Bundle the mwp application framework in with the plugin
		if ( $slug !== 'mwp-framework' and $bundle )
		{
			static::rmdir( WP_PLUGIN_DIR . '/' . $slug . '/framework' );
			static::rmdir( WP_PLUGIN_DIR . '/' . $slug . '/mwp-framework' );
			
			try {
				$bundle_filename = \MWP\Framework\Plugin::createBuild( 'mwp-framework', array( 'nobundle' => true, 'skip_db_dump' => true ) );
			}
			catch( \Exception $e ) {
				$message = $e->getMessage();
				throw new \ErrorException( $message );
			}
			
			$framework_zip = new \ZipArchive();
			$framework_zip->open( $bundle_filename );
			$framework_zip->extractTo( WP_PLUGIN_DIR . '/' . $slug . '/' );
			$framework_zip->close();
			
			/* Prevent bundled framework from being detected as another plugin by installer skin */
			$contents = file_get_contents( WP_PLUGIN_DIR . '/' . $slug . '/mwp-framework/plugin.php' );
			$contents = str_replace( '* Plugin Name:', '* Plugin:', $contents );
			file_put_contents( WP_PLUGIN_DIR . '/' . $slug . '/mwp-framework/plugin.php', $contents );
			
			rename( WP_PLUGIN_DIR . '/' . $slug . '/mwp-framework', WP_PLUGIN_DIR . '/' . $slug . '/framework' );
		}
		
		/**
		 * Create the ZIP Archive
		 */
		{
			$zip_filename = WP_PLUGIN_DIR . '/' . $slug . '/builds/' . $slug . '-' . $plugin_version . '.zip';
			$zip = new \ZipArchive();
			if ( $zip->open( $zip_filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== TRUE ) 
			{
				throw new \ErrorException( 'Cannot create the archive file: ' . $zip_filename );
			}
			
			/* Recursively build files into the archive */
			$basedir = str_replace( '\\', '/', WP_PLUGIN_DIR . '/' . $slug . '/' );
			$addToArchive = function( $source ) use ( $slug, $ignorelist, $basedir, $zip, &$addToArchive, $plugin_version )
			{
				$relativename = str_replace( $basedir, '', str_replace( '\\', '/', $source ) );
				
				// Check against ignore list
				foreach( $ignorelist as $pattern ) {
					if ( fnmatch( $pattern, $relativename ) ) {
						return;
					}
				}
				
				// Add file to zip
				if ( is_file( $source ) ) 
				{
					/* Replace tokens in source files */
					$pathinfo = pathinfo( $source );
					if ( isset( $pathinfo[ 'extension' ] ) and in_array( $pathinfo[ 'extension' ], array( 'php', 'js', 'json', 'css' ) ) and substr( $relativename, 0, 7 ) !== 'vendor/' )
					{
						$source_contents = file_get_contents( $source );
						$updated_contents = strtr( $source_contents, array( '{' . 'build_version' . '}' => $plugin_version ) );
						
						if ( $relativename == 'plugin.php' )
						{
							$docComments = array_filter(
								token_get_all( $updated_contents ), function( $token ) {
									return $token[0] == T_DOC_COMMENT;
								}
							);
							
							/* Plugin Header */
							$headerDoc = array_shift( $docComments );
							$newHeaderDoc = preg_replace( '/Version:(.*?)\n/', "Version: " . $plugin_version . "\n", $headerDoc );
							$updated_contents = str_replace( $headerDoc, $newHeaderDoc, $updated_contents );
						}
						
						if ( $updated_contents != $source_contents )
						{
							file_put_contents( $source, $updated_contents );
						}
					}
					
					$zip->addFile( $source, $slug . '/' . $relativename );
					return;
				}

				// Loop through the folder
				$dir = dir( $source );
				while ( false !== $entry = $dir->read() ) 
				{
					// Skip pointers & special dirs
					if ( in_array( $entry, array( '.', '..' ) ) )
					{
						continue;
					}

					$addToArchive( "$source/$entry" );
				}

				// Clean up
				$dir->close();
			};
			
			/* Save new plugin meta data before building package */
			$meta_data[ 'version' ] = $plugin_version;
			file_put_contents( WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php', "<?php\nreturn <<<'JSON'\n" . json_encode( $meta_data, JSON_PRETTY_PRINT ) . "\nJSON;\n" );
		
			/* Build the release package */
			$addToArchive( WP_PLUGIN_DIR . '/' . $slug );
			$zip->close();
			
			static::rmdir( WP_PLUGIN_DIR . '/' . $slug . '/framework' );

			/* Copy to latest dev.zip */
			if ( isset( $options[ 'dev' ] ) and $options[ 'dev' ] )
			{
				copy( WP_PLUGIN_DIR . '/' . $slug . '/builds/' . $slug . '-' . $plugin_version . '.zip', WP_PLUGIN_DIR . '/' . $slug . '/builds/' . $slug . '-dev.zip' );
			}
			
			/* Copy to latest stable.zip */
			if ( isset( $options[ 'stable' ] ) and $options[ 'stable' ] )
			{
				copy( WP_PLUGIN_DIR . '/' . $slug . '/builds/' . $slug . '-' . $plugin_version . '.zip', WP_PLUGIN_DIR . '/' . $slug . '/builds/' . $slug . '-stable.zip' );
			}
			
			return $zip_filename;
		}
	}
	
	/**
	 * Delete a directory and all files in it
	 *
	 * @param	string		$dir			The directory to delete
	 * @return	void
	 */
	protected static function rmdir( $dir )
	{
		if ( ! is_dir( $dir ) )
		{
			return;
		}
		
		$_dir = dir( $dir );
		while ( false !== $file = $_dir->read() ) 
		{
			// Skip pointers & special dirs
			if ( in_array( $file, array( '.', '..' ) ) )
			{
				continue;
			}

			if( is_dir( $dir . '/' . $file ) ) 
			{
				static::rmdir( $dir . '/' . $file ); 
			}
			else 
			{
				unlink( $dir . '/' . $file );
			}
			
		}
		$_dir->close();
		
		rmdir( $dir ); 
	}	
}
