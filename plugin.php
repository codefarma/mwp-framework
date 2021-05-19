<?php
/**
 * Plugin Name: MWP Application Framework for WordPress
 * Version: 2.2.14
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

					$taskFilters = [ [ 'task_blog_id=%d', get_current_blog_id() ] ];
					if ( ! isset( $_REQUEST['tbl_id'] ) ) {
						$taskFilters[] = array( 'task_fails<3 AND task_completed=0' );
					}

					\MWP\Framework\Task::setControllerClass( \MWP\Framework\Controller\Tasks::class );
					\MWP\Framework\Task::createController('admin', [
						'adminPage' => [
							'type' => 'management',
							'slug' => 'mwp-fw-tasks',
							'menu' => 'MWP Task Runner',
							'title' => __('Tasks Management', 'mwp-framework'),
						],
						'getActions' => function() { return array(); },
						'tableConfig' => [
							'constructor' => [
								'ajax' => true,
							],
							'hardFilters' => $taskFilters,
							'bulkActions' => [
								'runNext' => 'Run Next', 
								'unlock' => 'Unlock', 
								'delete' => 'Delete',
							],
							'sortBy' => 'task_running DESC, task_completed ASC, task_next_start ASC, task_priority',
							'sortOrder' => 'DESC',
							'columns' => [
								'task_action'       => __( 'Task Item', 'mwp-framework' ), 
								'task_last_start'   => __( 'Last Started', 'mwp-framework' ), 
								'task_next_start'   => __( 'Next Start', 'mwp-framework' ), 
								'stage'             => __( 'Stage', 'mwp-framework' ), 
								'task_fails'        => __( 'Fails', 'mwp-framework' ), 
								'task_data'         => __( 'Status', 'mwp-framework' ),
								'task_priority'     => __( 'Priority', 'mwp-framework' ),
							],
							'sortable' => [
								'task_action'       => array( 'task_action', false ),
								'task_last_start'   => array( 'task_last_start', false ), 
								'task_next_start'   => array( 'task_next_start', false ), 
								'task_priority'     => array( 'task_priority', false ),
								'task_fails'        => array( 'task_fails', false ),
							],
							'searchable' => [
								'task_action' => array( 'type' => 'contains', 'combine_words' => 'and' ),
								'task_tag'    => array( 'type' => 'contains', 'combine_words' => 'and' ),
								'task_data'   => array( 'type' => 'contains' ),
							],
							'handlers' => [
								'task_action' => function( $task ) use ( $framework ) {
									return $framework->getTemplateContent( 'views/management/tasks/task-title', array( 'task' => \MWP\Framework\Task::loadFromRowData( $task ) ) );
								},
								'task_last_start' => function( $task ) {
									$taskObj = \MWP\Framework\Task::loadFromRowData( $task );			
									return $taskObj->getLastStartForDisplay();
								},
								'task_next_start' => function( $task ) {
									$taskObj = \MWP\Framework\Task::loadFromRowData( $task );			
									return $taskObj->getNextStartForDisplay();
								},
								'stage' => function( $task ) {
									if ( $task['task_running'] ) {
										return "<span class='mwp-bootstrap'>
											<span class='label label-info' style='font-size:0.9em'>" . 
												__( "Running", 'mwp-framework' ) .
											"</span>
										</span>";
									}

									if ( $task['task_completed'] ) {
										return "<span class='mwp-bootstrap'>
											<span class='label label-success' style='font-size:0.9em'>" . 
												__( "Complete", 'mwp-framework' ) .
											"</span>
										</span>";
									}
									
									if ( $task['task_fails'] >= 3 ) {
										return "<span class='mwp-bootstrap'>
											<span class='label label-danger' style='font-size:0.9em'>" . 
												__( "Failed", 'mwp-framework' ) .
											"</span>
										</span>";
									}

									return "<span class='mwp-bootstrap'>
										<span class='label label-primary' style='font-size:0.9em'>" . 
											__( "Queued", 'mwp-framework' ) .
										"</span>
									</span>";
								},
								'task_data' => function( $task ) {
									$taskObj = \MWP\Framework\Task::loadFromRowData( $task );			
									$status = $taskObj->getStatusForDisplay();
									return $status;
								},
							],
							'extras' => [
								'status_filter' => [
									'init' => function( $table ) {
										$status = $_REQUEST['status'] ?? 'pending';
										$status_filter = NULL;
										
										switch( $status ) {
											case 'pending':
												$status_filter = array( 'task_fails<3 AND task_completed=0' );
												break;

											case 'running':
												$status_filter = array( 'task_running=1' );
												break;

											case 'queued':
												$status_filter = array( 'task_running=0 AND task_completed=0 AND task_fails<3' );
												break;
												
											case 'completed':
												$status_filter = array( 'task_completed>0' );
												break;
												
											case 'failed':
												$status_filter = array( 'task_fails>=3' );
												break;
										}

										if ( $status_filter ) {
											$table->addFilter( $status_filter );
										}
									},
									'output' => function( $table ) {
										$status = $_REQUEST['status'] ?? 'pending';
										$statuses = [
											'pending' => 'Pending Tasks',
											'running' => 'Running Tasks', 
											'queued' => 'Queueud Tasks',
											'completed' => 'Completed Tasks',
											'failed' => 'Failed Tasks',
										];

										$options = array_map( function( $val, $title ) use ( $status ) { 
											return sprintf( 
												'<option value="%s" %s>%s</option>', 
												$val, 
												$status == $val ? 'selected' : '', 
												$title 
											); 
										}, array_keys( $statuses ), $statuses );

										echo 'Show: 
										<select name="status" onchange="jQuery(this).closest(\'form\').submit()">' . 
											implode( '', $options ) . 
										'</select>';
									},
								],
								'status_count' => [
									'output' => function( $table ) {
										$blog_id = get_current_blog_id();
										$pending = \MWP\Framework\Task::countWhere(['task_completed=0 AND task_fails<3 AND task_blog_id=%d', $blog_id]);
										$running = \MWP\Framework\Task::countWhere(['task_running>0 AND task_blog_id=%d', $blog_id]);
										$queued = \MWP\Framework\Task::countWhere(['task_running=0 AND task_completed=0 AND task_fails<3 AND task_blog_id=%d', $blog_id]);
										$completed = \MWP\Framework\Task::countWhere(['task_completed>0 AND task_blog_id=%d', $blog_id]);
										$failed = \MWP\Framework\Task::countWhere(['task_fails>=3 AND task_blog_id=%d', $blog_id]);

										echo "<div class='mwp-bootstrap' style='display: inline-block; margin:0 15px; font-size:1.2em;'> 
											<span class='label label-default'>{$pending} pending</span> | 
											<span class='label label-info'>{$running} running</span> 
											<span class='label label-primary'>{$queued} queued</span> | 
											<span class='label label-success'>{$completed} completed</span> 
											<span class='label label-danger'>{$failed} failed</span>
										</div>";
									}
								],
								'auto_refresh' => [
									'output' => function( $table ) {
										$checked = $_REQUEST['auto_refresh'] ? 'checked' : '';
										$interval = intval( $_REQUEST['auto_refresh_int'] ?? 30 );
										$intervalMS = intval($interval * 1000);
										$intervalMS = $intervalMS < 1000 ? 1000 : $intervalMS;
										echo "<input type='checkbox' name='auto_refresh' value='1' {$checked}/ onchange=\"jQuery(this).closest('form').submit()\"> Auto Refresh every <input type='text' name='auto_refresh_int' value='{$interval}' style='width: 50px' /> seconds";
										echo "<script>if ( window.taskRefresh ) { clearTimeout(window.taskRefresh); }</script>";
										
										if ( $checked ) {
											echo "<script>
												window.taskRefresh = setTimeout(function() {
													jQuery('.management.records.index form').submit();
												}, {$intervalMS});
											</script>";
										}
									}
								],
							],
						],
					]);
					
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
