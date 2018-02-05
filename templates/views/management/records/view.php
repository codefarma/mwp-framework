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
 * @param	string												$title			The provided title
 * @param	MWP\Framework\Plugin								$plugin			The plugin associated with the active records/view
 * @param	MWP\Framework\Helpers\ActiveRecordController		$controller		The associated controller displaying this view
 * @param	MWP\Framework\Pattern\ActiveRecordController		$record			The active record to display
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>


<div class="wrap">
	<h1><?php echo $title ?></h1>
	<?php echo $content ?>
</div>
