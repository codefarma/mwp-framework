<?php
/**
 * Plugin HTML Template
 *
 * Created:  December 20, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.4.0
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'component/error' ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	string		$code		The error code
 * @param	string		$message	The error message
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<!-- html content -->
<div class="wrap mwp-bootstrap">
  <div class="alert alert-danger">Error: <?php echo $message ?> (Code: <?php echo $code ?>)</div>
</div>
