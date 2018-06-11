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
 * @param	array												$actions		Actions to display
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<?php foreach ( $actions as $action ) : ?>
<a <?php 
	if ( isset( $action['link_attr'] ) ) {
		foreach( $action['link_attr'] as $k => $v ) {
			if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
		}
	}
?> class="page-title-action" href="<?php echo $controller->getUrl( isset( $action['params'] ) ? $action['params'] : array() ) ?>">
	<span <?php 
			if ( isset( $action['attr'] ) ) {
				foreach( $action['attr'] as $k => $v ) {
					if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
				}
			}
		?>>
		<?php if ( isset( $action['icon'] ) ) : ?>
			<i class="<?php echo $action['icon'] ?>"></i>
		<?php endif ?>
		<?php echo $action['title'] ?>
	</span>
</a>
<?php endforeach ?>
