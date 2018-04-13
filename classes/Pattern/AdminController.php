<?php
/**
 * Plugin Class File
 *
 * Created:   April 12, 2018
 *
 * @package:  MWP Application Framework for WordPress
 * @author:   Kevin Carwile
 * @since:    {build_version}
 */
namespace MWP\Framework\Pattern;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\WordPress\AdminPage;
use MWP\Framework\Pattern\Singleton;

/**
 * BaseController Class
 */
abstract class _AdminController
{
	/**
	 * @var	Instance Cache
	 */
	protected static $_instance = array();
	
	/**
     * Protected constructor to prevent creating a new instance
     * from outside of this class.
     */
    protected function __construct() 
	{
	}
	
	/**
     * Private clone method to prevent cloning
     *
     * @return void
     */
    private function __clone()
    {
    }
	
	/**
	 * Initialize
	 *
	 * @return    void
	 */
	protected function constructed()
	{
		// initialization routine when the instance is first created
	}
	
	/**
	 * @var	array
	 */
	protected $config;
	
	/**
	 * Create Instance
	 *
	 * @param	string				$name				The name of the controller
	 * @throws  \ErrorException
	 * @return	AdminController
	 */
	public static function create( $name, $config=[] )
	{
		if ( isset( static::$_instance[ get_called_class() ][ $name ] ) ) {
			throw new \ErrorException( 'Controller instance by that name already exists.' );
		}
		
		$instance = new static;
		$instance->config = $config;
		$instance->constructed();
		
		if ( isset( $instance->config['adminPage'] ) ) {
			$instance->registerAdminPage( $instance->config['adminPage'] );
		}
		
		return static::$_instance[ get_called_class() ][ $name ] = $instance;
	}
	
	/**
	 * Get a controller instance
	 *
	 * @return AdminController|NULL
	 */
	public static function get( $name )
	{
		if ( isset( static::$_instance[ get_called_class() ][ $name ] ) ) {
			return static::$_instance[ get_called_class() ][ $name ];
		}
		
		return NULL;
	}

	/**
	 * @var	MWP\WordPress\AdminPage
	 */
	public $adminPage;
	
	/**
	 * Register the controller as an admin page
	 *
	 * @param	array			$options			Admin page options
	 */
	public function registerAdminPage( $options=array() )
	{
		$adminPage = new AdminPage;
		
		$adminPage->title = isset( $options['title'] ) ? $options['title'] : end( explode( '\\', get_called_class() ) );
		$adminPage->menu  = isset( $options['menu'] ) ? $options['menu'] : $adminPage->title;
		$adminPage->slug  = isset( $options['slug'] ) ? $options['slug'] : sanitize_title( str_replace( '\\', '-', get_called_class() ) );
		$adminPage->capability = isset( $options['capability'] ) ? $options['capability'] : $adminPage->capability;
		$adminPage->icon = isset( $options['icon'] ) ? $options['icon'] : $adminPage->icon;
		$adminPage->position = isset( $options['position'] ) ? $options['position'] : NULL;
		$adminPage->type = isset( $options['type'] ) ? $options['type'] : $adminPage->type;
		$adminPage->parent = isset( $options['parent'] ) ? $options['parent'] : $adminPage->parent;
		$adminPage->menu_submenu = isset( $options['menu_submenu'] ) ? $options['menu_submenu'] : null;
		
		$adminPage->applyToObject( $this );
		
		$this->adminPage = $adminPage;
		return $this->adminPage;
	}
	
	/**
	 * Get the controller url
	 *
	 * @param	array			$args			Optional query args
	 * @return	string
	 */
	public function getUrl( $args=array() )
	{
		if ( isset( $this->adminPage ) ) {
			return add_query_arg( $args, menu_page_url( $this->adminPage->slug, false ) );
		}
		
		return '';
	}

}
