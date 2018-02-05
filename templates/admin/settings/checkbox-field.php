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

$currentValue = $settings->getSetting( $field->name );
?>

<label for="checkbox-<?php echo $field->name ?>">
	<input type="hidden" name="<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>]" value="0" />
	<input type="checkbox" id="checkbox-<?php echo $field->name ?>" name="<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>]" value="1" <?php if ( $currentValue ) { echo 'checked="checked"'; } echo $field->getFieldAttributes() ?> />
	<?php echo $field->description; ?>
</label>

