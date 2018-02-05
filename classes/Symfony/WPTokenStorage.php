<?php
/**
 * Plugin Class File
 *
 * Created:   April 9, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */
namespace MWP\Framework\Symfony;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Token storage that uses Wordpress transients
 */
class WPTokenStorage implements \Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface
{
    /**
     * The namespace used to store values in the session.
     *
     * @var string
     */
    const SESSION_NAMESPACE = '_csrf';

    /**
     * @var bool
     */
    private $sessionStarted = false;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Initializes the storage with a session namespace.
     *
     * @param string $namespace The namespace under which the token is stored
     *                          in the session
     */
    public function __construct($namespace = self::SESSION_NAMESPACE)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        $token = get_transient( $this->namespace . $tokenId );

		if ( $token === false ) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $token;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token)
    {
        set_transient( $this->namespace . $tokenId, $token, 60 * 60 );
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId)
    {
        $token = get_transient( $this->namespace . $tokenId );
		
		return $token !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId)
    {
        delete_transient( $this->namespace . $tokenId );
    }
}
