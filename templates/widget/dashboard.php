<?php
/**
 * Plugin HTML Template
 *
 * Created:  June 8, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.3.1
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'widget/dashboard' ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Task;
use MWP\Framework\Framework;

$notices = array();
$framework = Framework::instance();

if ( isset( $_POST['mwp_fw_clear_caches'] ) and $_POST['mwp_fw_clear_caches'] ) {
	update_site_option( 'mwp_fw_cache_latest', time() );
	$framework->clearCaches();
	$notices[] = __( "Temporary caches have been cleared.", 'mwp-framework' );
}

if ( isset( $_POST['mwp_fw_update_schema'] ) and $_POST['mwp_fw_update_schema'] ) {
	foreach( apply_filters( 'mwp_framework_plugins', array() ) as $plugin ) {
		$plugin->updateSchema();
	}
	$notices[] = __( "Database table schemas have been brought up to date.", 'mwp-framework' );
}

$failed_task_count = Task::countTasks( null, null, 'failed' );

?>

<div style="float: right; display: inline-block;">

	<?php foreach ( $notices as $message ) : ?>
		<div class="notice updated"><p><?php echo esc_html( $message ) ?></p></div>
	<?php endforeach; ?>

	<form method="post" style="margin-bottom: 10px">
		<input name="mwp_fw_clear_caches" type="hidden" value="1" />
		<input class="button" value="Clear Caches" type="submit" style="width: 100%;"/>
	</form>

	<?php if ( ! $framework->isDev() ) : ?>
	<form method="post">
		<input name="mwp_fw_update_schema" type="hidden" value="1" />
		<input class="button" value="Update DB Schema" type="submit" style="width: 100%;" />
	</form>
	<?php endif; ?>

</div>

<a href="<?php echo admin_url( 'tools.php?page=mwp-fw-tasks' ) ?>">Tasks Pending</a>: <?php echo Task::countTasks() ?>
<?php if ( $failed_task_count ) : ?>
  <br><a style="color: red" href="<?php echo admin_url( 'tools.php?page=mwp-fw-tasks&status=failed' ) ?>">Tasks Failed</a>: <?php echo Task::countTasks( null, null, 'failed' ) ?>
<?php endif ?>

<div style="clear:both; padding-top: 10px;">
	<?php if ( $framework->isDev() ) : ?>
		<br><strong>Development Mode: </strong><span style="color: green">On</span>
	<?php endif; ?>
	<?php if ( ! $framework->getAnnotationReader() instanceof \Doctrine\Common\Annotations\FileCacheReader ) : ?>
		<br><strong>Annotation Caching: </strong><span style="color: red">Disabled</span>
	<?php endif; ?>
	<hr />
	<i class="dashicons dashicons-category" aria-hidden="true"></i> <?php echo str_replace( str_replace( '\\', '/', get_home_path() ), '', str_replace( '\\', '/', $framework->getPath() ) ) ?>
</div>
