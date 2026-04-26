<?php

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
