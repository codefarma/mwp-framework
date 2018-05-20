<?php
/**
 * Plugin Class File
 *
 * Created:   January 4, 2018
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.4.0
 */
namespace MWP\Framework;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * AjaxHandlers Class
 */
class _AjaxHandlers extends \MWP\Framework\Pattern\Singleton
{
	/**
	 * @var	self
	 */
	protected static $_instance;
	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
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
	public function setPlugin( \MWP\Framework\Plugin $plugin=NULL )
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
	public function __construct( \MWP\Framework\Plugin $plugin=NULL )
	{
		$this->setPlugin( $plugin ?: \MWP\Framework\Framework::instance() );
	}
	
	/**
	 * Load available studio projects
	 *
	 * @MWP\WordPress\AjaxHandler( action="mwp_resequence_records", for={"users"} )
	 *
	 * @return	void
	 */
	public function resequenceRecords()
	{
		check_ajax_referer( 'mwp-ajax-nonce', 'nonce' );
		
		if ( current_user_can( 'administrator' ) ) 
		{
			$recordClass = wp_unslash( $_POST['class'] );
			$sequence = $recordClass::_getSequenceCol();
			
			if ( class_exists( $recordClass ) and is_subclass_of( $recordClass, 'MWP\Framework\Pattern\ActiveRecord' ) and isset( $sequence ) ) {
				foreach( $_POST['sequence'] as $index => $record_id ) {
					$record = $recordClass::load( $record_id );
					$record->$sequence = $index + 1;
					$record->save();
					$record->flush();
					unset( $record );
				}
				
				wp_send_json( array( 'success' => true ) );
			}
		}
	}
}
