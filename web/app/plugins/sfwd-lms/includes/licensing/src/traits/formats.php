<?php

namespace LearnDash\Hub\Traits;

defined( 'ABSPATH' ) || exit;

trait Formats {
	/**
	 * Strip protocols
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function strips_protocol( string $url ): string {
		$parts = parse_url( $url );

		$host = $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : null );
		$host = rtrim( $host, '/' );

		return $host;
	}
}
