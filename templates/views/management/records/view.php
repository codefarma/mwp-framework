<?php
/**
 * Plugin HTML Template
 *
 * Created:  May 4, 2018
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    2.0.0
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

<div class="mwp-bootstrap" style="max-width: 1190px; margin: 50px auto;">
	<table class="table table-striped table-bordered" style="background-color: #fff; box-shadow: 2px 2px 8px #ddd; border-radius: 4px;">
		<thead>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach( $record::_getColumns() as $prop => $config ) : 
				if ( ! is_array( $config ) ) {
					$prop = $config;
					$config = [];
				}
				
				$value = $record->$prop;
			?>
			<tr>
				<td><?php echo ( isset( $config['title'] ) and $config['title'] ) ? __( $config['title'] ) : $record::_getPrefix() . $prop ?></td>
				<td><pre><?php echo esc_html( print_r( $value, true ) ?: '&nbsp;' ) ?></pre></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>