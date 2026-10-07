<?php
/**
 * Exception for 305 Use Proxy responses
 *
 * @package Requests\Exceptions
 */

namespace StellarWP\Learndash\WpOrg\Requests\Exception\Http;

use StellarWP\Learndash\WpOrg\Requests\Exception\Http;

/**
 * Exception for 305 Use Proxy responses
 *
 * @package Requests\Exceptions
 */
final class Status305 extends Http {
	/**
	 * HTTP status code
	 *
	 * @var integer
	 */
	protected $code = 305;

	/**
	 * Reason phrase
	 *
	 * @var string
	 */
	protected $reason = 'Use Proxy';
}
