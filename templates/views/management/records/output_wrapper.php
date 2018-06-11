<?php
/**
 * Plugin HTML Template
 *
 * Created:  December 13, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.4.0
 *
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	MWP\Framework\Plugin								$plugin			The plugin that created the controller
 * @param	MWP\Framework\Helpers\ActiveRecordController		$controller		The active record controller
 * @param	MWP\Framework\Helpers\ActiveRecordTable			$table			The active record display table
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="wrap management records <?php echo $classes ?>">
	<h1 class="wp-heading-inline"><?php echo $title ?></h1>
	<hr style="position: absolute">
	<?php echo $output ?>
</div>
