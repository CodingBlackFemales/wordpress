<?php
/**
 * LearnDash Sanitization class.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

use InvalidArgumentException;

/**
 * A helper class to sanitize various types of data.
 *
 * @since 4.8.0
 */
class Sanitize {
	/**
	 * Sanitize array recursively.
	 *
	 * @since 4.8.0
	 *
	 * @throws InvalidArgumentException Throws an exception when $sanitize_fn argument is invalid.
	 *
	 * @param array<mixed>    $array       Array in key value pair.
	 * @param string|callable $sanitize_fn Sanitization function name or callable to sanitize the array value.
	 *
	 * @return array<mixed>
	 */
	public static function array( array $array, $sanitize_fn = 'sanitize_text_field' ): array {
		if ( ! is_callable( $sanitize_fn ) ) {
			throw new InvalidArgumentException( 'Sanitization function or callback is invalid.' );
		}

		return array_map(
			function( $value ) use ( $sanitize_fn ) {
				return is_array( $value )
					? self::array( $value )
					: call_user_func( $sanitize_fn, $value );
			},
			$array
		);
	}

	/**
	 * Sanitizes a boolean value.
	 *
	 * @since 5.0.0
	 *
	 * @param scalar|null $value         The value to sanitize. Bools are returned directly, ints and floats are cast to bools, strings are checked for their content to determine if they are true or false. Null and empty strings return the default value.
	 * @param bool        $default_value The default value to return if the value is null or an empty string. Defaults to false.
	 *
	 * @return bool
	 */
	public static function bool( $value, bool $default_value = false ): bool {
		if (
			$value === null
			|| (
				is_string( $value )
				&& strlen( trim( $value ) ) === 0
			)
		) {
			return $default_value;
		}

		return rest_sanitize_boolean( Cast::to_string( $value ) );
	}

	/**
	 * Returns an array of allowed HTML tags for posts that includes SVG tags.
	 *
	 * @since 4.16.0
	 *
	 * @return array<string, array<string, bool|string|int|float>> Array of allowed HTML tags and attributes.
	 */
	public static function extended_kses(): array {
		$kses_defaults = wp_kses_allowed_html( 'post' );

		// Note: While HTML tags and attributes are case-insensitive, wp_kses requires them to be lowercase.
		$svg_args = [
			'svg'                 => [
				'aria-hidden'     => true,
				'aria-label'      => true,
				'aria-labelledby' => true,
				'baseprofile'     => true,
				'class'           => true,
				'height'          => true,
				'id'              => true,
				'role'            => true,
				'version'         => true,
				'viewbox'         => true,
				'width'           => true,
				'xmlns'           => true,
			],
			'symbol'              => [
				'class' => true,
				'id'    => true,
			],
			'use'                 => [
				'class'      => true,
				'id'         => true,
				'xlink:href' => true,
			],
			'defs'                => [
				'class'     => true,
				'clip-path' => true,
				'id'        => true,
			],
			'clippath'            => [
				'class' => true,
				'id'    => true,
			],
			'g'                   => [
				'class'        => true,
				'clip-rule'    => true,
				'fill'         => true,
				'fill-rule'    => true,
				'id'           => true,
				'mask'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'mask'                => [
				'class'     => true,
				'height'    => true,
				'id'        => true,
				'maskunits' => true,
				'style'     => true,
				'width'     => true,
				'x'         => true,
				'y'         => true,
			],
			'title'               => [
				'class' => true,
				'id'    => true,
				'title' => true,
			],
			'path'                => [
				'class'             => true,
				'clip-rule'         => true,
				'd'                 => true,
				'fill'              => true,
				'fill-rule'         => true,
				'id'                => true,
				'stroke'            => true,
				'stroke-dasharray'  => true,
				'stroke-dashoffset' => true,
				'stroke-linecap'    => true,
				'stroke-linejoin'   => true,
				'stroke-width'      => true,
			],
			'line'                => [
				'class'           => true,
				'id'              => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'x1'              => true,
				'x2'              => true,
				'y1'              => true,
				'y2'              => true,
			],
			'polyline'            => [
				'class'           => true,
				'id'              => true,
				'points'          => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
			],
			'circle'              => [
				'class'        => true,
				'cx'           => true,
				'cy'           => true,
				'fill'         => true,
				'height'       => true,
				'id'           => true,
				'r'            => true,
				'rx'           => true,
				'ry'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'transform'    => true,
				'width'        => true,
				'x'            => true,
				'y'            => true,
			],
			'ellipse'             => [
				'class'        => true,
				'cx'           => true,
				'cy'           => true,
				'fill'         => true,
				'height'       => true,
				'id'           => true,
				'rx'           => true,
				'ry'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'transform'    => true,
				'width'        => true,
				'x'            => true,
				'y'            => true,
			],
			'rect'                => [
				'class'        => true,
				'fill'         => true,
				'height'       => true,
				'id'           => true,
				'rx'           => true,
				'ry'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'transform'    => true,
				'width'        => true,
				'x'            => true,
				'y'            => true,
			],
			'image'               => [
				'class'               => true,
				'height'              => true,
				'id'                  => true,
				'preserveaspectratio' => true,
				'width'               => true,
				'x'                   => true,
				'xlink:href'          => true,
				'y'                   => true,
			],
			'text'                => [
				'class'        => true,
				'dx'           => true,
				'dy'           => true,
				'fill'         => true,
				'id'           => true,
				'lengthadjust' => true,
				'rotate'       => true,
				'stroke'       => true,
				'stroke-width' => true,
				'text-anchor'  => true,
				'x'            => true,
				'y'            => true,
			],
			'tspan'               => [
				'class'        => true,
				'dx'           => true,
				'dy'           => true,
				'fill'         => true,
				'id'           => true,
				'lengthadjust' => true,
				'rotate'       => true,
				'stroke'       => true,
				'stroke-width' => true,
				'text-anchor'  => true,
				'x'            => true,
				'y'            => true,
			],
			'textpath'            => [
				'class'        => true,
				'fill'         => true,
				'id'           => true,
				'lengthadjust' => true,
				'startoffset'  => true,
				'stroke'       => true,
				'stroke-width' => true,
				'text-anchor'  => true,
				'x'            => true,
				'y'            => true,
			],
			'switch'              => [
				'class' => true,
				'id'    => true,
			],
			'filter'              => [
				'class'  => true,
				'height' => true,
				'id'     => true,
				'width'  => true,
				'x'      => true,
				'y'      => true,
			],
			'fegaussianblur'      => [
				'class'        => true,
				'id'           => true,
				'in'           => true,
				'result'       => true,
				'stddeviation' => true,
			],
			'feoffset'            => [
				'class'  => true,
				'dx'     => true,
				'dy'     => true,
				'id'     => true,
				'in'     => true,
				'result' => true,
			],
			'feblend'             => [
				'class' => true,
				'id'    => true,
				'in'    => true,
				'in2'   => true,
				'mode'  => true,
			],
			'fecolormatrix'       => [
				'class'  => true,
				'id'     => true,
				'in'     => true,
				'result' => true,
				'type'   => true,
				'values' => true,
			],
			'fecomponenttransfer' => [
				'class'  => true,
				'id'     => true,
				'in'     => true,
				'result' => true,
			],
			'fecomposite'         => [
				'class'    => true,
				'id'       => true,
				'in'       => true,
				'in2'      => true,
				'k1'       => true,
				'k2'       => true,
				'k3'       => true,
				'k4'       => true,
				'operator' => true,
				'result'   => true,
			],
			'feconvolvematrix'    => [
				'bias'          => true,
				'class'         => true,
				'divisor'       => true,
				'edgemode'      => true,
				'id'            => true,
				'kernelmatrix'  => true,
				'order'         => true,
				'preservealpha' => true,
				'targetx'       => true,
				'targety'       => true,
			],
			'fediffuselighting'   => [
				'class'           => true,
				'diffuseconstant' => true,
				'id'              => true,
				'in'              => true,
				'result'          => true,
				'surfacescale'    => true,
			],
			'fedisplacementmap'   => [
				'class'            => true,
				'id'               => true,
				'in'               => true,
				'in2'              => true,
				'scale'            => true,
				'xchannelselector' => true,
				'ychannelselector' => true,
			],
			'feflood'             => [
				'class'         => true,
				'flood-color'   => true,
				'flood-opacity' => true,
				'id'            => true,
				'result'        => true,
			],
			'feimage'             => [
				'class'               => true,
				'id'                  => true,
				'preserveaspectratio' => true,
				'result'              => true,
				'xlink:href'          => true,
			],
			'femerge'             => [
				'class'  => true,
				'id'     => true,
				'result' => true,
			],
			'femergenode'         => [
				'class' => true,
				'id'    => true,
				'in'    => true,
			],
			'femorphology'        => [
				'class'    => true,
				'id'       => true,
				'in'       => true,
				'operator' => true,
				'radius'   => true,
				'result'   => true,
			],
			'fespecularlighting'  => [
				'class'            => true,
				'id'               => true,
				'in'               => true,
				'result'           => true,
				'specularconstant' => true,
				'specularexponent' => true,
				'surfacescale'     => true,
			],
			'fetile'              => [
				'class'  => true,
				'id'     => true,
				'in'     => true,
				'result' => true,
			],
			'feturbulence'        => [
				'basefrequency' => true,
				'class'         => true,
				'id'            => true,
				'numoctaves'    => true,
				'result'        => true,
				'seed'          => true,
				'stitchtiles'   => true,
				'type'          => true,
			],
		];

		return array_merge( $kses_defaults, $svg_args );
	}
}
