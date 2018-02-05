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
<?php if ($widget == 'single_text'): ?>
    <?php echo $view['form']->block($form, 'form_widget_simple'); ?>
<?php else: ?>
    <?php $vars = $widget == 'text' ? array('attr' => array('size' => 1)) : array() ?>
    <div <?php echo $view['form']->block($form, 'widget_container_attributes') ?>>
        <?php
            // There should be no spaces between the colons and the widgets, that's why
            // this block is written in a single PHP tag
            echo $view['form']->widget($form['hour'], $vars);

            if ($with_minutes) {
                echo ':';
                echo $view['form']->widget($form['minute'], $vars);
            }

            if ($with_seconds) {
                echo ':';
                echo $view['form']->widget($form['second'], $vars);
            }
        ?>
    </div>
<?php endif ?>
