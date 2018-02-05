<?php
/**
 * Plugin HTML Template
 *
 * Created:  March 2, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.2.4
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'views/management/tasks', array( 'title' => 'Some Custom Title', 'content' => 'Some custom content' ) ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	Table		$table		The tasks table
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Task;
?>

<div class="wrap">

	<h1>Wordpress Tasks Queue</h1>
	
	<?php echo $this->getTemplateContent( 'views/management/task-submenu' ) ?>
	
	<form method="post">
		<?php $table->search_box('search', 'search_text'); ?>
		<?php echo $table->getDisplay() ?>
	</form>
	
</div>
