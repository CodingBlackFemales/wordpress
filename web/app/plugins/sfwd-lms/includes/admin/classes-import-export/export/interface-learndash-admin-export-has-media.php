<?php
/**
 * LearnDash Admin Export Has Media Interface.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Learndash_Admin_Export_Has_Media {
	/**
	 * Returns media IDs.
	 *
	 * @since 4.3.0
	 *
	 * @return int[]
	 */
	public function get_media(): array;
}
