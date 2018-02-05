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
<input type="checkbox"
    <?php echo $view[ 'form' ]->block( $form, 'widget_attributes' ) ?>
    <?php if ( strlen( $value ) > 0 ): ?> value="<?php echo $view->escape($value) ?>"<?php endif ?>
    <?php if ( $checked ): ?> checked="checked"<?php endif ?>
/>
