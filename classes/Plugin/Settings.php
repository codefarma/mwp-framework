<?php
/**
 * Settings Class (Singleton)
 * 
 * Created:    Nov 20, 2016
 *
 * @package   MWP Application Framework
 * @author    Kevin Carwile
 * @since     1.0.0
 */

namespace MWP\Framework\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use \MWP\Framework\Pattern\Singleton;

/**
 * Provides base class to easily define new plugin settings.
 */
abstract class Settings extends Singleton
{
	
	/**
	 * Instance Cache - Required for singleton
	 * @var	self
	 */
	protected static $_instance;
	
	/**
	 * @var string	Settings Access Key
	 */
	public $key = 'main';
	
	/**
	 * @var array 	Plugin Settings
	 */
	protected $settings;
	
	/**
	 * @var	Plugin	Plugin Reference
	 */
	protected $plugin;
	
	/**
	 * @var array	Settings Defaults
	 */
	protected $defaults = array();
	
	/**
	 * @var	string	The database option_name used to store these settings
	 */
	protected $storageId;
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	protected function __construct()
	{
		if ( ! isset( $this->storageId ) )
		{
			$this->storageId = strtolower( str_replace( '\\', '_', get_class( $this ) ) );
		}
		
		parent::__construct();
	}
	
	/**
	 * Set a default setting
	 *
	 * @param	string		$name			Setting name
	 * @param	mixed		$value			Setting value
	 * @return	void
	 */
	public function setDefault( $name, $value )
	{
		$this->defaults[ $name ] = $value;
	}
	
	/**
	 * Set Plugin
	 *
	 * @param	\MWP\Framework\Plugin	$plugin		The plugin associated with these settings
	 * @return	void
	 */
	public function setPlugin( \MWP\Framework\Plugin $plugin )
	{
		$this->plugin = $plugin;
	}
	
	/**
	 * Get Plugin
	 *
	 * @return	Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Get Storage ID
	 *
	 * @return	string
	 */
	public function getStorageId()
	{
		return $this->storageId;
	}
	
	/**
	 * Get A Setting
	 *
	 * @param	string		$name		The setting name
	 * @return	mixed
	 */
	public function getSetting( $name )
	{
		if ( ! isset( $this->settings ) )
		{
			$this->settings = get_option( $this->storageId, array() );
		}
		
		if ( array_key_exists( $name, $this->settings ) )
		{
			return $this->settings[ $name ];
		}
		
		return isset( $this->defaults[ $name ] ) ? $this->defaults[ $name ] : NULL;
	}
	
	/**
	 * Set A Setting
	 *
	 * @param	string		$name		The setting name
	 * @param	mixed		$val		The setting value
	 * @return	this
	 */
	public function setSetting( $name, $val )
	{
		if ( ! isset( $this->settings ) )
		{
			$this->settings = get_option( $this->storageId, array() );
		}
		
		return $this;
	}
	
	/**
	 * Persist settings to the database
	 *
	 * @return	this
	 */
	public function saveSettings()
	{
		if ( ! isset( $this->settings ) )
		{
			$this->settings = get_option( $this->storageId, array() );
		}
		
		update_option( $this->storageId, $this->settings );
		return $this;
	}
	
	/**
	 * Validate Settings
	 *
	 * @param	array		$data			Input data
	 * @return	array
	 */
	public function validate( $data=array() )
	{
		return $data;
	}
	
}
