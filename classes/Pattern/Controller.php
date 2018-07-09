<?php
/**
 * Plugin Class File
 *
 * Created:   April 12, 2018
 *
 * @package:  MWP Application Framework for WordPress
 * @author:   Kevin Carwile
 * @since:    2.0.0
 */
namespace MWP\Framework\Pattern;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\WordPress\AdminPage;
use MWP\WordPress\PostPage;

/**
 * Controller Class
 */
abstract class _Controller
{
	/**
	 * @var	Instance Cache
	 */
	protected static $_instances = array();
	
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
	 * Constructed
	 *
	 * @return    void
	 */
	protected function constructed()
	{
		// constructor function for when the instance is first created
	}
	
	/**
	 * Initialize
	 *
	 * @return    void
	 */
	public function init()
	{
		// initialization routine when the controller is used
	}
	
	/**
	 * @var	array
	 */
	public $config;
	
	/**
	 * Create Instance
	 *
	 * @param	string				$name				The name of the controller
	 * @param	array				$config				The controller configuration
	 * @throws  \ErrorException
	 * @return	Controller
	 */
	public static function create( $name, $config=[] )
	{
		if ( isset( static::$_instances[ get_called_class() ][ $name ] ) ) {
			throw new \ErrorException( 'Controller instance by that name already exists. Class: ' . get_called_class() . ', Name: ' . $name );
		}
		
		$instance = new static;
		$instance->config = $config;
		$instance->constructed();
		
		if ( isset( $instance->config['adminPage'] ) ) {
			$instance->registerAdminPage( $instance->config['adminPage'] );
		}
		
		if ( isset( $instance->config['postPage'] ) ) {
			$instance->registerPostPage( $instance->config['postPage'] );
		}
		
		return static::$_instances[ get_called_class() ][ $name ] = $instance;
	}
	
	/**
	 * Get a controller instance
	 *
	 * @param	string		$name			The name used to reference the controller
	 * @return Controller|NULL
	 */
	public static function get( $name )
	{
		if ( isset( static::$_instances[ get_called_class() ][ $name ] ) ) {
			return static::$_instances[ get_called_class() ][ $name ];
		}
		
		return NULL;
	}

	/**
	 * @var	MWP\WordPress\AdminPage
	 */
	public $adminPage;
	
	/**
	 * @var	MWP\WordPress\PostPage
	 */
	public $postPage;
	
	/**
	 * Register the controller as an admin page
	 *
	 * @param	array			$options			Admin page options
	 * @return	AdminPage
	 */
	public function registerAdminPage( $options=array() )
	{
		$adminPage = new AdminPage;
		
		$adminPage->title = isset( $options['title'] ) ? $options['title'] : array_pop( explode( '\\', get_called_class() ) );
		$adminPage->menu  = isset( $options['menu'] ) ? $options['menu'] : $adminPage->title;
		$adminPage->slug  = isset( $options['slug'] ) ? $options['slug'] : sanitize_title( str_replace( '\\', '-', get_called_class() ) );
		$adminPage->capability = isset( $options['capability'] ) ? $options['capability'] : $adminPage->capability;
		$adminPage->icon = isset( $options['icon'] ) ? $options['icon'] : $adminPage->icon;
		$adminPage->position = isset( $options['position'] ) ? $options['position'] : NULL;
		$adminPage->type = isset( $options['type'] ) ? $options['type'] : $adminPage->type;
		$adminPage->parent = isset( $options['parent'] ) ? $options['parent'] : $adminPage->parent;
		$adminPage->menu_submenu = isset( $options['menu_submenu'] ) ? $options['menu_submenu'] : null;
		$adminPage->for = isset( $options['for'] ) ? $options['for'] : $adminPage->for;
		
		$adminPage->applyToObject( $this, array() );
		
		$this->adminPage = $adminPage;
		return $this->adminPage;
	}
	
	/**
	 * Register the controller to a specific post page
	 *
	 * @param	array			$options			Post page options
	 * @return	PostPage
	 */
	public function registerPostPage( $options=array() )
	{
		if ( isset( $options['post_id'] ) ) {
			$postPage = new PostPage;
		
			$postPage->post_id = $options['post_id'];
			$postPage->applyToObject( $this, array() );
			
			$this->postPage = $postPage;
			return $this->postPage;
		}		
	}
	
	/**
	 * Get the controller url
	 *
	 * @param	array			$args			Optional query args
	 * @return	string
	 */
	public function getUrl( $args=array() )
	{
		/* Check if we are registered to both front side and admin side pages. Get the url to the contextual page. */
		if ( isset( $this->adminPage ) and isset( $this->postPage ) ) {
			return is_admin() ? $this->getAdminUrl( $args ) : $this->getFrontUrl( $args );
		}
		
		/* Return one or the other */
		return $this->getAdminUrl( $args ) ?: $this->getFrontUrl( $args );
	}
	
	/**
	 * Get the controller admin side page url
	 *
	 * @param	array			$args			Optional query args
	 * @param	bool|NULL		$network		Flag indicating if url should point to network admin or not. NULL for auto-detect.
	 * @return	string
	 */
	public function getAdminUrl( $args=array() )
	{
		if ( isset( $this->adminPage ) ) {
			$network = $this->adminPage->for == 'all' ? is_network_admin() : $this->adminPage->for == 'network';
			return add_query_arg( $args, $network ? network_menu_page_url( $this->adminPage->slug, false ) : menu_page_url( $this->adminPage->slug, false ) );
		}
		
		return '';
	}
	
	/**
	 * Get the controller front side page url
	 *
	 * @param	array			$args			Optional query args
	 * @return	string
	 */
	public function getFrontUrl( $args=array() )
	{
		if ( isset( $this->postPage ) ) {
			return add_query_arg( $args, get_permalink( $this->postPage->post_id ) );
		}
		
		return '';
	}

}
