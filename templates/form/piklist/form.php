<?php
/**
 * Plugin HTML Template
 *
 * Created:  January 25, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.1.4
 *
 * @param	Plugin		$this			The plugin instance which is loading this template
 *
 * @param	Form		$form			The form being rendered
 * @param	array		$form_rows		An array of rendered form rows
 * @param	string		$hidden_fields	The closing form hidden fields
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}
?>

<div class="mwp-bootstrap">
	<form method="<?php echo $form->method ?>" action="<?php echo $form->action ?>">
		
		<?php foreach( $form_rows as $row ) : ?>
			<?php echo $row ?>
		<?php endforeach; ?>

		<?php echo $hidden_fields ?>
		
	</form>
</div>
