<?php
/**
 * Plugin HTML Template
 *
 * @var    $settings        \MWP\Framework\Plugin\Settings            The settings store
 * @var        $field            \Wordpress\Options\Field                    The options field definition
 */

if (!defined('ABSPATH')) {
    die('Access denied.');
}

?>

<textarea id='<?php echo $field->name ?>' name='<?php echo $settings->getStorageId() ?>[<?php echo $field->name ?>]'
          type='text' rows="5" cols="75"/>
<?php echo $settings->getSetting($field->name) ?>
</textarea>
