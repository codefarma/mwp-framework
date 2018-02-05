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
<?php echo $row_prefix ?>
<div <?php 
	foreach ($row_attr as $k => $v) { 
		if ($v === true) {
			printf('%s="%s" ', $view->escape($k), $view->escape($k));
		} elseif ($v !== false){
			printf('%s="%s" ', $view->escape($k), $view->escape($v));
		} 
	} ?>>
	<?php echo $prefix; ?>
    <?php echo $view[ 'form' ]->widget( $form ) ?>
	<?php echo $suffix ?>
</div>
<?php echo $row_suffix ?>
