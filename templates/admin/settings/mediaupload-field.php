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

wp_enqueue_media();
$currentValue = $settings->getSetting( $field->name ) ?: array();
$multiple = ( isset( $field->options['multiple'] ) and $field->options['multiple'] ) ? "true" : "false";
//$attachment = ( $currentValue ? get_post( $currentValue ) : null );

?>

<div class="mwp-media-setting" data-view-model="mwp-settings">
  <div data-bind="wpMedia: { frame: { title: '<?php echo esc_attr( $field->title ) ?>', button: { text: 'Select Image' }, multiple: <?php echo $multiple ?> }, attachments: <?php echo json_encode( array_map( 'intval', (array) $currentValue ) ) ?> }">
    <div data-bind="foreach: attachments">
	  <input data-bind="value: attributes.url" type="text" style="width:300px;" readonly />
	  <img data-bind="attr: { src: attributes.url }" class="image-preview" style="width:30px; height:auto; position:absolute;"><br>
	  <input data-bind="value: attributes.id" type="hidden" name="<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>][]" />
	</div>
	<a data-bind="click: function() { mediaFrame.open() }" type="button" class="button"><?php _e( 'Select Media' ); ?></a>
  </div>
</div>
