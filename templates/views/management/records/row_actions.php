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
	<?php if ( count( $actions ) > 1 ) : ?>
	<div class="btn-group">
	  <?php $action = array_shift( $actions ); ?>
	  <?php if ( isset( $action['html'] ) ) : ?>
		<?php echo $action['html'] ?>
	  <?php else: ?>
	  <a class="btn btn-sm btn-default" href="<?php echo ( isset( $action['url'] ) and $action['url'] ) ? $action['url'] : $controller->getUrl( isset( $action['params'] ) ? $action['params'] : array() ) ?>">
		<span <?php 
			if ( isset( $action['attr'] ) ) {
				foreach( $action['attr'] as $k => $v ) {
					if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
				}
			}
		?>>
			<?php if ( isset( $action['icon'] ) ) : ?>
				<i class="<?php echo $action['icon'] ?>" style="margin-right:4px"></i>
			<?php endif ?>
			<?php echo isset( $action['title'] ) ? $action['title'] : ''; ?>
		</span>
	  </a>
	  <?php endif ?>
	  <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		<span class="caret"></span>
		<span class="sr-only">Toggle Dropdown</span>
	  </button>
	  <ul class="dropdown-menu dropdown-menu-right">
		<?php foreach ( $actions as $action ) : ?>
		<?php if ( isset( $action['separator'] ) and $action['separator'] ) :?><li role="separator" class="divider"></li><?php endif ?>
		<li>
			<?php if ( isset( $action['html'] ) ) : ?>
				<?php echo $action['html'] ?>
			<?php else: ?>
			<a href="<?php echo ( isset( $action['url'] ) and $action['url'] ) ? $action['url'] : $controller->getUrl( isset( $action['params'] ) ? $action['params'] : array() ) ?>">
				<span <?php 
					if ( isset( $action['attr'] ) ) {
						foreach( $action['attr'] as $k => $v ) {
							if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
						}
					}
				?>>
					<?php if ( isset( $action['icon'] ) ) : ?>
						<i class="<?php echo $action['icon'] ?>" style="margin-right:4px"></i>
					<?php endif ?>
					<?php echo isset( $action['title'] ) ? $action['title'] : ''; ?>
				</span>
			</a>
			<?php endif ?>
		</li>
		<?php endforeach ?>
	  </ul>
	</div>
	<?php else: ?>
		<?php foreach ( $actions as $action ) : ?>
		<?php if ( isset( $action['html'] ) ) : ?>
			<?php echo $action['html'] ?>
		<?php else: ?>
		<a class="btn btn-sm btn-default" href="<?php echo ( isset( $action['url'] ) and $action['url'] ) ? $action['url'] : $controller->getUrl( isset( $action['params'] ) ? $action['params'] : array() ) ?>">
			<span <?php 
				if ( isset( $action['attr'] ) ) {
					foreach( $action['attr'] as $k => $v ) {
						if ( is_array( $v ) ) { $v = json_encode( $v ); } printf( '%s="%s" ', $k, esc_attr( $v ) );
					}
				}
			?>>
				<?php if ( isset( $action['icon'] ) ) : ?>
					<i class="<?php echo $action['icon'] ?>" style="margin-right:4px"></i>
				<?php endif ?>
				<?php echo isset( $action['title'] ) ? $action['title'] : ''; ?>
			</span>
		</a>
		<?php endif ?>
		<?php endforeach ?>
	<?php endif ?>
</div>

<?php echo $default_row_actions ?>
