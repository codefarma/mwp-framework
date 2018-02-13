<?php
/**
 * Plugin Class File
 *
 * Created:   December 18, 2016
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.0.1
 */
namespace MWP\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use \MWP\Framework\Pattern\ActiveRecord;
use \MWP\Framework\Framework;

/**
 * Task Class
 */
class _Task extends ActiveRecord
{
	/**
	 * @var	array		Multitons cache (needs to be defined in subclasses also)
	 */
	protected static $multitons = array();
	
	/**
	 * @var	string		Table name
	 */
	public static $table = 'mwp_queued_tasks';
	
	/**
	 * @var	array		Table columns
	 */
	public static $columns = array(
		'id',
		'action',
		'data' => array( 'format' => 'JSON' ),
		'priority',
		'next_start',
		'running',
		'last_start',
		'last_iteration',
		'tag',
		'fails',
		'completed',
		'blog_id',
	);
	
	/**
	 * @var	string		Table primary key
	 */
	public static $key = 'id';
	
	/**
	 * @var	string		Table column prefix
	 */
	public static $prefix = 'task_';
		
	/**
	 * @var bool		Task aborted
	 */
	public $aborted = false;
	
	/**
	 * @var	int			Circuit Breaker
	 */
	public $breaker;
	
	/**
	 * @var	int			Failover
	 */
	public $failover = false;
	
	/**
	 * Execute this task
	 *
	 * @return	void
	 */
	public function execute()
	{
		do_action( $this->action, $this );
	}
	
	/**
	 * Get task title
	 */
	public function getTitle()
	{
		$implied_title = ucwords( str_replace( '_', ' ', $this->action ) );
		return apply_filters( $this->action . '_title', $implied_title, $this );
	}
	
	/**
	 * Execute a setup action
	 * 
	 * @return	void
	 */
	public function setup()
	{
		do_action( $this->action . '_setup', $this );
	}
	
	/**
	 * Save a log message
	 *
	 * @param	string			$message			The message to log
	 */
	public function log( $message )
	{
		$logs = $this->getData( 'logs' );
		if ( ! is_array( $logs ) )
		{
			$logs = array();
		}
		
		$logs[] = array(
			'time' => time(),
			'message' => $message,
		);
		
		$this->setData( 'logs', $logs );
		$this->save();
	}
	
	/**
	 * Complete this task
	 *
	 * @return	void
	 */
	public function complete()
	{
		$this->completed = time();
		do_action( $this->action . '_complete', $this );
	}
	
	/**
	 * Abort the task
	 *
	 * @return 	void
	 */
	public function abort()
	{
		$this->aborted = true;
		do_action( $this->action . '_abort', $this );
	}
	
	/**
	 * Unlock the task
	 *
	 * @return	void
	 */
	public function unlock()
	{
		if ( $this->fails >= 3 )
		{
			$this->fails = 0;
			$this->save();
		}		
	}
	
	/**
	 * Run Next
	 *
	 * Increase the task priority to run next
	 *
	 * @return	void
	 */
	public function runNext()
	{
		if ( ! $this->completed )
		{
			$this->unlock();
			$this->running = 0;
			$this->next_start = 0;
			$this->priority = 99;
			$this->save();
		}		
	}
	
	/**
	 * Set Task Data
	 *
	 * @param	string			$key			The data key to set
	 * @param	mixed			$value			The value to set
	 * @return	void
	 */
	public function setData( $key, $value )
	{
		$data = $this->data;
		$data[ $key ] = $value;
		$this->data = $data;
	}
	
	/**
	 * Get Task Data
	 *
	 * @param	string			$key			The data key to set
	 * @return	mixed
	 */
	public function getData( $key )
	{
		$data = $this->data;
		if ( isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}
		
		return NULL;
	}
	
	/**
	 * Set the task status
	 *
	 * @param	string				$status				The task status to display in the admin
	 */
	public function setStatus( $status )
	{
		$data = $this->data;
		$data[ 'status' ] = (string) $status;
		$this->data = $data;
		$this->save();
	}
	
	/**
	 * Get status for display
	 * 
	 * @return	string
	 */
	public function getStatusForDisplay()
	{
		$status = $this->getData( 'status' ) ?: '---';
		$color = $this->completed ? 'green' : ( $this->fails > 2 ? 'red' : 'inherit' );
		return apply_filters( 'mwp_task_status_display', "<span style='color:{$color}' class='task-status-" . sanitize_title( $status ) . "'>{$status}</span>", $this );
	}

	/**
	 * Get next start for display
	 * 
	 * @return	string
	 */
	public function getNextStartForDisplay()
	{
		if ( $this->completed )
		{
			$next_start = __( 'N/A', 'mwp-framework' );
		}
		else 
		{
			if ( $this->next_start > 0 )
			{
				$next_start = get_date_from_gmt( date( 'Y-m-d H:i:s', $this->next_start ), 'F j, Y H:i:s' );
			}
			else
			{
				$next_start = __( 'ASAP', 'mwp-framework' );
			}
		}
		
		return apply_filters( 'mwp_task_next_start_display', $next_start, $this );
	}

	/**
	 * Get last start for display
	 * 
	 * @return	string
	 */
	public function getLastStartForDisplay()
	{
		if ( $this->last_start > 0 )
		{
			$last_start = get_date_from_gmt( date( 'Y-m-d H:i:s', $this->last_start ), 'F j, Y H:i:s' );
		}
		else
		{
			$last_start = __( 'Never', 'mwp-framework' );
		}
		
		return apply_filters( 'mwp_task_last_start_display', $last_start, $this );
	}

	/**
	 * Add a task to the queue
	 *
	 * @param	array|string		$config			Task configuration options
	 * @param	mixed				$data			Task data
	 * @return	Task
	 */
	public static function queueTask( $config, $data=NULL )
	{
		$task = new static;
		
		if ( is_array( $config ) )
		{
			if ( ! isset( $config[ 'action' ] ) )
			{
				return FALSE;
			}
			
			$task->action = $config[ 'action' ];
			
			if ( isset( $config[ 'tag' ] ) ) {
				$task->tag = $config[ 'tag' ];
			}
			
			if ( isset( $config[ 'priority' ] ) ) {
				$task->priority = $config[ 'priority' ];
			}
			
			if ( isset( $config[ 'next_start' ] ) ) {
				$task->next_start = $config[ 'next_start' ];
			}
		}
		
		if ( is_string( $config ) )
		{
			$task->action = $config;
		}
		
		$task->blog_id = get_current_blog_id();
		$task->data = $data;
		$task->log( 'Task queued.' );
		$task->save();
		
		return $task;
	}

	/**
	 * Delete tasks from queue based on action and or tag
	 *
	 * @param	string		$action			Delete all tasks with specific action
	 * @param	string		$tag			Delete all tasks with specific tag
	 * @return	void
	 */
	public static function deleteTasks( $action, $tag=NULL )
	{
		$db = Framework::instance()->db();
		
		if ( $action === NULL and $tag === NULL )
		{
			return;
		}
		
		/* Only action provided */
		if ( $tag === NULL )
		{
			$db->query( $db->prepare( "DELETE FROM  " . $db->base_prefix . static::$table . " WHERE task_action=%s AND task_blog_id=%d AND task_completed=0 AND task_running=0", $action, get_current_blog_id() ) );
		}
		
		/* Only tag provided */
		elseif ( $action === NULL )
		{
			$db->query( $db->prepare( "DELETE FROM  " . $db->base_prefix . static::$table . " WHERE task_tag=%s AND task_blog_id=%d AND task_completed=0 AND task_running=0", $tag, get_current_blog_id() ) );		
		}
		
		/* Both action and tag provided */
		else
		{
			$db->query( $db->prepare( "DELETE FROM  " . $db->base_prefix . static::$table . " WHERE task_action=%s AND task_tag=%s AND task_blog_id=%d AND task_completed=0 AND task_running=0", $action, $tag, get_current_blog_id() ) );
		}
	}
	
	/**
	 * Count tasks from queue based on action and or tag
	 *
	 * @param	string		$action			Count all tasks with specific action|NULL to ignore
	 * @param	string		$tag			Count all tasks with specific tag|NULL to ignore
	 * @param	string		$status			Status to count (pending,complete,running,failed)
	 * @return	int
	 */
	public static function countTasks( $action=NULL, $tag=NULL, $status='pending' )
	{
		$db = Framework::instance()->db();
		
		$status_clause = "task_completed=0 AND task_fails < 3 AND task_running=0";
		
		switch( $status ) 
		{
			case 'completed':
				$status_clause = "task_completed>0";
				break;
				
			case 'running':
				$status_clause = "task_running=1";
				break;
				
			case 'failed':
				$status_clause = "task_fails>=3";
				break;
		}
		
		if ( $action === NULL and $tag === NULL )
		{
			return $db->get_var( $db->prepare( "SELECT COUNT(*) FROM  " . $db->base_prefix . static::$table . " WHERE task_blog_id=%d AND {$status_clause}", get_current_blog_id() ) );
		}
		
		/* Only action provided */
		if ( $tag === NULL )
		{
			return $db->get_var( $db->prepare( "SELECT COUNT(*) FROM  " . $db->base_prefix . static::$table . " WHERE task_action=%s AND task_blog_id=%d AND {$status_clause}", $action, get_current_blog_id() ) );
		}
		
		/* Only tag provided */
		elseif ( $action === NULL )
		{
			return $db->get_var( $db->prepare( "SELECT COUNT(*) FROM  " . $db->base_prefix . static::$table . " WHERE task_tag=%s AND task_blog_id=%d AND {$status_clause}", $tag, get_current_blog_id() ) );		
		}
		
		/* Both action and tag provided */
		else
		{
			return $db->get_var( $db->prepare( "SELECT COUNT(*) FROM  " . $db->base_prefix . static::$table . " WHERE task_action=%s AND task_tag=%s AND task_blog_id=%d AND {$status_clause}", $action, $tag, get_current_blog_id() ) );
		}
	}

	/**
	 * Get the next task that needs to be run
	 *
	 * @return	Task|NULL
	 */
	public static function popQueue()
	{		
		$db = Framework::instance()->db();
		
		$running = $db->get_var( $db->prepare( "SELECT COUNT(*) FROM {$db->base_prefix}" . static::$table . " WHERE task_running=1 AND task_blog_id=%d", get_current_blog_id() ) );
		
		if ( $running >= Framework::instance()->getSetting( 'mwp_task_max_runners' ) ) {
			return null;
		}
		
		$row = $db->get_row( 
			$db->prepare( "
				SELECT * FROM {$db->base_prefix}" . static::$table . " 
					WHERE task_completed=0 AND task_running=0 AND task_next_start <= %d AND task_fails < 3 AND task_blog_id=%d
					ORDER BY task_priority DESC, task_last_start ASC, task_id ASC", time(), get_current_blog_id()
			), ARRAY_A
		);
		
		if ( $row === NULL )
		{
			return null;
		}
		
		return static::loadFromRowData( $row );
	}
	
	/**
	 * Unlock failed tasks
	 *
	 * @return	void
	 */
	public static function runMaintenance()
	{
		$db = Framework::instance()->db();
		
		$max_execution_time = ini_get('max_execution_time');
		
		// Update failover status of tasks that appear to have ended abruptly
		$db->query( "UPDATE " . $db->base_prefix . static::$table . " SET task_running=0, task_fails=task_fails + 1 WHERE task_running=1 AND task_last_iteration < " . ( time() - $max_execution_time ) );
		
		$retention_period = Framework::instance()->getSetting( 'mwp_task_retainment_period' );
		
		if ( $retention_period !== 'paranoid' ) { // Easter!
			// Remove completed tasks older than the retention period
			$db->query( "DELETE FROM " . $db->base_prefix . static::$table . " WHERE task_completed > 0 AND task_completed < " . ( time() - ( 60 * 60 * ( abs( intval( $retention_period ) ) ) ) ) );
		}
	}
}
