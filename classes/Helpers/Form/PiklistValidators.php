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
namespace MWP\Framework\Helpers\Form;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}


/**
 * A container for our custom validation and sanitization routines 
 */
class PiklistValidators
{
	/**
	 * Register piklist validation rules
	 * 
	 * @Wordpress\Filter( for="piklist_validation_rules" )
	 * 
	 * @param	array		$rules				The piklist validation rules array
	 */
	public function piklistValidationRules( $rules )
	{
		$rules[ 'mwp_validate_choices' ] = array
		(
			'callback' => function( $index, $value, $options, $field, $fields ) 
			{
				if ( $value and ! in_array( $value, array_keys( $field[ 'choices' ] ) ) ) {
					return __( 'The selected value was not one of the given choices.', 'mwp-framework' );
				}
				
				return true;
			},
		);
		
		$rules[ 'mwp_validate_number' ] = array
		(
			'callback' => function( $index, $value, $options, $field, $fields ) 
			{
				if ( ! is_numeric( $value ) ) {
					return __( 'The value is expected to be numeric.', 'mwp-framework' );
				}
				
				if ( isset( $field[ 'attributes' ][ 'min' ] ) and $value < $field[ 'attributes' ][ 'min' ] ) {
					return __( 'The value is less than the minimum value of: ' . $field[ 'attributes' ][ 'min' ], 'mwp-framework' );
				}
				
				if ( isset( $field[ 'attributes' ][ 'max' ] ) and $value > $field[ 'attributes' ][ 'max' ] ) {
					return __( 'The value is more than the maximum value of: ' . $field[ 'attributes' ][ 'max' ], 'mwp-framework' );
				}
				
				return true;
			},
		);
		
		return $rules;
	}
	
	/**
	 * Register piklist sanitization rules
	 * 
	 * @Wordpress\Filter( for="piklist_sanitization_rules" )
	 * 
	 * @param	array		$rules				The piklist sanitizations rules array
	 */
	public function piklistSanitizationRules( $rules )
	{		
		$rules[ 'mwp_sanitize_number' ] = array
		(
			'callback' => function( $value, $field, $options ) 
			{
				// allow empty submission if field is not required
				if ( $value === "" and $field[ 'required' ] == false ) { return $value; }
				
				// replace non numeric characters and cast to either int or float with a + 0
				$value = preg_replace( "/[^0-9.]/", "", $value ) + 0;
				
				// round to decimal precision if step is specified
				if ( isset( $field[ 'attributes' ][ 'step' ] ) )
				{
					$decimal_len = 0;
					if ( strpos( $field[ 'attributes' ][ 'step' ], '.' ) )
					{
						$parts = explode( '.', $field[ 'attributes' ][ 'step' ] );
						$decimals = array_pop( $parts );
						$decimal_len = strlen( $decimals );
					}	
					$value = round( $value, $decimal_len );
				}
				
				return $value;
			},
		);
		
		return $rules;
	}
}
