<?php
/**
 * Plugin HTML Template
 *
 * Created:  March 20, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.2.6
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'views/management/tasks/task-title', array( 'task' => $task ) ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	string		$task		The task item
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<strong style="font-size: 1.2em;"><a href="<?php echo add_query_arg( array( 'page' => 'mwp-fw-tasks', 'do' => 'viewtask', 'task_id' => $task->id ), admin_url( 'tools.php' ) ) ?>"><?php echo $task->getTitle() ?></a></strong>
<?php if ( $task->tag ) : ?><br>Tag: <?php echo $task->tag ?><?php endif; ?>
