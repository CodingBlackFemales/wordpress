<?php
/**
 * Replace OpenAPI $ref component values with relative URIs so LLMs
 * can fetch the JSON spec of the component.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Manifest;

/**
 * OpenAPI $ref replacer.
 */
class Ref_Replacer {
	/**
	 * The OpenAPI 3.0 reusable schema definition to find.
	 *
	 * @var string
	 */
	private const FIND = '#/components/schemas/';

	/**
	 * The relative URI to replace it with.
	 *
	 * @var string
	 */
	private const REPLACE = '/learndash/v1/manifest/component/';

	/**
	 * Replace OpenAPI $ref component values with relative URIs.
	 *
	 * @note Recursive method.
	 *
	 * @since 5.0.0
	 *
	 * @param array<string, mixed> $schema The schema.
	 *
	 * @return array<string, mixed> The modified schema.
	 */
	public function replace( array $schema ): array {
		foreach ( $schema as $key => $value ) {
			if ( $key === '$ref' && is_string( $value ) ) {
				$component      = str_replace( self::FIND, '', $value );
				$schema['href'] = self::REPLACE . rawurlencode( $component );
				unset( $schema[ $key ] );
			} elseif ( is_array( $value ) ) {
				$schema[ $key ] = $this->replace( $value );
			}
		}

		return $schema;
	}
}
