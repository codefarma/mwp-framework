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
	 * @var	bool	Indicates if these settings are network wide
	 */
	public $isNetworkGlobal = false;
	
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
	 * Load the settings
	 *
	 */
	public function refreshSettings()
	{
		$this->settings = $this->isNetworkGlobal ? get_site_option( $this->storageId, array() ) : get_option( $this->storageId, array() );
	}
	
	/**
	 * Get A Setting
	 *
	 * @param	string		$name		The setting name
	 * @return	mixed
	 */
	public function getSetting( $name )
	{
		if ( ! isset( $this->settings ) ) {
			$this->refreshSettings();
		}
		
		if ( array_key_exists( $name, $this->settings ) ) {
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
		if ( ! isset( $this->settings ) ) {
			$this->refreshSettings();
		}
		
		$this->settings[ $name ] = $val;
		$this->saveSettings();
		
		return $this;
	}
	
	/**
	 * Persist settings to the database
	 *
	 * @return	this
	 */
	public function saveSettings()
	{
		if ( ! isset( $this->settings ) ) {
			$this->refreshSettings();
		}
		
		$this->isNetworkGlobal ? update_site_option( $this->storageId, $this->settings ) : update_option( $this->storageId, $this->settings );
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
