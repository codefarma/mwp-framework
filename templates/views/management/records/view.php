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

<div class="mwp-bootstrap" style="max-width: 1190px; margin: 50px auto;">
	<table class="table table-striped table-bordered" style="background-color: #fff; box-shadow: 2px 2px 8px #ddd; border-radius: 4px;">
		<thead>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach( $record->dataArray() as $property => $value ) : ?>
			<tr>
				<td><?php echo $property ?></td>
				<td><pre><?php echo esc_html( $value ) ?></pre></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>