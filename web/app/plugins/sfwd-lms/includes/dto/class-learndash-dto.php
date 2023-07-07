<?php
/**
 * This class provides the easy way to operate data.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_DTO' ) ) {
	/**
	 * DTO class.
	 *
	 * @since 4.5.0
	 */
	abstract class Learndash_DTO {
		/**
		 * Properties are being cast to the specified type on construction according to the $cast property.
		 * Key is a property name, value is a PHP type which will be passed into "settype".
		 *
		 * @since 4.5.0
		 *
		 * @var array<string, string>
		 */
		protected $cast = array();

		/**
		 * Property keys to be excluded from the DTO.
		 *
		 * @since 4.5.0
		 *
		 * @var string[]
		 */
		protected $except_keys = array();

		/**
		 * Property keys to be included into the DTO.
		 *
		 * @since 4.5.0
		 *
		 * @var string[]
		 */
		protected $only_keys = array();

		/**
		 * Reflection class for a static DTO.
		 *
		 * @since 4.5.0
		 *
		 * @var ReflectionClass
		 */
		private $reflection_class;

		/**
		 * Constructor.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $args DTO properties.
		 *
		 * @throws Learndash_DTO_Validation_Exception If one or more properties are invalid.
		 *
		 * @return void
		 */
		final public function __construct( array $args = array() ) {
			$this->reflection_class = new ReflectionClass( $this );

			foreach ( $this->get_properties() as $property ) {
				$property_value = Learndash_Arr::get(
					$args,
					$property->getName(),
					$property->getDeclaringClass()->getDefaultProperties()[ $property->getName() ] ?? null // Property default value.
				);

				if ( isset( $this->cast[ $property->getName() ] ) ) {
					$property_type = $this->cast[ $property->getName() ];

					if (
						in_array(
							$property_type,
							array( 'bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'array', 'object', 'null' ),
							true
						)
					) {
						settype( $property_value, $this->cast[ $property->getName() ] );
					} elseif ( class_exists( $property_type ) && ! $property_value instanceof $property_type ) {
						$property_value = is_array( $property_value )
							? new $property_type( $property_value )
							: new $property_type();
					}
				}

				$property->setValue( $this, $property_value );
			}

			$this->validate();
		}

		/**
		 * Creates a DTO instance.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $args DTO properties.
		 *
		 * @throws Learndash_DTO_Validation_Exception If one or more properties are invalid.
		 *
		 * @return static
		 */
		public static function create( array $args = array() ): self {
			return new static( $args );
		}

		/**
		 * Returns all properties. Keys are property names.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,mixed>
		 */
		public function all(): array {
			$data = array();

			foreach ( $this->get_properties() as $property ) {
				$data[ $property->getName() ] = $property->getValue( $this );
			}

			return $data;
		}

		/**
		 * Includes a property or multiple properties in the DTO.
		 *
		 * @since 4.5.0
		 *
		 * @param string ...$keys Keys.
		 *
		 * @return static
		 */
		public function only( string ...$keys ): self {
			$clone = clone $this;

			$clone->only_keys = array_merge( $this->only_keys, $keys );

			return $clone;
		}

		/**
		 * Excludes a property or multiple properties from the DTO.
		 *
		 * @since 4.5.0
		 *
		 * @param string ...$keys Keys.
		 *
		 * @return static
		 */
		public function except( string ...$keys ): self {
			$clone = clone $this;

			$clone->except_keys = array_merge( $this->except_keys, $keys );

			return $clone;
		}

		/**
		 * Returns an array of properties.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,mixed>
		 */
		public function to_array(): array {
			if ( count( $this->only_keys ) ) {
				$array = Learndash_Arr::only( $this->all(), $this->only_keys );
			} else {
				$array = Learndash_Arr::except( $this->all(), $this->except_keys );
			}

			return $this->parse_array( $array ); // @phpstan-ignore-line -- It's array<string,mixed> actually.
		}

		/**
		 * Returns a json encoded array of properties.
		 *
		 * @since 4.5.0
		 *
		 * @return string|false
		 */
		public function to_json() {
			return wp_json_encode( $this->to_array() );
		}

		/**
		 * Validates properties on construction based on validators.
		 * Key is a property name, value is an array of validator objects.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,Learndash_DTO_Property_Validator[]>
		 */
		protected function get_validators(): array {
			return array();
		}

		/**
		 * Recursively parses an array and converts DTOs to arrays.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $array Array.
		 *
		 * @return array<string,mixed>
		 */
		private function parse_array( array $array ): array {
			foreach ( $array as $key => $value ) {
				if ( $value instanceof Learndash_DTO ) {
					$array[ $key ] = $value->to_array();

					continue;
				}

				if ( ! is_array( $value ) ) {
					continue;
				}

				$array[ $key ] = $this->parse_array( $value );
			}

			return $array;
		}

		/**
		 * Returns public properties.
		 *
		 * @since 4.5.0
		 *
		 * @throws Learndash_DTO_Validation_Exception If one or more properties are invalid.
		 *
		 * @return void
		 */
		private function validate(): void {
			$validation_errors = array();

			$validators = $this->get_validators();

			foreach ( $this->get_properties() as $property ) {
				if ( ! isset( $validators[ $property->getName() ] ) ) {
					continue;
				}

				foreach ( $validators[ $property->getName() ] as $validator ) {
					$result = $validator->validate( $property->getValue( $this ) );

					if ( $result->is_valid() ) {
						continue;
					}

					$validation_errors[ $property->getName() ][] = $result;
				}
			}

			if ( count( $validation_errors ) ) {
				throw new Learndash_DTO_Validation_Exception( $this, $validation_errors );
			}
		}

		/**
		 * Returns public properties.
		 *
		 * @since 4.5.0
		 *
		 * @return ReflectionProperty[]
		 */
		private function get_properties(): array {
			return array_filter(
				$this->reflection_class->getProperties( ReflectionProperty::IS_PUBLIC ),
				function ( ReflectionProperty $property ) {
					return ! $property->isStatic();
				}
			);
		}
	}
}
