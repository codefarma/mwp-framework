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

/* Button Label */
if ( ! $label ) { 
	$label = isset( $label_format ) ? strtr( $label_format, array( '%name%' => $name, '%id%' => $id ) ) : $view[ 'form' ]->humanize( $name ); 
}

?>
<button type="<?php echo isset( $type ) ? $view->escape( $type ) : 'button' ?>" <?php echo $view[ 'form' ]->block( $form, 'button_attributes' ) ?>>
	<?php echo $view->escape( false !== $translation_domain ? $view[ 'translator' ]->trans( $label, array(), $translation_domain ) : $label ) ?>
</button>
