<?php
/**
 * Form template file
 *
 * Created:   April 3, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>
<?php if ($compound): ?>
<?php echo $view['form']->block($form, 'form_widget_compound')?>
<?php else: ?>
<?php echo $view['form']->block($form, 'form_widget_simple')?>
<?php endif ?>
