<?php
/**
 * Exception for 413 Request Entity Too Large responses
 *
 * @package Requests\Exceptions
 */

namespace StellarWP\Learndash\WpOrg\Requests\Exception\Http;

use StellarWP\Learndash\WpOrg\Requests\Exception\Http;

/**
 * Exception for 413 Request Entity Too Large responses
 *
 * @package Requests\Exceptions
 */
final class Status413 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 413;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Request Entity Too Large';
}
