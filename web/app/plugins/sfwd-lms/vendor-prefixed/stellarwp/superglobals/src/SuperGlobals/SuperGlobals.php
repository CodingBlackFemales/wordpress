<?php

namespace StellarWP\Learndash\StellarWP\SuperGlobals;

use StellarWP\Learndash\StellarWP\Arrays\Arr;

class SuperGlobals {
	/**
	 * Grab sanitized _SERVER variable.
	 *
	 * @since 1.0.0
	 *
	 * @see   Arr::get()
	 *
	 * @param string|array $var
	 * @param mixed        $default
	 *
	 * @return mixed
	 */
	public static function get_server_var( $var, $default = null ) {
		$data = [];

		// Prevent a slew of warnings every time we call this.
		if ( ! empty( $_SERVER ) ) {
			$data[] = (array) $_SERVER;
		}

		if ( empty( $data ) ) {
			return $default;
		}

		$unsafe = Arr::get_in_any( $data, $var, $default );
		return static::sanitize_deep( $unsafe );
	}

	/**
	 * Gets a value from `$_GET`.
	 *
	 * @since 1.0.0
	 *
	 * @see   Arr::get()
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function get_get_var( string $var, $default = null ) {
		$unsafe = Arr::get( (array) $_GET, $var, $default );
		return static::sanitize_deep( $unsafe );
	}

	/**
	 * Gets a value from `$_POST`.
	 *
	 * @since 1.0.0
	 *
	 * @see   Arr::get()
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get_post_var( string $var, $default = null ) {
		$unsafe = Arr::get( (array) $_POST, $var, $default );
		return static::sanitize_deep( $unsafe );
	}

	/**
	 * Gets a value from `$_ENV`.
	 *
	 * @since 1.3.0
	 *
	 * @see   Arr::get()
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get_env_var( string $var, $default = null ) {
		$unsafe = Arr::get( (array) $_ENV, $var, $default );
		return static::sanitize_deep( $unsafe );
	}

	/**
	 * Gets the requested superglobal variable.
	 *
	 * @param string $superglobal A superglobal, such as 'COOKIE', 'ENV', 'GET', 'POST', 'REQUEST', or 'SERVER'.
	 *
	 * @return mixed
	 */
	public static function get_raw_superglobal( string $superglobal ) {
		$superglobal = strtoupper( $superglobal );

		switch ( $superglobal ) {
			case '_COOKIE':
			case 'COOKIE':
				$var = $_COOKIE;
				break;
			case '_ENV':
			case 'ENV':
				$var = $_ENV;
				break;
			case '_GET':
			case 'GET':
				$var = $_GET;
				break;
			case '_POST':
			case 'POST':
				$var = $_POST;
				break;
			case '_REQUEST':
			case 'REQUEST':
				$var = $_REQUEST;
				break;
			case '_SERVER':
			case 'SERVER':
				$var = $_SERVER;
				break;
			default:
				return [];
		}

		return $var;
	}

	/**
	 * Gets the requested superglobal variable, sanitized.
	 *
	 * @param string $superglobal A superglobal, such as 'COOKIE', 'ENV', 'GET', 'POST', 'REQUEST', or 'SERVER'.
	 *
	 * @return mixed
	 */
	public static function get_sanitized_superglobal( string $superglobal ) {
		$var = static::get_raw_superglobal( $superglobal );
		return static::sanitize_deep( $var );
	}

	/**
	 * Tests to see if the requested variable is set either as a post field or as a URL
	 * param and returns the value if so.
	 *
	 * Post data takes priority over fields passed in the URL query. If the field is not
	 * set then $default (null unless a different value is specified) will be returned.
	 *
	 * The variable being tested for can be an array if you wish to find a nested value.
	 *
	 * @since 1.0.0
	 *
	 * @see   Arr::get()
	 *
	 * @param string|array $var
	 * @param mixed        $default
	 *
	 * @return mixed
	 */
	public static function get_var( $var, $default = null ) {
		$requests = [];

		// Prevent a slew of warnings every time we call this.
		if ( ! empty( $_REQUEST ) ) {
			$requests[] = (array) $_REQUEST;
		}

		if ( ! empty( $_POST ) ) {
			$requests[] = (array) $_POST;
		}

		if ( ! empty( $_GET ) ) {
			$requests[] = (array) $_GET;
		}

		if ( empty( $requests ) ) {
			return $default;
		}

		$unsafe = Arr::get_in_any( $requests, $var, $default );
		return static::sanitize_deep( $unsafe );
	}

	/**
	 * Sanitizes a value according to its type.
	 *
	 * The function will recursively sanitize array values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value, or values, to sanitize.
	 *
	 * @return mixed|null Either the sanitized version of the value, or `null` if the value is not a string, number or
	 *                    array.
	 */
	public static function sanitize_deep( &$value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			$value = htmlspecialchars( $value );
			return $value;
		}
		if ( is_int( $value ) ) {
			$value = filter_var( $value, FILTER_VALIDATE_INT );
			return $value;
		}
		if ( is_float( $value ) ) {
			$value = filter_var( $value, FILTER_VALIDATE_FLOAT );
			return $value;
		}
		if ( is_array( $value ) ) {
			array_walk( $value, [ __CLASS__, 'sanitize_deep' ] );
			return $value;
		}

		return null;
	}
}
