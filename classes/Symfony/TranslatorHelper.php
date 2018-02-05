<?php
/**
 * Plugin Class File
 *
 * Created:   April 2, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */
namespace MWP\Framework\Symfony;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use Symfony\Component\Templating\Helper\Helper;

/**
 * FormHelper Class
 */
class TranslatorHelper extends Helper
{

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator A TranslatorInterface instance
     */
    public function __construct()
    {
        
    }
	
    /**
     * Translate string
     */
    public function trans( $id, array $parameters = array(), $domain = '', $locale = null )
    {
        return __( $id, $domain );
    }
	
    /**
     * Translate choice
     */
    public function transChoice( $id, $number, array $parameters = array(), $domain = '', $locale = null )
    {
        return __( $id, $domain );
    }
	
    /**
     * Get helper name
     */
    public function getName()
    {
        return 'translator';
    }

}
