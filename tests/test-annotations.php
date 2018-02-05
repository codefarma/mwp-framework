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
class MWPAnnotationsTest extends WP_UnitTestCase 
{
	/**
	 * @var	\MWP\Framework\Framework
	 */
	protected $framework;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		\MWP\Framework\Framework::instance()->attach( new AnnotationsTest );
	}
	
	/**
	 * Test @Wordpress\Action
	 */
	public function test_action() 
	{
		do_action( 'test_add_action' );
		$this->assertTrue( AnnotationsTest::$actionPassed );
	}
	
	/**
	 * Test @Wordpress\Filter
	 */
	public function test_filter()
	{
		$this->assertEquals( 'test passed', apply_filters( 'test_apply_filters', FALSE ) );
	}
	
	/**
	 * Test @Wordpress\Shortcode
	 */
	public function test_shortcode()
	{
		do_shortcode( "[test_shortcode_tag type='test' value='passed']It Works![/test_shortcode_tag]");
		
		$this->assertEquals( 'test', AnnotationsTest::$shortcodeData[ 'atts' ][ 'type' ] );
		$this->assertEquals( 'passed', AnnotationsTest::$shortcodeData[ 'atts' ][ 'value' ] );
		$this->assertEquals( 'It Works!', AnnotationsTest::$shortcodeData[ 'content' ] );
	}
	
	/**
	 * Test @Wordpress\AjaxHandler
	 */
	public function test_ajax_handler()
	{
		do_action( 'wp_ajax_test_ajax_handler' );
		$this->assertEquals( 'passed', AnnotationsTest::$ajaxTest );
		
		AnnotationsTest::$ajaxTest = NULL;
		$this->assertEquals( NULL, AnnotationsTest::$ajaxTest );
		
		do_action( 'wp_ajax_nopriv_test_ajax_handler' );
		$this->assertEquals( 'passed', AnnotationsTest::$ajaxTest );
	}
	
	/**
	 * Test @Wordpress\AjaxHandler( for={"guests"} )
	 */
	public function test_ajax_handler_anon()
	{
		do_action( 'wp_ajax_nopriv_test_ajax_handler_anon' );
		$this->assertEquals( 'passed', AnnotationsTest::$ajaxTestAnon );
		
		AnnotationsTest::$ajaxTestAnon = NULL;
		$this->assertEquals( NULL, AnnotationsTest::$ajaxTestAnon );
		
		do_action( 'wp_ajax_test_ajax_handler_anon' );
		$this->assertEquals( NULL, AnnotationsTest::$ajaxTestAnon );
	}	
	
	/**
	 * Test @Wordpress\AjaxHandler( for={"users"} )
	 */
	public function test_ajax_handler_user()
	{
		do_action( 'wp_ajax_test_ajax_handler_users' );
		$this->assertEquals( 'passed', AnnotationsTest::$ajaxTestUser );
		
		AnnotationsTest::$ajaxTestUser = NULL;
		$this->assertEquals( NULL, AnnotationsTest::$ajaxTestUser );
		
		do_action( 'wp_ajax_nopriv_test_ajax_handler_users' );
		$this->assertEquals( NULL, AnnotationsTest::$ajaxTestUser );
	}	
}

class AnnotationsTest
{
	static $actionPassed = FALSE;
	static $shortcodeData = NULL;
	static $ajaxTest = NULL;
	static $ajaxTestAnon = NULL;
	static $ajaxTestUser = NULL;
	
	/**
	 * @Wordpress\Action( for="test_add_action" )
	 */
	public function testAddAction()
	{
		static::$actionPassed = TRUE;
	}
	
	/**
	 * @Wordpress\Filter( for="test_apply_filters" )
	 */
	public function testApplyFilters( $value )
	{
		return 'test passed';
	}
	
	/**
	 * @Wordpress\Shortcode( name="test_shortcode_tag" )
	 */
	public function testShortcodeTag( $atts, $content )
	{
		static::$shortcodeData = array( 'atts' => $atts, 'content' => $content );
	}
	
	/**
	 * @Wordpress\AjaxHandler( action="test_ajax_handler" )
	 */
	public function testAjaxHandler()
	{
		static::$ajaxTest = 'passed';
	}
	
	/**
	 * @Wordpress\AjaxHandler( action="test_ajax_handler_anon", for={"guests"} )
	 */
	public function testAjaxHandlerAnon()
	{
		static::$ajaxTestAnon = 'passed';
	}
	
	/**
	 * @Wordpress\AjaxHandler( action="test_ajax_handler_users", for={"users"} )
	 */
	public function testAjaxHandlerUser()
	{
		static::$ajaxTestUser = 'passed';
	}	
}
