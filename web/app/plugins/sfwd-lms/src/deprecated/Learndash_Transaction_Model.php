<?php
/**
 * Deprecated. Use LearnDash\Core\Models\Transaction instead.
 *
 * This class provides the easy way to operate transactions.
 *
 * @since 4.5.0
 * @deprecated 4.6.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'4.6.0',
	esc_html( LEARNDASH_LMS_PLUGIN_DIR . '/src/Core/Models/Transaction.php' )
);

if ( ! class_exists( 'Learndash_Transaction_Model' ) ) {
	/**
	 * Transaction model class.
	 *
	 * @since 4.5.0
	 * @deprecated 4.6.0
	 */
	class Learndash_Transaction_Model extends \LearnDash\Core\Models\Transaction {
		/**
		 * Sets multiple attributes.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @param array<string,mixed> $attributes Attributes. Keys are attribute names, values are attribute values.
		 *
		 * @return void
		 */
		public function set_attributes( array $attributes ): void {
			_deprecated_function( __METHOD__, '4.6.0', 'fill' );

			foreach ( $attributes as $attribute_name => $attribute_value ) {
				$this->set_attribute( $attribute_name, $attribute_value );
			}
		}

		/**
		 * Returns an attribute value or null if not found.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @param string $attribute_name  Attribute name.
		 * @param mixed  $attribute_value Attribute value.
		 *
		 * @return void
		 */
		public function set_attribute( string $attribute_name, $attribute_value ): void {
			_deprecated_function( __METHOD__, '4.6.0', 'setAttribute' );

			$this->attributes[ $attribute_name ] = $attribute_value;
		}

		/**
		 * Returns all attributes.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @return array<string,mixed>
		 */
		public function get_attributes(): array {
			_deprecated_function( __METHOD__, '4.6.0', 'toArray' );

			return $this->toArray();
		}

		/**
		 * Returns an attribute value or null if not found.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @param string $attribute_name Attribute name.
		 * @param mixed  $default        Default value.
		 *
		 * @return mixed
		 */
		public function get_attribute( string $attribute_name, $default = null ) {
			_deprecated_function( __METHOD__, '4.6.0', 'getAttribute' );

			return $this->attributes[ $attribute_name ] ?? $default;
		}

		/**
		 * Removes all attributes.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @return void
		 */
		public function clear_attributes(): void {
			_deprecated_function( __METHOD__, '4.6.0' );

			$this->attributes = array();
		}

		/**
		 * Removes an attribute.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @param string $attribute_name Attribute name.
		 *
		 * @return void
		 */
		public function remove_attribute( string $attribute_name ): void {
			_deprecated_function( __METHOD__, '4.6.0' );

			unset( $this->attributes[ $attribute_name ] );
		}

		/**
		 * Returns true if an attribute exists. Otherwise, false.
		 *
		 * @since 4.5.0
		 * @deprecated 4.6.0
		 *
		 * @param string $attribute_name Attribute name.
		 *
		 * @return bool
		 */
		public function has_attribute( string $attribute_name ): bool {
			_deprecated_function( __METHOD__, '4.6.0', 'hasAttribute' );

			return isset( $this->attributes[ $attribute_name ] );
		}
	}
}
