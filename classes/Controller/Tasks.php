<?php
/**
 * Plugin Class File
 *
 * Created:   March 2, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.2.4
 */
namespace MWP\Framework\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Task;

/**
 * Tasks Controller
 *
 * @MWP\WordPress\AdminPage( title="Tasks Management", menu="MWP Task Runner", slug="mwp-fw-tasks", type="management" )
 */
class _Tasks extends \MWP\Framework\Helpers\ActiveRecordController
{
	/**
	 * @var	object			Singleton instance
	 */
	protected static $_instance;
	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin;
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * @param	\MWP\Framework\Plugin	$plugin			The plugin to associate this class with, or NULL to auto-associate
	 * @return	void
	 */
	public function __construct( \MWP\Framework\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin ?: \MWP\Framework\Framework::instance();
	}
	
	/**
	 * Show the status and logs for a task
	 * 
	 * @return void
	 */
	public function do_view( $record=NULL )
	{
		if ( isset( $_REQUEST[ 'id' ] ) )
		{
			try
			{
				$task = Task::load( $_REQUEST[ 'id' ] );
			}
			catch( \OutOfRangeException $e )
			{
				$task = NULL;
			}
		}
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/task-item', array( 'task' => $task ) );
	}
	
}
