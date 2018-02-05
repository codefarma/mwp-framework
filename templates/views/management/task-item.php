<?php
/**
 * Plugin HTML Template
 *
 * Created:  April 5, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.2.8
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'views/management/task-item', array() ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	string		$task		The task being viewed
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$displaydate = function( $timestamp, $default='' ) {
	if ( $timestamp > 0 ) {
		return get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), 'F j, Y H:i:s' );
	}
	else {
		return $default;
	}
};

$task_data = $task->data;
unset( $task_data['logs'] );
unset( $task_data['status'] );
?>

<div class="wrap">
	
	<h1>Task Item Details</h1>
	<?php if ( $task !== NULL ) : ?>
		
		<div>
			<?php echo $this->getTemplateContent( 'views/management/task-submenu' ) ?>
		</div>
		
		<hr style="clear:both">
		
		<div style="margin-left: 25px; float: right; width: 50%;">
			<h2><?php _e( 'Task Data', 'mwp-framework' ) ?></h2>
			<pre style=" border: 1px solid #aaa; padding: 15px; background-color: #fff; max-width: 100%; overflow: auto;"><?php echo esc_html( json_encode( $task_data, JSON_PRETTY_PRINT ) ) ?></pre>
		</div>
		
		<h2><?php echo $task->getTitle() ?></h2>

		<blockquote>
			<ul>
				<li><strong>Status:</strong> <?php echo $task->getStatusForDisplay() ?></li>
				<li><strong>Action:</strong> <code><?php echo $task->action ?></code></li>
				<li><strong>Tag:</strong> <?php echo $task->tag ?: 'None' ?></li>
				<li><strong>Last Start:</strong> <?php echo $task->getLastStartForDisplay(); ?></li>
				<li><strong>Next Start:</strong> <?php echo $task->getNextStartForDisplay(); ?></li>
			</ul>
		</blockquote>

		<h2>Logs</h2>
		
		<blockquote>
		<?php
			$logs = $task->getData( 'logs' );
			if ( ! empty( $logs ) )
			{
				echo "<ul style='list-style-type: disc;'>";
				foreach( $logs as $log )
				{
					?>
						<li>
							<?php echo call_user_func( $displaydate, $log[ 'time' ] ) . ": " . $log[ 'message' ] ?>
						</li>
					<?php
				}
				echo "</ul>";
			}
			else
			{
				echo "<ul style='list-style-type: disc;'><li>None</li></ul>";
			}
		?>
		</blockquote>
		
	<?php else: ?>
	
		<h1>Task Not Found</h1>
	
	<?php endif ?>
</div>
