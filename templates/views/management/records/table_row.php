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
 * @param	array												$item			The item row
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$recordClass = $table->activeRecordClass;

?>
<tr id="<?php echo $item[ $recordClass::$prefix . $recordClass::$key ] ?>">
	<?php echo $table->single_row_columns( $item ); ?>
</tr>
