<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * This class will handle the data's sanitize and validation, it doesn't do the saving.
 * Class Model
 *
 * @package LearnDash\Hub
 * @property array attributes A magic property, use for mass assignment.
 */
abstract class Model extends Base implements \ArrayAccess {
	/**
	 * The record ID
	 *
	 * @var int
	 * @property
	 */
	public $id = 0;

	/**
	 * Contains all the validation rules, the format is
	 * [
	 *   [['property1','property2],'validator','message'=>'Optional']
	 * ]
	 *
	 * @var array
	 */
	protected $rules;

	/**
	 * After get validated, the validation errors will be stored in the property.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * Contain the annotation metadata.
	 *
	 * @var array
	 */
	protected $annotations;

	/**
	 * Model constructor.
	 */
	public function __construct() {
		$this->parse_annotations();
	}

	/**
	 * The validator engine.
	 *
	 * This method is not used in this version of the plugin.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		return false;
	}

	/**
	 * Retrieve all validation errors;
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Retrieve the errors relate to a single property.
	 *
	 * @param string $key The error key.
	 *
	 * @return string
	 */
	public function get_error( string $key ): string {
		return $this->errors[ $key ] ?? '';
	}

	/**
	 * Set value of an object property.
	 *
	 * @param string $name  The property name.
	 * @param mixed  $value The property value.
	 *
	 * @throws \Exception If data is not an array.
	 */
	public function __set( string $name, $value ) {
		if ( 'attributes' === $name ) {
			// massive assignments.
			if ( ! is_array( $value ) ) {
				throw new \Exception( 'Invalid data for a mass assignment.' );
			}
			foreach ( $value as $key => $val ) {
				if ( ! property_exists( $this, $key ) ) {
					throw new \Exception( "Property {$key} not exists." );
				}
				if ( isset( $this->annotations[ $key ] ) ) {
					$type = $this->annotations[ $key ]['type'];
					if ( 'boolean' === $type || 'bool' === $type ) {
						$val = filter_var( $val, FILTER_VALIDATE_BOOLEAN );
					} elseif ( null === $val && 'string' === $type ) {
						$val = '';
					} else {
						settype( $val, $type );
					}
				}
				// thanks to the ArrayAccess interface, so we can use object as array.
				$this[ $key ] = $val;
			}
		}
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the SPL interface [[\ArrayAccess]].
	 * It is implicitly called when you use something like `$value = $model[$offset];`.
	 *
	 * @param mixed $offset the offset to retrieve element.
	 *
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet( $offset ) {
		return $this->$offset;
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the SPL interface [[\ArrayAccess]].
	 * It is implicitly called when you use something like `$model[$offset] = $item;`.
	 *
	 * @param int   $offset the offset to set element.
	 * @param mixed $value  the element value.
	 */
	public function offsetSet( $offset, $value ) {
		$this->$offset = $value;
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the SPL interface [[\ArrayAccess]].
	 * It is implicitly called when you use something like `isset($model[$offset])`.
	 *
	 * @param mixed $offset the offset to check on.
	 *
	 * @return bool whether or not an offset exists.
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->$offset );
	}

	/**
	 * Sets the element value at the specified offset to null.
	 * This method is required by the SPL interface [[\ArrayAccess]].
	 * It is implicitly called when you use something like `unset($model[$offset])`.
	 *
	 * @param mixed $offset the offset to unset element.
	 */
	public function offsetUnset( $offset ) {
		$this->$offset = null;
	}

	/**
	 * In model, we going to parse the annotations if any.
	 *
	 * @return array
	 */
	public function to_array(): array {
		if ( empty( $this->annotations ) ) {
			return parent::to_array();
		}

		$return = array();
		foreach ( array_keys( $this->annotations ) as $property ) {
			if ( isset( $this[ $property ] ) ) {
				$return[ $property ] = $this[ $property ];
			}
		}

		return $return;
	}

	/**
	 * Parse the annotations of the class, and cache it. The list should be
	 * - type: for casting
	 * - sanitize_*: the list of sanitize_ functions, which should be run on this property
	 * - rule: the rule that we use for validation
	 */
	protected function parse_annotations() {
		$class      = new \ReflectionClass( static::class );
		$properties = $class->getProperties( \ReflectionProperty::IS_PUBLIC );
		foreach ( $properties as $property ) {
			$doc_block = $property->getDocComment();
			if ( ! stristr( $doc_block, '@property' ) ) {
				continue;
			}
			$this->annotations[ $property->getName() ] = array(
				'type'     => $this->parse_annotations_var( $doc_block ),
				'sanitize' => $this->parse_annotation_sanitize( $doc_block ),
				'rule'     => $this->parse_annotation_rule( $doc_block ),
			);
		}
	}

	/**
	 * Get the variable type
	 *
	 * @param string $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotations_var( string $docblock ) {
		$pattern = '/@var\s(.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			$type = trim( $matches[1] );

			// only allow right type.
			if ( in_array(
				$type,
				array(
					'boolean',
					'bool',
					'integer',
					'int',
					'float',
					'double',
					'string',
					'array',
					'object',
				)
			) ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * Get the sanitize function
	 *
	 * @param string $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotation_sanitize( string $docblock ) {
		$pattern = '/@(sanitize_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}

	/**
	 * Get the validation rule
	 *
	 * @param string $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotation_rule( string $docblock ) {
		$pattern = '/@(rule_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}
}
