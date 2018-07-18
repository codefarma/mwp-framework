<?php
/**
 * Framework Class (Singleton)
 * 
 * Created:    Nov 20, 2016
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.0.0
 */

namespace MWP\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use \Doctrine\Common\Annotations\AnnotationReader;
use \Doctrine\Common\Annotations\FileCacheReader;

/**
 * Provides access to core framework methods and features. 
 */
class _Framework extends Plugin
{
	/**
	 * Instance Cache - Required for all singleton subclasses
	 *
	 * @var	self
	 */
	protected static $_instance;
	
	/** 
	 * @var Annotations Reader
	 */
	protected $reader;
		
	/**
	 * Constructor
	 */
	protected function __construct()
	{
		/* Load Annotation Reader */		
		try 
		{
			// Attempt to read from file caches
			$this->reader = new FileCacheReader( new AnnotationReader(), dirname( __DIR__ ) . "/annotations/cache", $this->isDev() );
		} 
		catch( \InvalidArgumentException $e )
		{
			// Fallback to reading on the fly every time if the directory cannot be loaded
			$this->reader = new AnnotationReader();
		}
		
		/* Register WP CLI */
		if ( defined( '\WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'mwp', 'MWP\Framework\CLI' );
		}
		
		/* Init Parent */
		parent::__construct();		
	}
	
	/**
	 * Get annotation reader
	 *
	 * @return	Reader
	 */
	public function getAnnotationReader()
	{
		return $this->reader;
	}
	
	/**
	 * Return the database
	 *
	 * @return		wpdb
	 */
	public function db()
	{
		global $wpdb;
		return $wpdb;
	}
	
	/**
	 * @var		Request
	 */
	protected $request;
	
	/**
	 * Get the HTTP Request
	 *
	 * @return	Request
	 */
	public function getRequest()
	{
		if ( ! isset( $this->request ) ) {
			$this->request = new \Symfony\Component\HttpFoundation\Request( stripslashes_deep( $_GET ), stripslashes_deep( $_POST ), array(), stripslashes_deep( $_COOKIE ), $_FILES, $_SERVER );
		}
		
		return $this->request;
	}
	
	/**
	 * Is development mode
	 *
	 * @return	bool
	 */
	public function isDev()
	{
		// Respect a hard setting
		if ( defined( 'MWP_FRAMEWORK_DEV' ) ) {
			return \MWP_FRAMEWORK_DEV === TRUE;
		}
		
		// Fallback to soft setting
		return (bool) $this->getSetting( 'mwp_developer_mode' );
	}
	
	/**
	 * Initialization
	 *
	 * @MWP\WordPress\Action( for="init" )
	 *
	 * @return	void
	 */
	public function initialized()
	{
		// Ensure task runner is activated
		if ( wp_get_schedule( 'mwp_framework_queue_run' ) == false ) 
		{
			$this->frameworkActivated();
		}

		if ( ! $this->isDev() )
		{
			$instance_meta = $this->data( 'instance-meta' ) ?: array();
			$mwp_cache_latest = get_site_option( 'mwp_fw_cache_latest' ) ?: 0;
			
			// Clear caches for this instance if we know they are out of date
			if ( ! isset( $instance_meta[ 'cache_timestamp' ] ) or $instance_meta[ 'cache_timestamp' ] < $mwp_cache_latest ) {
				$this->clearCaches();
			}
		}
	}
	
	/**
	 * Admin init
	 * 
	 * @MWP\WordPress\Action( for="admin_init" )
	 * 
	 * @return	void
	 */
	public function adminInit()
	{
		remove_action( 'install_plugins_upload', 'install_plugins_upload' );
	}
	
	/**
	 * Modify the upload plugin form 
	 * 
	 * @MWP\WordPress\Action( for="install_plugins_upload", output=true )
	 * 
	 * @return	string
	 */
	public function uploadPluginForm()
	{
		return $this->getTemplateContent( 'admin/upload-plugin-form' );
	}
	
	/**
	 * Add dashboard widget
	 *
	 * @MWP\WordPress\Action( for="wp_dashboard_setup" )
	 * 
	 * @return	void
	 */
	public function addDashboardWidget()
	{
		if ( $plugin_meta = $this->getData( 'plugin-meta' ) ) {
			$version = isset( $plugin_meta['version'] ) ? " ({$plugin_meta['version']})" : '';
		}
		
		if ( current_user_can('administrator') ) {
			wp_add_dashboard_widget( 'mwp-fw-console', __( "MWP Application Framework Console", 'mwp-framework' ) . $version, function() {
				echo $this->getTemplateContent( 'widget/dashboard' );
			});
		}
	}
	
	/**
	 * Allow plugins to be upgraded by uploading a new version
	 * 
	 * @MWP\WordPress\Filter( for="upgrader_package_options" )
	 * 
	 * @param	array			$options				The upgrader options
	 * @return	array
	 */
	public function allowUploadUpgrades( $options )
	{
		if ( isset( $_REQUEST['clear_destination'] ) and $_REQUEST['clear_destination'] ) 
		{
			$options['clear_destination'] = true;
		}
		
		return $options;
	}
	
	/**
	 * Return the form class for supported form engines
	 * 
	 * @MWP\WordPress\Filter( for="mwp_fw_form_class", args=6 )
	 * 
	 * @param	string			$form_class			The current form class
	 * @param	string			$name				The form name
	 * @param	Plugin			$plugin				The creating plugin
	 * @param	array|NULL		$data				Default form data
	 * @param	array			$options			Form options
	 * @param	string|NULL		$implementation		The form implementation to use
	 * @return	string
	 */
	public function mwpFormClass( $form_class, $name, $plugin, $data, $options, $implementation )
	{
		switch( $implementation ) 
		{
			case 'symfony':	return 'MWP\Framework\Helpers\Form\SymfonyForm';
		}
		
		return $form_class;
	}
	
	/**
	 * Attach Functionality to WordPress
	 *
	 * @api
	 *
	 * @param	object		$instance		An object instance to attach to wordpress 
	 * @return	this
	 */
	public function attach( $instance )
	{
		// Make sure schema is installed for plugins when running tests
		if ( defined('DIR_TESTDATA') and $instance instanceof Plugin ) {
			$instance->updateSchema();
			register_shutdown_function( function() use ( $instance ) {
				$instance->uninstall();
			});
		}
		
		$reflClass = new \ReflectionClass( get_class( $instance ) );
		$vars = array();
		$framework = $this;
		
		/**
		 * Class Annotations
		 */
		$getClassAnnotationsRecursively = function( $reflClass ) use ( $framework, &$getClassAnnotationsRecursively ) 
		{
			$annotations = $reflClass->getDocComment() ? $framework->reader->getClassAnnotations( $reflClass ) : array();
			$inheritByDefault = true;
			foreach( $annotations as $k => $annotation ) {
				if ( $annotation instanceof \MWP\Annotations\Inherit ) {
					if ( $parentReflClass = $reflClass->getParentClass() ) {
						array_splice( $annotations, $k, 1, $getClassAnnotationsRecursively( $parentReflClass ) );
					}
					$inheritByDefault = false;
					break;
				}
				if ( $annotation instanceof \MWP\Annotations\Override ) {
					$inheritByDefault = false;
					break;
				}
			}
			
			if ( $inheritByDefault ) {
				if ( $parentReflClass = $reflClass->getParentClass() ) {
					$annotations = array_merge( $annotations, $getClassAnnotationsRecursively( $parentReflClass ) );
				}
			}
			
			return $annotations;
		};
		
		foreach( $getClassAnnotationsRecursively( $reflClass ) as $annotation ) {
			if ( is_callable( array( $annotation, 'applyToObject' ) ) ) {
				$result = $annotation->applyToObject( $instance, $vars );
				if ( ! empty( $result ) ) {
					$vars = array_merge( $vars, $result );
				}
			}
		}
		
		/**
		 * Property Annotations
		 */
		$getPropertyAnnotationsRecursively = function( $property ) use ( $framework, &$getPropertyAnnotationsRecursively ) 
		{
			$annotations = $framework->reader->getPropertyAnnotations( $property );
			$inheritByDefault = true;
			foreach( $annotations as $k => $annotation ) {
				if ( $annotation instanceof \MWP\Annotations\Inherit ) {
					if ( $parentReflClass = $property->getDeclaringClass()->getParentClass() ) {
						try {
							if ( $parentProperty = $parentReflClass->getProperty( $property->getName() ) ) {
								array_splice( $annotations, $k, 1, $getPropertyAnnotationsRecursively( $parentProperty ) );
							}
						} catch( \ReflectionException $e ) {}
					}
					$inheritByDefault = false;
					break;
				}
				if ( $annotation instanceof \MWP\Annotations\Override ) {
					$inheritByDefault = false;
					break;
				}
			}
			
			if ( $inheritByDefault ) {
				if ( $parentReflClass = $property->getDeclaringClass()->getParentClass() ) {
					try {
						if ( $parentProperty = $parentReflClass->getProperty( $property->getName() ) ) {
							$annotations = array_merge( $annotations, $getPropertyAnnotationsRecursively( $parentProperty ) );
						}
					} catch( \ReflectionException $e ) {}
				}
			}
			
			return $annotations;
		};
		
		foreach ( $reflClass->getProperties() as $property ) {
			foreach ( $getPropertyAnnotationsRecursively( $property ) as $annotation ) {
				if ( is_callable( array( $annotation, 'applyToProperty' ) ) ) {
					$result = $annotation->applyToProperty( $instance, $property, $vars );
					if ( ! empty( $result ) ) {
						$vars = array_merge( $vars, $result );
					}
				}
			}
		}
		
		/**
		 * Method Annotations
		 */
		$getMethodAnnotationsRecursively = function( $method ) use ( $framework, &$getMethodAnnotationsRecursively ) {
			$annotations = $framework->reader->getMethodAnnotations( $method );
			$inheritByDefault = true;
			foreach( $annotations as $k => $annotation ) {
				if ( $annotation instanceof \MWP\Annotations\Inherit ) {
					if ( $parentReflClass = $method->getDeclaringClass()->getParentClass() ) {
						try {
							if ( $parentMethod = $parentReflClass->getMethod( $method->getName() ) ) {
								array_splice( $annotations, $k, 1, $getMethodAnnotationsRecursively( $parentMethod ) );
							}
						} catch( \ReflectionException $e ) {}
					}
					$inheritByDefault = false;
					break;
				}
				if ( $annotation instanceof \MWP\Annotations\Override ) {
					$inheritByDefault = false;
					break;
				}
			}
			
			if ( $inheritByDefault ) {
				if ( $parentReflClass = $method->getDeclaringClass()->getParentClass() ) {
					try { 
						if ( $parentMethod = $parentReflClass->getMethod( $method->getName() ) ) {
							$annotations = array_merge( $annotations, $getMethodAnnotationsRecursively( $parentMethod ) );
						}
					} catch( \ReflectionException $e ) {}
				}
			}
			
			return $annotations;
		};
		
		foreach ( $reflClass->getMethods() as $method ) {
			foreach ( $getMethodAnnotationsRecursively( $method ) as $annotation ) {
				if ( is_callable( array( $annotation, 'applyToMethod' ) ) ) {
					$result = $annotation->applyToMethod( $instance, $method, $vars );
					if ( ! empty( $result ) ) {
						$vars = array_merge( $vars, $result );
					}
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Clear annotation reader cache upon plugin updates, etc
	 *
	 * @return	void
	 */
	public function clearCaches()
	{
		// Delete files in cache folder
		@array_map( 'unlink', glob( dirname( __DIR__ ) . "/annotations/cache/*.cache.php" ) );
		
		do_action( 'mwp_framework_clear_caches' );
		
		if ( ! $this->isDev() ) {
			$instance_meta = $this->data( 'instance-meta' ) ?: array();		
			$instance_meta[ 'cache_timestamp' ] = time();
			$this->setData( 'instance-meta', $instance_meta );
		}
	}
	
	/**
	 * Register framework resources and dependency chains
	 * 
	 * @MWP\WordPress\Action( for="wp_enqueue_scripts", priority=-1 )
	 * @MWP\WordPress\Action( for="admin_enqueue_scripts", priority=-1 )
	 * @MWP\WordPress\Action( for="login_enqueue_scripts", priority=-1 )
	 */
	public function enqueueScripts()
	{
		$location = is_admin() ? 'admin' : 'front';
		
		$use_bootstrap_js = $this->getSetting( "mwp_bootstrap_disable_{$location}_js" ) ? false : true;
		$use_bootstrap_css = $this->getSetting( "mwp_bootstrap_disable_{$location}_css" ) ? false : true;
		
		wp_register_script( 'knockout', $this->fileUrl( 'assets/js/knockout.min.js' ), [], $this->getVersion() );
		wp_register_script( 'knockback', $this->fileUrl( 'assets/js/knockback.min.js' ), array( 'underscore', 'backbone', 'knockout' ), $this->getVersion() );
		wp_register_script( 'jquery-loading-overlay', $this->fileUrl( 'assets/js/jquery.loading-overlay.min.js' ), [], $this->getVersion() );
		
		$bootstrap_js = $use_bootstrap_js ? 'assets/js/mwp.bootstrap.min.js' : 'assets/js/mwp.bootstrap.disabled.js';
		wp_register_script( 'mwp-bootstrap', $this->fileUrl( $bootstrap_js ), array( 'jquery' ), $this->getVersion() );

		$bootstrap_css = $use_bootstrap_css ? 'assets/css/mwp-bootstrap.min.css' : 'assets/css/mwp-bootstrap.disabled.css';
		wp_register_style( 'mwp-bootstrap', $this->fileUrl( $bootstrap_css ), [], $this->getVersion() );
		
		if ( $use_bootstrap_js ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'mwp-bootstrap' );
		}
		
		if ( $use_bootstrap_css ) {
			wp_enqueue_style( 'mwp-bootstrap' );
		}
		
		wp_register_script( 'mwp-settings', $this->fileUrl( 'assets/js/mwp.settings.js' ), array( 'mwp', 'knockback' ), $this->getVersion() );
		wp_register_script( 'mwp', $this->fileUrl( 'assets/js/mwp.framework.js' ), array( 'jquery', 'underscore', 'backbone', 'knockout' ), $this->getVersion() );
		wp_localize_script( 'mwp', 'mw_localized_data', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'ajaxnonce' => wp_create_nonce( 'mwp-ajax-nonce' ),
		));

		/* Forms */
		wp_register_script( 'mwp-forms-controller', $this->fileUrl( 'assets/js/mwp-forms-controller.js' ), array( 'mwp', 'jquery-ui-sortable' ), $this->getVersion() );
		wp_enqueue_style( 'mwp-forms-css', $this->fileUrl( 'assets/css/mwp-forms.css' ) );
		
		if ( is_admin() ) {
			wp_enqueue_script( 'mwp-settings' );
			wp_enqueue_style( 'mwp-forms-css' );
			wp_enqueue_script( 'mwp-forms-controller' );
		}
	}
	
	/**
	 * Register admin related scripts
	 *
	 * @MWP\WordPress\Action( for="admin_enqueue_scripts" )
	 *
	 * @return	void
	 */
	public function adminEnqueueScripts()
	{
	}
	
	/**
	 * Get all mwp application framework plugins
	 *
	 * @api
	 *
	 * @param	bool		$recache		Force recaching of plugins
	 * @return	array
	 */
	public function getPlugins( $recache=FALSE )
	{
		static $plugins;
		
		if ( ! isset( $plugins ) or $recache )
		{
			$plugins = apply_filters( 'mwp_framework_plugins', array() );
		}
		
		return $plugins;
	}
	
	/**
	 * Include localized data with mwp scripts when concatenation is turned on
	 *
	 * @MWP\WordPress\Filter( for="script_loader_tag", args=3 )
	 * 
	 * @param	string			$tag				The script tag
	 * @param	string			$handle				The script handle
	 * @param	string			$src				The script src
	 * @return	string
	 */
	public function adjustConcatSettings( $tag, $handle, $src )
	{
		global $wp_scripts;
		if ( $wp_scripts->do_concat )
		{
			$localized_data = $wp_scripts->get_data( $handle, 'data' );
			if ( $localized_data and strstr( $localized_data, 'mw_localized_data' ) ) 
			{
				/**
				 * If $wp_scripts->do_concat is enabled, then localized data will all be printed before the
				 * actual concatenated scripts are outputted, which can cause script specific mw_localized_data 
				 * to clobber each other. So, this prepends the localized data to the concatenated script tag.
				 */
				ob_start();
				$wp_scripts->print_extra_script( $handle );
				$local_data_script = ob_get_clean();
				$tag = $local_data_script . $tag;
			}
		}
		
		return $tag;
	}
	
	/**
	 * Add a one minute time period to the wordpress cron schedule
	 *
	 * @MWP\WordPress\Filter( for="cron_schedules" )
	 *
	 * @param	array		$schedules		Array of schedule frequencies
	 * @return	array
	 */
	public function cronSchedules( $schedules )
	{
		$schedules[ 'minutely' ] = array(
			'interval' => 60,
			'display' => __( 'Once Per Minute' )
		);
		
		return $schedules;
	}
	
	/**
	 * Setup the queue schedule on framework activation
	 *
	 * @MWP\WordPress\Plugin( on="activation", file="plugin.php" )
	 *
	 * @return	void
	 */
	public function frameworkActivated()
	{
		wp_clear_scheduled_hook( 'mwp_framework_queue_run' );
		wp_schedule_event( time(), 'minutely', 'mwp_framework_queue_run' );
	}
	
	/**
	 * Clear the queue schedule on framework deactivation
	 *
	 * @MWP\WordPress\Plugin( on="deactivation", file="plugin.php" )
	 *
	 * @return	void
	 */
	public function frameworkDeactivated()
	{
		wp_clear_scheduled_hook( 'mwp_framework_queue_run' );
	}
	
	/**
	 * Run any queued tasks
	 *
	 * @MWP\WordPress\Action( for="mwp_framework_queue_run" )
	 *
	 * @return	void
	 */
	public function runTasks()
	{		
		$db = $this->db();
		$begin_time = time();
		$task_max_execution_time = $max_execution_time = ini_get( 'max_execution_time' );
		$max_task_runners = $this->getSetting( 'mwp_task_max_runners' ) ?: 4;
		
		/* Attempt to increase execution time if it is set to less than what is needed to spin up all task runners (1 per minute) */
		if ( $max_execution_time < ( 60 * $max_task_runners ) ) {
			if ( set_time_limit( ( 60 * $max_task_runners ) ) ) {
				$max_execution_time = ( 60 * $max_task_runners );
			}
		}
		
		Task::runMaintenance();
		$task = null;
		
		/* Log Fatalities */
		register_shutdown_function( function() use ( &$task ) 
		{
			if ( $task instanceof Task and $task->running ) 
			{
				$error = error_get_last();
				$task->log( 'Runtime error interruption.' );
				$task->log( print_r( $error, true ) );
				$task->fails = $task->fails + 1;
				$task->next_start = time() + 180;
				$task->running = 0;
				$task->setData( 'status', 'Failed' );
				$task->save();
				$task->shutdown();
			}
		});
		
		/* Run tasks */
		while 
		( 
			/* We have a task to run */
			$task = Task::popQueue() and
			
			/* and we have time to run it */
			( time() - $begin_time < $max_execution_time - 10 )
		)
		{
			$task->breaker = 0;
			
			$task->last_start = time();
			$task->last_iteration = time();
			$task->running = 1;
			$task->save();
			
			if ( has_action( $task->action ) )
			{
				// Allow the task to bootstrap if needed
				$task->setup();
				
				try
				{
					while
					( 
						! $task->completed and ! $task->aborted and             // task is not yet complete
						time() >= $task->next_start and                         // task has not been rescheduled for the future
						( time() - $begin_time < $max_execution_time - 10 )     // there is still time to run it
					)
					{
						/**
						 * Even though we are enforcing an overall max_execution_time limit, allow each individual iteration
						 * to use a full execution time block if needed before being killed by the system.
						 */
						set_time_limit( $task_max_execution_time );
						
						$task->breaker = $task->breaker + 1;
						$task->execute();
						$task->last_iteration = time();
						$task->save();
						
						if ( $task->breaker >= 1000 ) {
							$task->log( 'Circuit breaker switched after ' . $task->breaker . ' iterations. Set $task->breaker = 0 in the task callback to circumvent.' );
							$task->fails = $task->fails + 1;
							$task->failover = true;
							$task->next_start = time() + 180;
						}						
					}
					
					if ( $task->aborted )
					{
						$task->setData( 'status', 'Aborted' );
						$task->log( 'Task aborted.' );
						$task->running = 0;
						$task->fails = 3;
						$task->save();
					}
					else
					{
						if ( $task->completed ) {
							$task->setData( 'status', 'Completed' );
							$task->log( 'Task Complete.' );
						} else {
							$task->log( 'Task suspended.' );
						}
						
						if ( ! $task->failover ) {
							$task->fails = 0;
						}
						
						$task->running = 0;
						$task->save();
					}
				}
				catch( \Exception $e )
				{
					$task->running = 0;
					$task->fails = 3;
					$task->setData( 'status', 'Failed' );
					$task->log( 'Runtime exception encountered: ' . $e->getMessage() );
					$task->save();
				}
				
				// Allow the task to shutdown if needed
				$task->shutdown();
			}
			else
			{
				$task->setData( 'status', 'Unavailable' );
				$task->running = 0;
				$task->fails = 3;
				$task->log( 'Action callback not available for this task: ' . $task->action );
				$task->save();
			}
		}
	}
	
	/**
	 * Perform task queue maintenance
	 *
	 * @MWP\WordPress\Action( for="mwp_framework_queue_maintenance" )
	 *
	 * @return	void
	 */
	public function runTasksMaintenance()
	{
		Task::runMaintenance();
	}
		
	/**
	 * Generate a new plugin from the boilerplate
	 *
	 * @api
	 *
	 * @param	array		$data		New plugin data
	 * @return	Plugin
	 * @throws	\InvalidArgumentException	Throws exception when invalid plugin data is provided
	 * @throws	\ErrorException			Throws an error when the plugin data conflicts with another plugin
	 */
	public function createPlugin( $data )
	{
		$plugin_dir = $data[ 'slug' ];
		$plugin_name = $data[ 'name' ];
		$plugin_vendor = $data[ 'vendor' ];
		$plugin_namespace = $data[ 'namespace' ];
		
		if ( ! $data[ 'slug' ] )      { throw new \InvalidArgumentException( 'Invalid plugin slug.' ); }
		if ( ! $data[ 'name' ] )      { throw new \InvalidArgumentException( 'No plugin name provided.' );  }
		if ( ! $data[ 'vendor' ] )    { throw new \InvalidArgumentException( 'No vendor name provided.' );  }
		if ( ! $data[ 'namespace' ] ) { throw new \InvalidArgumentException( 'No namespace provided.' );    }
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/mwp-framework/boilerplate' ) )
		{
			throw new \ErrorException( "Boilerplate plugin not present. Can't create a new one.", 1 );
		}
		
		if ( is_dir( WP_PLUGIN_DIR . '/' . $plugin_dir ) )
		{
			throw new \ErrorException( 'Plugin directory is already being used.', 2 );
		}
		
		$this->copyPluginFiles( $this->getPath() . '/boilerplate', WP_PLUGIN_DIR . '/' . $plugin_dir, $data );
		
		/* Create an alias file for the test suite, etc... */
		file_put_contents( WP_PLUGIN_DIR . '/' . $plugin_dir . '/' . $data[ 'slug' ] . '.php', "<?php\n\n
/* Load framework for tests */
if ( defined( 'DIR_TESTDATA' ) ) {
	\$plugin_dir = dirname( dirname( __FILE__ ) );
	if ( ! file_exists( \$plugin_dir . '/mwp-framework/plugin.php' ) ) {
		die( 'Error: MWP Framework must be present in ' . \$plugin_dir . '/mwp-framework to run tests on this plugin.' );
	}
	
	require_once \$plugin_dir . '/mwp-framework/plugin.php';
}

require_once 'plugin.php';" );
		
		/* Include autoloader so we can instantiate the plugin */
		include_once WP_PLUGIN_DIR . '/' . $plugin_dir . '/vendor/autoload.php';
		
		$pluginClass = $plugin_namespace . '\Plugin';
		$plugin = $pluginClass::instance();
		$plugin->setPath( WP_PLUGIN_DIR . '/' . $plugin_dir );
		$plugin->setData( 'plugin-meta', $data );
		
		return $plugin;
	}
	
	/**
	 * Copy boilerplate plugin and customize the metadata
	 *
	 * @param       string   $source    Source path
	 * @param       string   $dest      Destination path
	 * @param	    array    $data      Plugin metadata
	 * @return      bool     Returns TRUE on success, FALSE on failure
	 */
	protected function copyPluginFiles( $source, $dest, $data )
	{
		// Simple copy for a file
		if ( is_file( $source ) ) 
		{
			if ( ! in_array( basename( $source ), array( 'README.md' ) ) )
			{
				copy( $source, $dest );
				
				$pathinfo = pathinfo( $dest );
				if ( isset( $pathinfo[ 'extension' ] ) and in_array( $pathinfo[ 'extension' ], array( 'php', 'js', 'json', 'css' ) ) )
				{
					file_put_contents( $dest, $this->replaceMetaContents( file_get_contents( $dest ), $data ) );
				}
				
				return true;
			}
			
			return false;
		}

		// Make destination directory
		if ( ! is_dir( $dest ) ) 
		{
			mkdir( $dest );
		}

		// Loop through the folder
		$dir = dir( $source );
		while ( false !== $entry = $dir->read() ) 
		{
			// Skip pointers & special dirs
			if ( in_array( $entry, array( '.', '..', '.git' ) ) )
			{
				continue;
			}

			// Deep copy directories
			if ( $dest !== "$source/$entry" ) 
			{
				$this->copyPluginFiles( "$source/$entry", "$dest/$entry", $data );
			}
		}

		// Clean up
		$dir->close();
		return true;
	}
	
	/**
	 * Create new javascript module
	 *
	 * @param	string		$slug		The plugin slug
	 * @param	string		$name		The javascript module name
	 * @return	string
	 * @throws	\ErrorException
	 */
	public function createJavascript( $slug, $name )
	{
		if ( ! file_exists( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/assets/js/main.js' ) )
		{
			throw new \ErrorException( "The boilerplate plugin is not present. \nTry using: $ wp mwp update-boilerplate" );
		}
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/assets/js' ) )
		{
			throw new \ErrorException( 'Javascript directory is not valid: ' . $slug . '/assets/js' );
		}
		
		if ( substr( $name, -3 ) === '.js' )
		{
			$name = substr( $name, 0, strlen( $name ) - 3 );
		}
		
		$javascript_file = WP_PLUGIN_DIR . '/' . $slug . '/assets/js/' . $name . '.js';
		
		if ( file_exists( $javascript_file ) )
		{
			throw new \ErrorException( "The javascript file already exists: " . $slug . '/assets/js/' . $name . '.js' );
		}
		
		if ( ! copy( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/assets/js/main.js', $javascript_file ) )
		{
			throw new \ErrorException( 'Error copying file to destination: ' . $slug . '/assets/js/' . $name . '.js' );
		}
		
		$plugin_data_file = WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php';
		
		if ( file_exists( $plugin_data_file ) )
		{
			$plugin_data = json_decode( include $plugin_data_file, TRUE );
			file_put_contents( $javascript_file, $this->replaceMetaContents( file_get_contents( $javascript_file ), $plugin_data ) );
		}

		return $javascript_file;
	}
	
	/**
	 * Create new stylesheet
	 *
	 * @param	string		$slug		The plugin slug
	 * @param	string		$name		The stylesheet name
	 * @return	string
	 * @throws	\ErrorException
	 */
	public function createStylesheet( $slug, $name )
	{
		if ( ! file_exists( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/assets/css/style.css' ) )
		{
			throw new \ErrorException( "The boilerplate plugin is not present. \nTry using: $ wp mwp update-boilerplate" );
		}
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/assets/css' ) )
		{
			throw new \ErrorException( 'Stylesheet directory is not valid: ' . $slug . '/assets/css' );
		}
		
		if ( substr( $name, -4 ) === '.css' )
		{
			$name = substr( $name, 0, strlen( $name ) - 4 );
		}
		
		$stylesheet_file = WP_PLUGIN_DIR . '/' . $slug . '/assets/css/' . $name . '.css';
		
		if ( file_exists( $stylesheet_file ) )
		{
			throw new \ErrorException( "The stylesheet file already exists: " . $slug . '/assets/css/' . $name . '.css' );
		}
		
		if ( ! copy( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/assets/css/style.css', $stylesheet_file ) )
		{
			throw new \ErrorException( 'Error copying file to destination: ' . $slug . '/assets/css/' . $name . '.css' );
		}
		
		$plugin_data_file = WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php';
		
		if ( file_exists( $plugin_data_file ) )
		{
			$plugin_data = json_decode( include $plugin_data_file, TRUE );
			file_put_contents( $stylesheet_file, $this->replaceMetaContents( file_get_contents( $stylesheet_file ), $plugin_data ) );
		}
		
		return $stylesheet_file;
	}

	/**
	 * Create new template snippet
	 *
	 * @param	string		$slug		The plugin slug
	 * @param	string		$name		The template name
	 * @return	string
	 * @throws	\ErrorException
	 */
	public function createTemplate( $slug, $name )
	{
		if ( ! file_exists( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/templates/snippet.php' ) )
		{
			throw new \ErrorException( "The boilerplate plugin is not present. \nTry using: $ wp mwp update-boilerplate" );
		}
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/templates' ) )
		{
			throw new \ErrorException( 'Template directory is not valid: ' . $slug . '/templates' );
		}
		
		if ( substr( $name, -4 ) === '.php' )
		{
			$name = substr( $name, 0, strlen( $name ) - 4 );
		}
		
		$template_file = WP_PLUGIN_DIR . '/' . $slug . '/templates/' . $name . '.php';
		
		if ( file_exists( $template_file ) )
		{
			throw new \ErrorException( "The template file already exists: " . $slug . '/templates/' . $name . '.php' );
		}
		
		$parts = explode( '/', $name );		
		$basedir = WP_PLUGIN_DIR . '/' . $slug . '/templates';
		$filename = array_pop( $parts );
		foreach( $parts as $dir )
		{
			$basedir .= '/' . $dir;
			if ( ! is_dir( $basedir ) )
			{
				mkdir( $basedir );
			}
		}
		
		if ( ! copy( WP_PLUGIN_DIR . '/mwp-framework/boilerplate/templates/snippet.php', $template_file ) )
		{
			throw new \ErrorException( 'Error copying file to destination: ' . $slug . '/templates/' . $name . '.php' );
		}
		
		$template_contents = file_get_contents( $template_file );
		$template_contents = str_replace( "'snippet'", "'{$name}'", $template_contents );

		$plugin_data_file = WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php';		
		if ( file_exists( $plugin_data_file ) )
		{
			$plugin_data = json_decode( include $plugin_data_file, TRUE );
			$template_contents = $this->replaceMetaContents( $template_contents, $plugin_data );
		}
		
		file_put_contents( $template_file, $template_contents );
		
		return $template_file;
	}
	
	/**
	 * Create new php class
	 *
	 * @param	string		$slug		The plugin slug
	 * @param	string		$name		The php classname
	 * @return	string
	 * @throws	\ErrorException
	 */
	public function createClass( $slug, $name, $type='generic' )
	{
		$plugin_data_file = WP_PLUGIN_DIR . '/' . $slug . '/data/plugin-meta.php';
		
		if ( ! file_exists( $plugin_data_file ) )
		{
			throw new \ErrorException( "No metadata available for this plugin. Namespace unknown." );
		}
		
		$plugin_data = json_decode( include $plugin_data_file, TRUE );

		if ( ! isset( $plugin_data[ 'namespace' ] ) )
		{
			throw new \ErrorException( "Namespace not defined in the plugin metadata." );
		}
		
		$base_namespace = $namespace = $plugin_data[ 'namespace' ];
		$name = trim( str_replace( $namespace, '', $name ), '\\' );
		$parts = explode( '\\', $name );
		$classname = array_pop( $parts );
		
		if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug . '/classes' ) )
		{
			throw new \ErrorException( 'Class directory is not valid: ' . 'plugins/' . $slug . '/classes' );
		}
		
		$basedir = WP_PLUGIN_DIR . '/' . $slug . '/classes';
		foreach( $parts as $dir )
		{
			$basedir .= '/' . $dir;
			if ( ! is_dir( $basedir ) )
			{
				mkdir( $basedir );
			}
			$namespace .= '\\' . $dir;
		}
		
		$class_file = $basedir . '/' . $classname . '.php';
		
		if ( file_exists( $class_file ) )
		{
			throw new \ErrorException( "The class file already exists: " . str_replace( WP_PLUGIN_DIR, '', $class_file ) );
		}
		
		$version_tag = '{' . 'build_version' . '}';
		$class_contents = '';
		
		switch( $type ) 
		{
			case 'model':
				$pieces = explode( '-', $slug );
				$table_name = strtolower( end( $pieces ) . '_' . $classname );
				$class_contents = <<<CLASS
<?php
/**
 * $classname Model [ActiveRecord]
 *
 * Created:   {date_time}
 *
 * @package:  {plugin_name}
 * @author:   {plugin_author}
 * @since:    $version_tag
 */
namespace $namespace;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Pattern\ActiveRecord;

/**
 * $classname Class
 */
class _$classname extends ActiveRecord
{
	/**
	 * @var	array		Multitons cache (needs to be defined in subclasses also)
	 */
	protected static \$multitons = array();
	
	/**
	 * @var	string		Table name
	 */
	protected static \$table = '$table_name';
	
	/**
	 * @var	array		Table columns
	 */
	protected static \$columns = array(
		'id',
		'title' => [ 'type' => 'varchar', 'length' => 255 ],
	);
	
	/**
	 * @var	string		Table primary key
	 */
	protected static \$key = 'id';
	
	/**
	 * @var	string		Table column prefix
	 */
	protected static \$prefix = '';
	
	/**
	 * @var bool		Site specific table? (for multisites)
	 */
	protected static \$site_specific = FALSE;
	
	/**
	 * @var	string
	 */
	protected static \$plugin_class = '{$base_namespace}\Plugin';
	
	/**
	 * @var	string
	 */
	public static \$sequence_col;
	
	/**
	 * @var	string
	 */
	public static \$parent_col;

	/**
	 * @var	string
	 */
	public static \$lang_singular = 'Record';
	
	/**
	 * @var	string
	 */
	public static \$lang_plural = 'Records';
	
	/**
	 * @var	string
	 */
	public static \$lang_view = 'View';

	/**
	 * @var	string
	 */
	public static \$lang_create = 'Create';

	/**
	 * @var	string
	 */
	public static \$lang_edit = 'Edit';
	
	/**
	 * @var	string
	 */
	public static \$lang_delete = 'Delete';

}

CLASS;
				break;
				
			case 'singleton':
				$class_contents = <<<CLASS
<?php
/**
 * $classname Class [Singleton]
 *
 * Created:   {date_time}
 *
 * @package:  {plugin_name}
 * @author:   {plugin_author}
 * @since:    $version_tag
 */
namespace $namespace;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Pattern\Singleton;

/**
 * $classname
 */
class _$classname extends Singleton
{
	/**
	 * @var self
	 */
	protected static \$_instance;
	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected \$plugin;
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		if ( isset( \$this->plugin ) ) {
			return \$this->plugin;
		}
		
		\$this->setPlugin( \MWP\Boilerplate\Plugin::instance() );
		
		return \$this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin \$plugin=NULL )
	{
		\$this->plugin = \$plugin;
		return \$this;
	}

}

CLASS;
				break;
				
			case 'generic':
			default:
				$class_contents = <<<CLASS
<?php
/**
 * $classname Class
 *
 * Created:   {date_time}
 *
 * @package:  {plugin_name}
 * @author:   {plugin_author}
 * @since:    $version_tag
 */
namespace $namespace;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * $classname
 */
class _$classname
{
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected \$plugin;
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		if ( isset( \$this->plugin ) ) {
			return \$this->plugin;
		}
		
		\$this->setPlugin( \MWP\Boilerplate\Plugin::instance() );
		
		return \$this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin \$plugin=NULL )
	{
		\$this->plugin = \$plugin;
		return \$this;
	}
	
}

CLASS;
				break;				
		}
		
		file_put_contents( $class_file, $this->replaceMetaContents( $class_contents, $plugin_data ) );
	
		return $class_file;
	}

	/**
	 * Replace meta contents
	 *
	 * @param	string		$source		The source code to replace meta contents in
	 * @param	array		$data		Plugin meta data
	 * @return	string
	 */
	public function replaceMetaContents( $source, $data )
	{
		$data = array_merge( array( 
			'name' => '',
			'url' => '',
			'description' => '',
			'namespace' => '',
			'slug' => '',
			'vendor' => '',
			'author' => '',
			'author_url' => '',
			'date' => date( 'F j, Y' ),
			), $data );
			
		return strtr( $source, array
		( 
			'b7f88d4569eea7ab0b52f6a8c0e0e90c'  => md5( $data[ 'slug' ] ),
			'MWP\Boilerplate'           => $data[ 'namespace' ],
			'MWP\\\Boilerplate'         => str_replace( '\\', '\\\\', $data[ 'namespace' ] ),
			'mwp/boilerplate'           => strtolower( str_replace( '\\', '/', $data[ 'namespace' ] ) ),
			'BoilerplatePlugin'                 => str_replace( '\\', '', $data[ 'namespace'] ) . 'Plugin',
			'{vendor_name}'                     => $data[ 'vendor' ],
			'{plugin_name}'                     => $data[ 'name' ],
			'{plugin_url}'                      => $data[ 'url' ],
			'{plugin_slug}'                     => $data[ 'slug' ],
			'{plugin_description}'              => $data[ 'description' ],
			'{plugin_dir}'                      => $data[ 'slug' ],
			'{plugin_author}'                   => $data[ 'author' ],
			'{plugin_author_url}'               => $data[ 'author_url' ],
			'{date_time}'                       => $data[ 'date' ],						
		) );
	}

}
