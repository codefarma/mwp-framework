<?php
/**
 * Plugin HTML Template
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<div class="wrap">
	<h1><?php echo $title ?></h1>
	<form action="<?php echo esc_attr($action); ?>" method="post">
		<?php settings_fields( $page_id ); ?>
		<?php do_settings_sections( $page_id ); ?>
		<?php submit_button(); ?>
	</form>
</div>