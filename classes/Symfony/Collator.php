<?php
/**
 * Plugin Class File
 *
 * Created:   March 18, 2020
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    {plugin_version}
 */
namespace MWP\Framework\Symfony;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use Symfony\Component\Intl\Collator\Collator as IntlCollator;

/**
 * Collator Class
 */
class Collator extends IntlCollator
{

	public function __construct($locale) {

	}
	
	/**
	 * Sort array maintaining index association.
	 *
	 * @param array &$array   Input array
	 * @param int   $sortFlag Flags for sorting, can be one of the following:
	 *                        Collator::SORT_REGULAR - compare items normally (don't change types)
	 *                        Collator::SORT_NUMERIC - compare items numerically
	 *                        Collator::SORT_STRING - compare items as strings
	 *
	 * @return bool True on success or false on failure
	 */
	public function asort(&$array, $sortFlag = self::SORT_REGULAR)
	{
		$raw_data = $array;

		array_walk(
			$array,
			function ( &$value ) {
				$value = remove_accents( html_entity_decode( $value ) );
			}
		);

		uasort( $array, 'strcmp' );

		foreach ( $array as $key => $val ) {
			$array[ $key ] = $raw_data[ $key ];
		}

		return true;
	}
	
}
