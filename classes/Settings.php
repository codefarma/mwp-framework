<?php
/**
 * Settings Class File
 *
 * @vendor: {vendor_name}
 * @package: {plugin_name}
 * @author: {plugin_author}
 * @link: {plugin_author_url}
 * @since: {date_time}
 */
namespace MWP\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Plugin Settings
 *
 * @MWP\WordPress\Options( menu="MWP Framework" )
 * 
 * @MWP\WordPress\Options\Section( title="Bootstrap Javascript" )
 * ----------------------------------------------------------
 * @MWP\WordPress\Options\Field( 
 *   name="mwp_bootstrap_disable_front_js", 
 *   type="checkbox",
 *   title="Front",
 *   description="Disable inclusion of Bootstrap Javascript by MWP on the front end", 
 *   default=false 
 * )
 * @MWP\WordPress\Options\Field( 
 *   name="mwp_bootstrap_disable_admin_js", 
 *   type="checkbox",
 *   title="Admin",
 *   description="Disable inclusion of Bootstrap Javascript by MWP on the admin side", 
 *   default=false 
 * )
 *
 * @MWP\WordPress\Options\Section( title="Bootstrap CSS" )
 * ---------------------------------------------------
 * @MWP\WordPress\Options\Field( 
 *   name="mwp_bootstrap_disable_front_css", 
 *   type="checkbox",
 *   title="Front",
 *   description="Disable inclusion of Bootstrap CSS by MWP on the front end", 
 *   default=false 
 * )
 * @MWP\WordPress\Options\Field( 
 *   name="mwp_bootstrap_disable_admin_css", 
 *   type="checkbox",
 *   title="Admin",
 *   description="Disable inclusion of Bootstrap CSS by MWP on the admin side", 
 *   default=false 
 * )
 *
 * @MWP\WordPress\Options\Section( title="Task Runner" )
 * -------------------------------------------------
 * @MWP\WordPress\Options\Field( name="mwp_task_max_runners", type="text", title="Max Concurrent Running Tasks", description="Configure the maximum amount of tasks that can be running at the same time.", default=4 )
 * @MWP\WordPress\Options\Field( name="mwp_task_retainment_period", type="text", title="Completed Task Retainment Period", description="Number of hours to retain completed tasks in the log for review.", default=24 )
 *
 * @MWP\WordPress\Options\Section( title="Developer Features", description="developerDescription" )
 * ---------------------------------------------------
 * @MWP\WordPress\Options\Field( name="mwp_developer_mode", type="checkbox", title="Enable Developer Mode", description="Developer mode will disable caching mechanisms and automatic plugin schema updates.", default=false )
 */
class _Settings extends \MWP\Framework\Plugin\Settings
{
	/**
	 * Instance Cache - Required for singleton
	 * @var	self
	 */
	protected static $_instance;
	
	/**
	 * Output the javascript section description
	 * 
	 * @return	string
	 */
	public function developerDescription()
	{
		if ( defined( 'MWP_FRAMEWORK_DEBUG' ) ) {
			return "Developer mode currently has an override set to <span style='color:red'>" . ( \MWP_FRAMEWORK_DEBUG ? 'ON' : 'OFF' ) . "</span> due to global MWP_FRAMEWORK_DEBUG constant being set in dev_config.php";
		}
	}
}
