<?php
/**
 * The API implemented by all subscribers.
 *
 * @package StellarWP\Learndash\StellarWP\Telemetry\Contracts
 *
 * @license GPL-2.0-or-later
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\Telemetry\Contracts;

/**
 * Interface Subscriber_Interface
 *
 * @package StellarWP\Learndash\StellarWP\Telemetry\Contracts
 */
interface Subscriber_Interface {

	/**
	 * Register action/filter listeners to hook into WordPress
	 *
	 * @return void
	 */
	public function register();

}
