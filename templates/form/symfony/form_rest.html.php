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
<?php foreach ($form as $child): ?>
    <?php if (!$child->isRendered()): ?>
        <?php echo $view['form']->row($child) ?>
    <?php endif; ?>
<?php endforeach; ?>
