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
<?php $method = strtoupper($method) ?>
<?php $form_method = $method === 'GET' || $method === 'POST' ? $method : 'POST' ?>
<?php if ( count( $all_errors ) > 0 ) : ?>
	<div class="alert alert-danger"><?php echo __( 'Validation Error: Please correct the errors on this form and resubmit.', 'mwp-framework' ); ?></div>
<?php endif; ?>
<form name="<?php echo $name ?>" method="<?php echo strtolower($form_method) ?>"<?php if ($action !== ''): ?> action="<?php echo $action ?>"<?php endif ?><?php foreach ($form_attr as $k => $v) { printf(' %s="%s"', $view->escape($k), $view->escape($v)); } ?><?php if ($multipart): ?> enctype="multipart/form-data"<?php endif ?>>
<?php if ($form_method !== $method): ?>
	<input type="hidden" name="_method" value="<?php echo $method ?>" />
<?php endif ?>
