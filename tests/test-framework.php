<?php
/**
 * Testing Class
 *
 * To set up testing for your wordpress plugin:
 *
 * @see: http://wp-cli.org/docs/plugin-unit-tests/
 *
 * @package Simple Forums
 */
if ( ! class_exists( 'WP_UnitTestCase' ) )
{
	die( 'Access denied.' );
}

/**
 * Test the framework
 */
class MWPFrameworkTest extends WP_UnitTestCase 
{
	/**
	 * Test that the framework is actually an instance of a mwp application framework plugin
	 */
	public function test_plugin_class() 
	{
		$framework = \MWP\Framework\Framework::instance();
		
		// Check that the framework is a subclass of MWP\Framework\Plugin 
		$this->assertTrue( $framework instanceof \MWP\Framework\Plugin );
	}
}
