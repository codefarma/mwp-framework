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

?>

<input id='<?php echo $field->name ?>' name='<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>]' size='40' type='text' value='<?php echo $settings->getSetting( $field->name ) . $field->getFieldAttributes() ?>' />
