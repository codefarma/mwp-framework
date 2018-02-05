<?php
/**
 * WP CLI Command Class (WP_CLI_Command)
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

/**
 * MWP Framework commands that can be executed from the WP CLI.
 */
class CLI extends \WP_CLI_Command {

	/**
	 * Creates a new boilerplate mwp application framework plugin.
	 *
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the plugin.
	 *
	 * [--vendor=<vendor>]
	 * : The name of the plugin provider.
	 *
	 * [--namespace=<namespace>]
	 * : The Vendor\Package namespace for the plugin.
	 *
	 * [--slug=<slug>]
	 * : The directory name that the plugin will be created in.
	 *
	 * [--description=<description>]
	 * : The plugin description.
	 *
	 * [--author=<author>]
	 * : The name of the plugin author.
	 *
	 * [--author-url=<author_url>]
	 * : The plugin author web url.
	 *
	 * [--plugin-url=<plugin_url>]
	 * : The plugin project url.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new plugin
	 *     $ wp mwp create "My New Plugin" --vendor="My Company" --slug="example-plugin-dir" --namespace="MyCompany\MyPlugin" --description="A new plugin to customize."
	 *     Success: Plugin successfully created in 'example-plugin-dir'.
	 *
	 * @subcommand create-plugin
	 * @when after_wp_load
	 */
	public function createPlugin( $args, $assoc ) 
	{
		$framework = \MWP\Framework\Framework::instance();
		
		$assoc[ 'name' ] = $args[0];
		
		if ( ! isset( $assoc[ 'vendor' ] ) )
		{
			$assoc[ 'vendor' ] = "MWP Application Framework";
		}
		
		if ( ! isset( $assoc[ 'namespace' ] ) )
		{
			/**
			 * Create a namespace from the vendor + plugin name 
			 */
			
			/* Reduce to only alphanumerics and spaces */ 
			$vendorName = preg_replace( '/[^A-Za-z0-9 ]/', '', $assoc[ 'vendor' ] );
			$packageName = preg_replace( '/[^A-Za-z0-9 ]/', '', $assoc[ 'name' ] );
			
			/* Combine possible multiple spaces into a single space */
			$vendorName = preg_replace( '/ {2,}/', ' ', $vendorName );
			$packageName = preg_replace( '/ {2,}/', ' ', $packageName );
			
			/* Trim spaces off ends */
			$vendorName = trim( $vendorName );
			$packageName = trim( $packageName );
			
			/* Divide into words */
			$vendorPieces = explode( ' ', $vendorName );
			$packagePieces = explode( ' ', $packageName );
			
			/* Create vendor space from first 1 or 2 words */
			if ( count( $vendorPieces ) > 1 )
			{
				$piece1 = array_shift( $vendorPieces );
				$piece2 = array_shift( $vendorPieces );
				$vendorSpace = ucwords( $piece1 ) . ucwords( $piece2 );
			}
			else
			{
				$vendorSpace = ucwords( $vendorPieces[0] );
			}
			
			/* Create package space from first 1 or 2 words */
			if ( count( $packagePieces ) > 1 )
			{
				$piece1 = array_shift( $packagePieces );
				$piece2 = array_shift( $packagePieces );
				$packageSpace = ucwords( $piece1 ) . ucwords( $piece2 );
			}
			else
			{
				$packageSpace = ucwords( $packagePieces[0] );
			}
			
			$assoc[ 'namespace' ] = "$vendorSpace\\$packageSpace";
		}
		
		if ( ! isset( $assoc[ 'slug' ] ) )
		{
			$assoc[ 'slug' ] = strtolower( preg_replace( '|\\\|', '-', $assoc[ 'namespace' ] ) );
		}
		
		try
		{
			\WP_CLI::line( 'Creating plugin...' );
			$framework->createPlugin( $assoc );
		}
		catch( \Exception $e )
		{
			if ( $e->getCode() == 1 )
			{
				// No boilerplate present
				\WP_CLI::error( $e->getMessage() . "\nSuggestion: Try using: $ wp mwp update-boilerplate https://github.com/Miller-Media/wp-plugin-boilerplate/archive/master.zip" );
			}
			else if ( $e->getCode() == 2 )
			{
				// Plugin directory already used
				\WP_CLI::error( $e->getMessage() . "\nSuggestion: Try using: $ wp mwp create-plugin \"{$assoc['name']}\" --slug='my-custom-dir' to force a different install directory" );
			}
			
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::success( "Plugin successfully created in '{$assoc[ 'slug' ]}'." );
	}
	
	/**
	 * Update the wordpress plugin boilerplate
	 *
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The download url of the current boilerplate to update to 
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the boilerplate
	 *     $ wp mwp update-boilerplate https://github.com/Miller-Media/wp-plugin-boilerplate/archive/master.zip
	 *     Success: Boilerplate successfully updated.
	 *
	 * @subcommand update-boilerplate
	 * @when after_wp_load
	 */
	public function updateBoilerplate( $args, $assoc ) 
	{
		include_once( ABSPATH . 'wp-admin/includes/file.php' ); // Internal Upgrader WP Class
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ); // Internal Upgrader WP Class
		
		if ( WP_Filesystem() == FALSE )
		{
			\WP_CLI::error( 'Error initializing wp_filesystem.' );
			return;
		}
		
		$framework = \MWP\Framework\Framework::instance();
		$download_url = $args[0] ?: 'https://github.com/Miller-Media/wp-plugin-boilerplate/archive/master.zip';
		$upgrader = new \WP_Upgrader( new \MWP\Framework\CLI\WPUpgraderSkin );

		\WP_CLI::line( 'Downloading package...' );
		
		/*
		 * Download the package (Note, This just returns the filename
		 * of the file if the package is a local file)
		 */
		if ( is_wp_error( $download = $upgrader->download_package( $download_url ) ) ) 
		{
			\WP_CLI::error( $download->get_error_message() );
		}

		$delete_package = ( $download != $download_url ); // Do not delete a "local" file

		\WP_CLI::line( 'Extracting package...' );

		// Unzips the file into a temporary directory.
		if ( is_wp_error( $working_dir = $upgrader->unpack_package( $download, $delete_package ) ) ) 
		{
			\WP_CLI::error( $working_dir->get_error_message() );
		}
		
		\WP_CLI::line( 'Updating boilerplate plugin...' );

		// With the given options, this installs it to the destination directory.
		$result = $upgrader->install_package( array
		(
			'source' => $working_dir,
			'destination' => $framework->getPath() . '/boilerplate',
			'clear_destination' => true,
			'abort_if_destination_exists' => false,
			'clear_working' => true,
			'hook_extra' => array(),
		) );
		
		if ( is_wp_error( $result ) )
		{
			\WP_CLI::error( $result->get_error_message() );
		}
		
		\WP_CLI::success( 'Boilerplate successfully updated.' );
	}
	
	/**
	 * Add a new javascript module
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * <name>
	 * : The name of the javascript file
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a new javascript module
	 *     $ wp mwp add-js my-plugin testmodule.js
	 *     Success: Javascript module added successfully.
	 *
	 * @subcommand add-js
	 * @when after_wp_load
	 */
	public function createJavascriptModule( $args, $assoc )
	{
		$framework = \MWP\Framework\Framework::instance();
		
		if ( ! ( $args[0] and $args[1] ) )
		{
			\WP_CLI::error( 'Not enough command arguments given.' );
		}
		
		try
		{
			\WP_CLI::line( 'Creating new javascript module...' );
			$framework->createJavascript( $args[0], $args[1] );
		}
		catch( \ErrorException $e )
		{
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::success( 'Javascript module added successfully.' );
	}
	
	/**
	 * Add a new stylesheet file
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * <name>
	 * : The name of the stylesheet file
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a new css stylesheet
	 *     $ wp mwp add-css my-plugin newstyle.css
	 *     Success: Stylesheet added successfully.
	 *
	 * @subcommand add-css
	 * @when after_wp_load
	 */
	public function createStylesheetFile( $args, $assoc )
	{
		$framework = \MWP\Framework\Framework::instance();
		
		if ( ! ( $args[0] and $args[1] ) )
		{
			\WP_CLI::error( 'Not enough command arguments given.' );
		}
		
		try
		{
			\WP_CLI::line( 'Creating new css stylesheet...' );
			$framework->createStylesheet( $args[0], $args[1] );
		}
		catch( \ErrorException $e )
		{
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::success( 'Stylesheet added successfully.' );
	}
	
	/**
	 * Add a new template file
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * <name>
	 * : The name of the template file
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a new template snippet
	 *     $ wp mwp add-template my-plugin views/category
	 *     Success: Template added successfully.
	 *
	 * @subcommand add-template
	 * @when after_wp_load
	 */
	public function createTemplateFile( $args, $assoc )
	{
		$framework = \MWP\Framework\Framework::instance();
		
		if ( ! ( $args[0] and $args[1] ) )
		{
			\WP_CLI::error( 'Not enough command arguments given.' );
		}
		
		try
		{
			\WP_CLI::line( 'Creating new template snippet...' );
			$framework->createTemplate( $args[0], $args[1] );
		}
		catch( \ErrorException $e )
		{
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::success( 'Template added successfully.' );
	}

	/**
	 * Add a new php class file
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * <name>
	 * : The name of the new class (can be namespaced)
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a new php class file
	 *     $ wp mwp add-class my-plugin Plugin\NewSettings
	 *     Success: Class added successfully.
	 *
	 * @subcommand add-class
	 * @when after_wp_load
	 */
	public function createClassFile( $args, $assoc )
	{
		$framework = \MWP\Framework\Framework::instance();
		
		try
		{
			\WP_CLI::line( 'Creating new plugin class file...' );
			$framework->createClass( $args[0], $args[1] );
		}
		catch( \ErrorException $e )
		{
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::success( 'Class added sucessfully.' );
	}
	
	/**
	 * Update plugin meta data contents
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * [--auto-update]
	 * : Automatically update meta data by reading plugin header
	 *
	 * [--filename=<filename>]
	 * : The plugin filename that contains the meta data
	 *
	 * [--<field>=<value>]
	 * : The specific meta data to update
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the meta data in a plugin file
	 *     $ wp mwp update-meta my-plugin --auto-update --namespace="MyCompany\PluginPackage"
	 *     Success: Meta data successfully updated.
	 *
	 * @subcommand update-meta
	 * @when after_wp_load
	 */
	public function updateMetaData( $args, $assoc )
	{
		$slug = $args[0];
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug ) )
		{
			\WP_CLI::error( 'Plugin directory is not valid: ' . $slug );
		}
		
		$meta_data = array();
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/data' ) )
		{
			/* Create the data dir to store the meta data */
			if ( ! mkdir( WP_PLUGIN_DIR . '/' . $slug . '/data' ) )
			{
				\WP_CLI::error( 'Error creating data directory: ' . $slug . '/data' );
			}
			
		}
		else
		{
			/* Read existing metadata */
			if ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php' ) )
			{
				$meta_data = json_decode( include WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php', TRUE );
			}
		}
		
		if ( isset( $assoc[ 'auto-update' ] ) and $assoc[ 'auto-update' ] )
		{
			include_once get_home_path() . 'wp-admin/includes/plugin.php';
			$filename = isset( $assoc[ 'filename' ] ) ? $assoc[ 'filename' ] : 'plugin.php';

			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $slug . '/' . $filename ) )
			{
				\WP_CLI::error( 'Could not locate the plugin file: ' . $slug . '/' . $filename . "\n" . "Try using the --filename parameter to specify the correct filename." );
			}
			
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug . '/' . $filename, FALSE );
			
			if ( empty( $plugin_data ) )
			{
				\WP_CLI::error( 'No meta data could be found in file: ' . $slug . '/' . $filename );
			}
			
			$meta_data[ 'slug' ] = $slug;
			
			if ( $plugin_data[ 'Name' ] ) {
				$meta_data[ 'name' ] = $plugin_data[ 'Name' ];
			}

			if ( $plugin_data[ 'PluginURI' ] ) {
				$meta_data[ 'plugin_url' ] = $plugin_data[ 'PluginURI' ];
			}
			
			if ( $plugin_data[ 'Description' ] ) {
				$meta_data[ 'description' ] = $plugin_data[ 'Description' ];
			}
			
			if ( $plugin_data[ 'AuthorName' ] ) {
				$meta_data[ 'author' ] = $plugin_data[ 'AuthorName' ];
			}
			
			if ( $plugin_data[ 'AuthorURI' ] ) {
				$meta_data[ 'author_url' ] = $plugin_data[ 'AuthorURI' ];
			}
			
			if ( $plugin_data[ 'Version' ] )
			{
				$meta_data[ 'version' ] = $plugin_data[ 'Version' ];
			}
		}
		
		foreach( $assoc as $key => $value )
		{
			if ( ! in_array( $key, array( 'auto-update', 'filename' ) ) )
			{
				$meta_data[ $key ] = $value;
			}
		}
		
		file_put_contents( WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php', "<?php\nreturn <<<'JSON'\n" . json_encode( $meta_data, JSON_PRETTY_PRINT ) . "\nJSON;\n" );
		
		\WP_CLI::success( 'Meta data successfully updated.' );
	}
	
	/**
	 * Update plugin meta data contents
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the meta data in a plugin file
	 *     $ wp mwp update-meta my-plugin --auto-update --namespace="MyCompany\PluginPackage"
	 *     Success: Meta data successfully updated.
	 *
	 * @subcommand update-database
	 * @when after_wp_load
	 */
	public function updateDatabase( $args, $assoc )
	{
		$slug = $args[0];
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug ) )
		{
			\WP_CLI::error( 'Plugin directory is not valid: ' . $slug );
		}
		
		$meta_data = array();
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/data' ) )
		{
			\WP_CLI::error( 'Could not find plugin meta data directory: ' . WP_PLUGIN_DIR . '/' . $slug . '/data' );
		}
		else
		{
			/* Read existing metadata */
			if ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php' ) )
			{
				$meta_data = json_decode( include WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php', TRUE );
				
				if ( ! $meta_data['namespace'] ) {
					\WP_CLI::error( 'Could not detect plugin namespace.' );
				}
				
				$pluginClass = $meta_data['namespace'] . ( $slug == 'mwp-framework' ? '\Framework' : '\Plugin' );
				$plugin = $pluginClass::instance();
				$deltaUpdate = $plugin->updateSchema();
				
				if ( $deltaUpdate ) {
					foreach( $deltaUpdate as $table_name => $updates ) {
						foreach( $updates as $updateDescription ) {
							\WP_CLI::line( $updateDescription );
						}
					}
					\WP_CLI::success( 'Database schema updated.' );
				} else {
					\WP_CLI::success( 'Database schema is already up to date.' );
				}
			}
		}
	}
	
	/**
	 * Build a new plugin package for release
	 * 
	 * @param	$args		array		Positional command line arguments
	 * @param	$assoc		array		Named command line arguments
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the mwp application framework plugin
	 * 
	 * [--version-update=<version>]
	 * : The new plugin version can be set explicitly, or auto incremented by using =(major, minor, point, patch)
	 *
	 * [--stable]
	 * : Use flag to update the latest-stable.zip to the current build
	 *
	 * [--dev]
	 * : Use flag to update the latest-dev.zip to the current build
	 *
	 * [--nobundle]
	 * : Use flag to prevent the mwp application framework from being bundled in with the plugin
	 *
	 * ## EXAMPLES
	 *
	 *     # Build a new plugin package for release
	 *     $ wp mwp build-plugin my-plugin --version-update=point
	 *     Success: Plugin package successfully built.
	 *
	 * @subcommand build-plugin
	 * @when after_wp_load
	 */
	public function buildPlugin( $args, $assoc )
	{
		$slug = $args[0];
		
		try {
			\WP_CLI::line( 'Building...' );
			$build_file = \MWP\Framework\Plugin::createBuild( $slug, $assoc );
		}
		catch( \Exception $e )
		{
			\WP_CLI::error( $e->getMessage() );
		}
		
		\WP_CLI::line( $build_file );
		\WP_CLI::success( 'Plugin package successfully built.' );
	}
	
	/**
	 * Delete a directory and all files in it
	 *
	 * @param	string		$dir			The directory to delete
	 * @return	void
	 */
	protected function rmdir( $dir )
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
				$this->rmdir( $dir . '/' . $file ); 
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
