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
class _Tasks extends \MWP\Framework\Pattern\Singleton
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
	 * Tasks Management Index
	 * 
	 * @return	string
	 */
	public function do_index()
	{
		$table = Task::createDisplayTable();
		
		$table->columns = array
		( 
			'task_action'       => __( 'Task Item', 'mwp-framework' ), 
			'task_last_start'   => __( 'Last Started', 'mwp-framework' ), 
			'task_next_start'   => __( 'Next Start', 'mwp-framework' ), 
			'task_running'      => __( 'Activity', 'mwp-framework' ), 
			'task_fails'        => __( 'Fails', 'mwp-framework' ), 
			'task_data'         => __( 'Status', 'mwp-framework' ),
			'task_priority'     => __( 'Priority', 'mwp-framework' ),
		);
		
		$table->sortableColumns = array(
			'task_action'       => array( 'task_action', false ),
			'task_last_start'   => array( 'task_last_start', false ), 
			'task_next_start'   => array( 'task_next_start', false ), 
			'task_running'      => array( 'task_running', false ), 
			'task_fails'        => array( 'task_fails', false ), 
			'task_priority'     => array( 'task_priority', false ),			
		);
		
		$table->searchableColumns = array(
			'task_action' => array( 'type' => 'contains', 'combine_words' => 'and' ),
			'task_tag'    => array( 'type' => 'contains', 'combine_words' => 'and' ),
			'task_data'   => array( 'type' => 'contains' ),
		);
		
		$table->bulkActions = array( 
			'runNext' => 'Run Next', 
			'unlock' => 'Unlock', 
			'delete' => 'Delete'  
		);
		
		$table->sortBy = 'task_priority DESC, task_next_start';
		$table->sortOrder = 'ASC';
		
		$table->handlers = array
		(
			'task_action' => function( $task )
			{
				return $this->getPlugin()->getTemplateContent( 'views/management/tasks/task-title', array( 'task' => Task::loadFromRowData( $task ) ) );
			},
			'task_last_start' => function( $task ) 
			{
				$taskObj = Task::loadFromRowData( $task );			
				return $taskObj->getLastStartForDisplay();
			},
			'task_next_start' => function( $task ) 
			{
				$taskObj = Task::loadFromRowData( $task );			
				return $taskObj->getNextStartForDisplay();
			},
			'task_running' => function( $task ) 
			{
				if ( $task[ 'task_running' ] ) {
					return __( "Running", 'mwp-framework' );
				}
				
				return __( "Idle", 'mwp-framework' );
			},
			'task_data' => function( $task ) 
			{
				$taskObj = Task::loadFromRowData( $task );			
				$status = $taskObj->getStatusForDisplay();
				return $status;
			},
		);
		
		// Default to all non-completed tasks
		$where = array( 'task_completed=0 AND task_running=0 AND task_blog_id=%d AND task_fails<3', get_current_blog_id() );
		
		if ( isset( $_REQUEST[ 'status' ] ) )
		{
			switch( $_REQUEST[ 'status' ] )
			{
				case 'running':
				
					$where = array( 'task_running=1 AND task_blog_id=%d', get_current_blog_id() );
					break;
					
				case 'completed':
				
					$where = array( 'task_completed>0 AND task_blog_id=%d', get_current_blog_id() );
					break;
					
				case 'failed':
				
					$where = array( 'task_fails>=3 AND task_blog_id=%d', get_current_blog_id() );
					break;
			}
		}
		
		$table->read_inputs();
		$table->prepare_items( $where );
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/tasks', array( 'table' => $table ) );
	}
	
	/**
	 * Show the status and logs for a task
	 * 
	 * @return void
	 */
	public function do_viewtask()
	{
		if ( isset( $_REQUEST[ 'task_id' ] ) )
		{
			try
			{
				$task = Task::load( $_REQUEST[ 'task_id' ] );
			}
			catch( \OutOfRangeException $e )
			{
				$task = NULL;
			}
		}
		
		echo $this->getPlugin()->getTemplateContent( 'views/management/task-item', array( 'task' => $task ) );
	}
	
}
