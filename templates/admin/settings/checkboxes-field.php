<?php
/**
 * Plugin HTML Template
 *
 * @var 	$settings		\MWP\Framework\Plugin\Settings			The settings store
 * @var		$field			\Wordpress\Options\Field					The options field definition
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$selectOptions = $field->options ?: array();
$currentValue = $settings->getSetting( $field->name ) ?: array();

/**
 * If select options is a string, see if it is a callable method on the settings store
 * that can be used to generate the select options
 */
if ( is_string( $field->options ) )
{
	if ( is_callable( array( $settings, $selectOptions ) ) )
	{
		$selectOptions = call_user_func( array( $settings, $selectOptions ), $currentValue );
	}
}

?>

<?php foreach( $selectOptions as $value => $title ) : ?>
<div>
	<label>
		<input type="checkbox" name='<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>][]'<?php echo $field->getFieldAttributes() ?> value="<?php echo $value ?>" <?php if( in_array( $value, $currentValue ) ){ echo "checked"; } ?> /> 
		<?php echo $title ?>
	</label>
</div>
<?php endforeach; ?>
