<?php
/**
 * Plugin HTML Template
 *
 * Created:  December 13, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.4.0
 *
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	MWP\Framework\Helpers\Form						$form			The form that was built
 * @param	MWP\Framework\Plugin								$plugin			The plugin that created the controller
 * @param	MWP\Framework\Helpers\ActiveRecordController		$controller		The active record controller
 * @param   array|NULL                                      $error          Any errors encountered while saving the record
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="wrap">
	<h1><?php echo $title ?></h1>
	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error">
			<ul>
			<?php foreach( $error->errors as $type => $errors ) : ?>
				<?php foreach( $errors as $message ) : ?>
					<li><?php echo $message ?></li>
				<?php endforeach; ?>
			<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>	<?php echo $form->render() ?>
</div>
