<?php
/**
 * Plugin Class File
 *
 * Created:   January 25, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.1.4
 */
namespace MWP\Framework\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Form Class
 */
abstract class Form
{	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
	/**
	 * @var	string
	 */
	public $name;
	
	/**
	 * @var	string
	 */
	public $method = "POST";
	
	/**
	 * @var	string
	 */
	public $action = "";
	
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
	 * Enable or disable csrf protection
	 *
	 * @param	bool			$bool			Either true for ON or false for OFF
	 * @return	this							Chainable
	 */
	abstract public function csrf( $bool );
	
	/**
	 * Add a field to the form
	 *
	 * @param	string		$name			The field name
	 * @param	string		$type			The field type
	 * @param	array		$options		The field options
	 * @return	this						Chainable
	 */
	abstract public function addField( $name, $type='text', $options=array() );
	
	/**
	 * Check if form was submitted
	 *
	 * @return	bool
	 */
	abstract public function isSubmitted();
	
	/**
	 * Check for valid form submission
	 *
	 * @return	bool
	 */
	abstract public function isValidSubmission();
	
	/**
	 * Get the form submission data
	 *
	 * @return	array|false
	 */
	abstract public function getSubmissionData();
	
	/**
	 * Get submitted form values
	 *
	 * @return	array
	 */
	abstract public function getValues();
	
	/**
	 * Get form submission errors
	 *
	 * @return	array			Fields that had errors
	 */
	abstract public function getErrors();

	/**
	 * Get form output
	 *
	 * @return	string
	 */
	abstract public function render();
	
	/** 
	 * Convert to string value
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch( \Exception $e )
		{
			return $e->getMessage();
		}
	}
	
	/**
	 * @var	array
	 */
	public $completeCallbacks = array();
	
	/**
	 * Add a form processing complete callback
	 *
	 * @param	callable		$callback			A function name, closure, or callable
	 * @return	void
	 */
	public function onComplete( $callback )
	{
		if ( is_callable( $callback ) ) {
			$this->completeCallbacks[] = $callback;
		}
	}
	
	/**
	 * Signal that the processing of a successful form submission is complete, allowing hooks to run
	 *
	 * @param	callback		$final_callback			An executable callback to filter and run
	 * @return	mixed
	 */
	public function processComplete( $final_callback=NULL )
	{
		$final_callback = $this->applyFilters( 'process_complete', $final_callback );
		
		foreach( $this->completeCallbacks as $_callback ) {
			call_user_func( $_callback );
		}
		
		if( is_callable( $final_callback ) ) {
			return call_user_func( $final_callback );
		}
	}
	
	/**
	 * Apply a standard set of filters
	 *
	 * @param	string		$action			The action being filtered
	 * @return	mixed
	 */
	public function applyFilters( $action, $value )
	{
		// allow global modifications for this action
		$value = apply_filters( 'mwp_form_' . $action, $value, $this );
		
		// allow plugin specific modifications for this action
		$value = apply_filters( 'mwp_form_' . $action . '_' . $this->getPluginSlug(), $value, $this );
		
		// allow form specific modifications for this action
		$value = apply_filters( 'mwp_form_' . $action . '_' . $this->getPluginSlug() . '_' . $this->name, $value, $this );
		
		return $value;
	}
	
}
