<?php
/**
 * Plugin Class File
 *
 * Created:   December 18, 2016
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.0.1
 */
namespace MWP\Framework\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/** WP_Upgrader_Skin class */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

/**
 * WPUpgraderSkin Class
 */
class WPUpgraderSkin extends \WP_Upgrader_Skin
{
	/**
	 * @access public
	 */
	public function header() {}

	/**
	 * @access public
	 */
	public function footer() {}
	
	/**
	 *
	 * @param string|WP_Error $errors
	 */
	public function error($errors) {}

	/**
	 *
	 * @param string $string
	 */
	public function feedback($string) {}

	/**
	 * @access public
	 */
	public function before() {}

	/**
	 * @access public
	 */
	public function after() {}

	/**
	 * Output JavaScript that calls function to decrement the update counts.
	 *
	 * @since 3.9.0
	 *
	 * @param string $type Type of update count to decrement. Likely values include 'plugin',
	 *                     'theme', 'translation', etc.
	 */
	protected function decrement_update_count( $type ) {}

}
