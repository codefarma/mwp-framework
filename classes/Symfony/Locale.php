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

use Symfony\Component\Intl\Locale\Locale as IntlLocale;

/**
 * Locale Class
 */
class Locale extends IntlLocale
{
	static $available_translations;

	public static function getWPAvailableTranslations() {
		if ( ! isset( static::$available_translations ) ) {
			include_once( ABSPATH . '/wp-admin/includes/translation-install.php' );
			static::$available_translations = \wp_get_available_translations();
		}

		return static::$available_translations;
	}

    /**
     * Returns the localized display name for the locale.
     *
     * @param string $locale   The locale code to return the display locale name from
     * @param string $inLocale Optional format locale code to use to display the locale name
     *
     * @return string The localized locale display name
     *
     * @see http://www.php.net/manual/en/locale.getdisplayname.php
     */
    public static function getDisplayName($locale, $inLocale = null)
    {
		$translations = static::getWPAvailableTranslations();

		if ( isset( $translations[ $locale ]['native_name'] ) ) {
			return $translations[ $locale ]['native_name'];
		} else {
			if ( isset( $translations[ $inLocale ]['native_name'] ) ) {
				return $translations[ $inLocale ]['native_name'];
			}
		}

		return $locale;
    }

	
}
