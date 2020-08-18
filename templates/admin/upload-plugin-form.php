<?php
/**
 * Plugin HTML Template
 *
 * Created:  July 14, 2017
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    1.3.3
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'admin/upload-plugin-form' ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="upload-plugin">
	<p class="install-help"><?php _e('If you have a plugin in a .zip format, you may install it by uploading it here.'); ?></p>
	<p class="install-help"><?php _e('If you want to upload a new version of an existing plugin, check the checkbox.'); ?></p>
	<form method="post" enctype="multipart/form-data" class="wp-upload-form" style="flex-direction: column" action="<?php echo self_admin_url('update.php?action=upload-plugin'); ?>">
		<div style="display: flex">
			<?php wp_nonce_field( 'plugin-upload' ); ?>
			<label class="screen-reader-text" for="pluginzip"><?php _e( 'Plugin zip file' ); ?></label>
			<input type="file" id="pluginzip" name="pluginzip" />
			<?php submit_button( __( 'Install Now' ), '', 'install-plugin-submit', false ); ?>
		</div>
		<p>
			<label><input type="checkbox" name="clear_destination" value="1" /> Upgrade existing plugin.</label>
		</p>
	</form>
</div>
