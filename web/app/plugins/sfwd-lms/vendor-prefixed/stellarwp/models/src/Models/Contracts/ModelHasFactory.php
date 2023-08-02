<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\Models\Contracts;

use StellarWP\Learndash\StellarWP\Models\ModelFactory;

/**
 * @since 1.0.0
 */
interface ModelHasFactory {
	/**
	 * @since 1.0.0
	 *
	 * @return ModelFactory
	 */
	public static function factory();
}
