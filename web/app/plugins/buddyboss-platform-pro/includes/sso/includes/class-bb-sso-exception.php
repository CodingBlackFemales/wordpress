<?php
/**
 * SSO Exceptions.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BB_SSO_Exception
 *
 * @since 2.6.30
 */
class BB_SSO_Exception extends Exception {

	/**
	 * BB_SSO_Exception constructor.
	 *
	 * @param string         $message  The Exception message to throw.
	 * @param int            $code     The Exception code.
	 * @param Throwable|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct( $message = '', $code = 0, ?Throwable $previous = null ) {
		$message = sanitize_text_field( $message );
		parent::__construct( $message, $code, $previous );
	}
}
