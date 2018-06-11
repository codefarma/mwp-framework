<?php
/**
 * Plugin HTML Template
 *
 * Created:  January 4, 2018
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.4.0
 *
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	MWP\Framework\Helpers\ActiveRecordTable			$table			The active record display table
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$singular = $table->_args['singular'];

if ( ! empty( $table->searchableColumns ) ) {
	echo $this->getTemplateContent( 'views/management/records/search_input', [ 'table' => $table ] );
}
?>

<?php 
if ( $table->displayTopNavigation ) {
	$table->display_tablenav( 'top' ); 
}
?>

<?php $table->screen->render_screen_reader_content( 'heading_list' ); ?>

<table class="wp-list-table <?php echo implode( ' ', $table->get_table_classes() ); ?>" <?php echo $table->getViewModelAttr() ?>>
	<?php if ( $table->displayTopHeaders ) : ?>
		<thead>
		<tr>
			<?php $table->print_column_headers(); ?>
		</tr>
		</thead>
	<?php endif ?>
	
	<tbody id="the-list"<?php if ( $singular ) { echo " data-wp-lists='list:$singular'"; } ?> <?php echo $table->getSequencingBindAttr() ?>>
		<?php $table->display_rows_or_placeholder(); ?>
	</tbody>

	<?php if ( $table->displayBottomHeaders ) : ?>
		<tfoot>
		<tr>
			<?php $table->print_column_headers( false ); ?>
		</tr>
		</tfoot>
	<?php endif ?>

</table>

<?php 
if ( $table->displayBottomNavigation ) {
	$table->display_tablenav( 'bottom' ); 
}
?>
