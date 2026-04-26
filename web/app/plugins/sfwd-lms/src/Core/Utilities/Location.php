<?php
/**
 * LearnDash Location Utility class.
 *
 * @since 4.21.5
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

/**
 * A helper class to provide helpers for determining location.
 *
 * @since 4.21.5
 */
class Location {
	/**
	 * Determines if the given screen ID is a LearnDash admin page.
	 *
	 * @since 4.21.5
	 *
	 * @param string|null $screen_id The screen ID to check. If null, the current screen ID will be used.
	 *
	 * @return bool True if the provided screen ID is a LearnDash page, false otherwise.
	 */
	public static function is_learndash_admin_page( $screen_id = null ): bool {
		if (
			empty( $screen_id )
			&& is_admin()
			&& function_exists( 'get_current_screen' )
		) {
			$screen = get_current_screen();

			if ( ! empty( $screen->id ) ) {
				$screen_id = $screen->id;
			}
		}

		$return = false;

		$learndash_partial_page_ids = [
			'learndash',
			'sfwd',
			'ld-',
			'ld_',
			'lms-',
			'lms_',
		];

		if (
			! empty( $screen_id )
			&& Str::contains( $screen_id, $learndash_partial_page_ids )
		) {
			$return = true;
		}

		$learndash_full_page_ids = [
			'edit-groups', // LearnDash groups' listing page.
			'groups', // LearnDash groups' single editor page.
		];

		if (
			! empty( $screen_id )
			&& Str::matches( $screen_id, $learndash_full_page_ids )
		) {
			$return = true;
		}

		/**
		 * Filters whether the screen ID is a LearnDash admin page.
		 *
		 * @since 4.21.5
		 *
		 * @param bool        $return True if the screen ID is a LearnDash page, false otherwise.
		 * @param string|null $screen The screen ID.
		 *
		 * @return bool True if the screen ID is a LearnDash page, false otherwise.
		 */
		return apply_filters(
			'learndash_location_is_learndash_admin_page',
			$return,
			$screen_id
		);
	}

	/**
	 * Determines if the current screen is the WordPress plugins page.
	 *
	 * @since 4.25.5
	 *
	 * @param string|null $screen_id The screen ID to check. If null, the current screen ID will be used.
	 *
	 * @return bool True if the current screen is the WordPress plugins page, false otherwise.
	 */
	public static function is_plugins_page( $screen_id = null ): bool {
		if (
			empty( $screen_id )
			&& is_admin()
			&& function_exists( 'get_current_screen' )
		) {
			$screen = get_current_screen();

			if ( ! empty( $screen->id ) ) {
				$screen_id = $screen->id;
			}
		}

		$return = false;

		if (
			! empty( $screen_id )
			&& $screen_id === 'plugins'
		) {
			$return = true;
		}

		/**
		 * Filters whether the screen ID is the WordPress plugins page.
		 *
		 * @since 4.25.5
		 *
		 * @param bool        $return True if the screen ID is a WordPress plugins page, false otherwise.
		 * @param string|null $screen The screen ID.
		 *
		 * @return bool True if the screen ID is a WordPress plugins page, false otherwise.
		 */
		return apply_filters(
			'learndash_location_is_plugins_page',
			$return,
			$screen_id
		);
	}

	/**
	 * Determines if the current screen is the WordPress Dashboard -> Updates page.
	 *
	 * @since 4.25.5
	 *
	 * @param string|null $screen_id The screen ID to check. If null, the current screen ID will be used.
	 *
	 * @return bool True if the current screen is the WordPress Dashboard -> Updates page, false otherwise.
	 */
	public static function is_updates_page( $screen_id = null ): bool {
		if (
			empty( $screen_id )
			&& is_admin()
			&& function_exists( 'get_current_screen' )
		) {
			$screen = get_current_screen();

			if ( ! empty( $screen->id ) ) {
				$screen_id = $screen->id;
			}
		}

		$return = false;

		if (
			! empty( $screen_id )
			&& $screen_id === 'update-core'
		) {
			$return = true;
		}

		/**
		 * Filters whether the screen ID is the WordPress Dashboard -> Updates page.
		 *
		 * @since 4.25.5
		 *
		 * @param bool        $return    True if the screen ID is the WordPress Dashboard -> Updates page, false otherwise.
		 * @param string|null $screen_id The screen ID.
		 *
		 * @return bool True if the screen ID is the WordPress Dashboard -> Updates page, false otherwise.
		 */
		return apply_filters(
			'learndash_location_is_updates_page',
			$return,
			$screen_id
		);
	}
}
