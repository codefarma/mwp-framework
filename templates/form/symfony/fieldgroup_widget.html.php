<?php
/**
 * Form template file
 *
 * Created:   December 14, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$element = $type == 'fieldset' ? 'fieldset' : 'div';
$title_element = $type == 'fieldset' ? 'legend' : 'h3';
?>
<<?php echo $element ?> <?php echo $view['form']->block($form, 'widget_container_attributes') ?>>
	<?php if ( $type == 'fieldset' and $title ) { ?>
	<legend><?php echo $title ?></legend>
	<?php } ?>
	<?php echo $view['form']->block($form, 'form_rows') ?>
</<?php echo $element ?>>

