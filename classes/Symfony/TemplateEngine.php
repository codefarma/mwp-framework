<?php
/**
 * Plugin Class File
 *
 * Created:   April 1, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */
namespace MWP\Framework\Symfony;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\Helper\HelperInterface;

/**
 * TemplateEngine Class
 */
class TemplateEngine implements EngineInterface, \ArrayAccess
{
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;

	/**
	 * @var		array			Helpers
	 */
	protected $helpers = array();

	/** 
	 * @var		array			Globals
	 */
	protected $globals = array();
	protected $charset = 'UTF-8';


	/**
	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin $plugin )
	{
		$this->plugin = $plugin;
		return $this;
	}

	/**
	 * Constructor
	 *
	 * @param	\MWP\Framework\Plugin	$plugin			The plugin to associate this class with, or NULL to auto-associate
	 * @return	void
	 */
	public function __construct( \MWP\Framework\Plugin $plugin )
	{
		$this->setPlugin( $plugin );
	}

	/**
	 * Render a template
	 *
	 * @param		string			$name					The template name to render
	 * @param		array			$parameters				Template variables
	 * @return		string
	 */
	public function render( $name, array $parameters = array() )
	{
		$parameters = array_replace( $this->getGlobals(), $parameters );
		$parameters[ 'view' ] = $this;
		
		return $this->getPlugin()->getTemplateContent( $this->translateName( $name ), $parameters );
	}

	/**
	 * Check if a template exists
	 *
	 * @param		string			$name			The template name to check
	 * @return		bool
	 */
	public function exists( $name )
	{
		$filename = $this->getPlugin()->getTemplate( $this->translateName( $name ) );

		return file_exists( $filename );
	}

	/**
	 * Check if a given template can be handled by this engine
	 *
	 * @param		string			$name			The template name to check
	 * @return		bool
	 */
	public function supports( $name )
	{
		return substr( $name, -4 ) == '.php';
	}

	/**
	 * Translate a template file name to mwp application framework expected format
	 *
	 * @param		string			$name			The template name
	 * @return		string
	 */
	public function translateName( $name )
	{
		list( $theme, $template ) = explode( ':', $name );

		// Remove .php extension from template name if it has one
		if ( substr( $template, -4 ) == '.php' )
		{
			$template = substr( $template, 0, strlen( $template ) - 4 );
		}
		
		return $theme . '/' . $template;
	}

	/**
	 * Gets a helper value.
	 *
	 * @param string $name The helper name
	 *
	 * @return HelperInterface The helper value
	 *
	 * @throws \InvalidArgumentException if the helper is not defined
	 */
	public function offsetGet( $name )
	{
		return $this->get( $name );
	}

	/**
	 * Returns true if the helper is defined.
	 *
	 * @param string $name The helper name
	 *
	 * @return bool true if the helper is defined, false otherwise
	 */
	public function offsetExists( $name )
	{
		return isset( $this->helpers[ $name ] );
	}

	/**
	 * Sets a helper.
	 *
	 * @param HelperInterface $name  The helper instance
	 * @param string          $value An alias
	 */
	public function offsetSet( $name, $value )
	{
		$this->set( $name, $value );
	}

	/**
	 * Removes a helper.
	 *
	 * @param string $name The helper name
	 *
	 * @throws \LogicException
	 */
	public function offsetUnset( $name )
	{
		throw new \LogicException( sprintf( 'You can\'t unset a helper (%s).', $name ) );
	}

	/**
	 * Adds some helpers.
	 *
	 * @param HelperInterface[] $helpers An array of helper
	 */
	public function addHelpers( array $helpers )
	{
		foreach ( $helpers as $alias => $helper ) {
			$this->set( $helper, is_int( $alias ) ? null : $alias );
		}
	}

	/**
	 * Sets the helpers.
	 *
	 * @param HelperInterface[] $helpers An array of helper
	 */
	public function setHelpers( array $helpers )
	{
		$this->helpers = array();
		$this->addHelpers( $helpers );
	}

	/**
	 * Sets a helper.
	 *
	 * @param HelperInterface $helper The helper instance
	 * @param string          $alias  An alias
	 */
	public function set( HelperInterface $helper, $alias = null )
	{
		$this->helpers[ $helper->getName() ] = $helper;
		if ( null !== $alias ) {
			$this->helpers[$alias] = $helper;
		}

		$helper->setCharset( $this->charset );
	}

	/**
	 * Returns true if the helper if defined.
	 *
	 * @param string $name The helper name
	 * @return bool true if the helper is defined, false otherwise
	 */
	public function has( $name )
	{
		return isset( $this->helpers[ $name ] );
	}

	/**
	 * Gets a helper value.
	 *
	 * @param string $name The helper name
	 * @return HelperInterface The helper instance
	 * @throws \InvalidArgumentException if the helper is not defined
	 */
	public function get( $name )
	{
		if ( ! isset( $this->helpers[ $name ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'The helper "%s" is not defined.', $name ) );
		}

		return $this->helpers[ $name ];
	}

	/**
	 * Escapes a string by using the current charset.
	 *
	 * @param mixed  $value   A variable to escape
	 * @param string $context The context name
	 *
	 * @return string The escaped value
	 */
	public function escape( $value, $context = 'html' )
	{
		if ( is_numeric( $value ) ) {
			return $value;
		}

		$wp_esc_func = 'esc_' . $context;
		return call_user_func( $wp_esc_func, $value );
	}

	/**
	 * Sets the charset to use.
	 *
	 * @param string $charset The charset
	 */
	public function setCharset( $charset )
	{
		if ( 'UTF8' === $charset = strtoupper( $charset ) ) {
			$charset = 'UTF-8'; // iconv on Windows requires "UTF-8" instead of "UTF8"
		}
		$this->charset = $charset;

		foreach ( $this->helpers as $helper ) {
			$helper->setCharset( $this->charset );
		}
	}

	/**
	 * Gets the current charset.
	 *
	 * @return string The current charset
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 */
	public function addGlobal( $name, $value )
	{
		$this->globals[ $name ] = $value;
	}

	/**
	 * Returns the assigned globals.
	 *
	 * @return array
	 */
	public function getGlobals()
	{
		return $this->globals;
	}
	
}
