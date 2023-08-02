<?php
/**
 * Base model class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use StellarWP\Learndash\StellarWP\Models\Contracts\Model as ModelInterface;
use StellarWP\Learndash\StellarWP\Models\Model as StellarModel;

/**
 * Base model class.
 *
 * @since 4.6.0
 */
abstract class Model extends StellarModel {
	/**
	 * Returns true if an attribute exists. Otherwise, false.
	 *
	 * @since 4.6.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return bool
	 */
	public function hasAttribute( string $key ): bool {
		return array_key_exists( $key, $this->attributes );
	}

	/**
	 * Get an attribute from the model.
	 * It was overridden to disable properties validation for now as they are dynamic. Properties must be added later and this method must be removed.
	 *
	 * @since 4.6.0
	 *
	 * @param string $key     Attribute name.
	 * @param mixed  $default Default value. Default null.
	 *
	 * @return mixed
	 */
	public function getAttribute( string $key, $default = null ) {
		return $this->attributes[ $key ] ?? $default;
	}

	/**
	 * Sets an attribute on the model.
	 * It was overridden to disable properties validation for now as they are dynamic. Properties must be added later and this method must be removed.
	 *
	 * @since 4.6.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ): ModelInterface {
		$this->attributes[ $key ] = $value;

		return $this;
	}
}
