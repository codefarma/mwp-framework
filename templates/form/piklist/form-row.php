<?php
/**
 * Plugin HTML Template
 *
 * Created:  January 25, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.1.4
 *
 * @param	Plugin		$this			The plugin instance which is loading this template
 *
 * @param	array		$field			The field definition
 * @param	string		$field_name		The field name
 * @param	string		$field_html		The rendered field html
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}
?>

<div class="form-group">
	<label for="<?php echo $field_name ?>"><?php echo $field['label'] ?></label>
	<?php echo $field_html ?>
</div>
