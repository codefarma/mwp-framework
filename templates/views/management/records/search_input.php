<?php
/**
 * Plugin HTML Template
 *
 * Created:  May 22, 2018
 *
 * @package  MWP Application Framework
 * @author   Kevin Carwile
 * @since    2.0.5
 *
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	ActiveRecordTable		$table			The table this input is being displayed on
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

$text = __( 'Search' );
$input_id = "record-search-input";

?>
<p class="search-box">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text ?>:</label>
	<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php if ( $table->searchPhrase ) { echo esc_html( $table->searchPhrase ); } ?>" />
	<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
<div style="clear:both"></div>
