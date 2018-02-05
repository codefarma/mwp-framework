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
 * @param	ActiveRecordController		$controller				The active record controller
 * @param	ActiveRecord				$record					The active record 
 * @param	ActiveRecordTable			$table					The display table
 * @param	array						$actions				The record actions
 * @param	string						$default_row_actions	The core default row actions
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="mwp-bootstrap mwp-table-row-actions">
	<?php foreach ( $actions as $action ) : ?>
	<a <?php 
		if ( isset( $action['attr'] ) ) {
			foreach( $action['attr'] as $k => $v ) {
				if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
			}
		}
	?> href="<?php echo $controller->getUrl( isset( $action['params'] ) ? $action['params'] : array() ) ?>">
		<?php if ( isset( $action['icon'] ) ) : ?>
			<i class="<?php echo $action['icon'] ?>"></i>
		<?php endif ?>
		<?php echo isset( $action['title'] ) ? $action['title'] : ''; ?>
	</a>
	<?php endforeach ?>
</div>

<?php echo $default_row_actions ?>
