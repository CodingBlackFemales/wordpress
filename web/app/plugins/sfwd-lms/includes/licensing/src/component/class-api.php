<?php
/**
 * LearnDash Hub API Component.
 *
 * Handles all API interactions between the WordPress site and LearnDash licensing server.
 * This includes license verification, project data retrieval and caching, and domain management.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Hub\Component
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Component;

use LearnDash\Hub\Framework\Base;
use LearnDash\Hub\Traits\Formats;
use LearnDash\Hub\Traits\License;
use LearnDash\Core\Validations;
use StellarWP\Learndash\StellarWP\Validation\Validator;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * This class handle all stuffs relate to API.
 */
class API extends Base {
	use License;
	use Formats;

	/**
	 * The option name for storing plugin slugs.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	const OPTION_NAME_PLUGIN_SLUGS = 'learndash-hub-projects-slugs';

	/**
	 * The option name for storing projects API data.
	 *
	 * @since 4.21.1
	 *
	 * @var string
	 */
	const OPTION_NAME_PROJECTS_API = 'learndash-hub-projects-api';

	/**
	 * The option name for storing fetched projects.
	 *
	 * @since 4.21.1
	 *
	 * @var string
	 */
	const OPTION_NAME_FETCH_PROJECTS = 'learndash_hub_fetch_projects';

	/**
	 * The option name for storing update plugins cache.
	 *
	 * @since 4.21.1
	 *
	 * @var string
	 */
	const OPTION_NAME_UPDATE_PLUGINS_CACHE = 'learndash_hub_update_plugins_cache';

	/**
	 * The API base URL.
	 *
	 * @var string
	 */
	public $base = LICENSING_SITE . '/wp-json/' . BASE_REST;

	/**
	 * The cache time for failed responses.
	 *
	 * @since 4.21.5
	 *
	 * @var int
	 */
	private static int $failed_response_cache_time = MINUTE_IN_SECONDS * 10;

	/**
	 * Trigger a license verification.
	 *
	 * @param string $email       The email that registered with LearnDash.
	 * @param string $license_key The license key provided when registered.
	 * @param bool   $force_check Force check the license status.
	 *
	 * @return WP_Error|bool
	 */
	public function verify_license( string $email, string $license_key, bool $force_check = false ) {
		if ( ! $force_check ) {
			$license_status = $this->get_license_status();

			if ( '' !== $license_status ) {
				return ! is_wp_error( $license_status ) ? true : $license_status;
			}
		}

		$response = $this->do_api_request(
			'/site/auth',
			'POST',
			array(
				'site_url'    => site_url(),
				'license_key' => $license_key,
				'email'       => $email,
				'stats'       => $this->build_site_stats(),
			)
		);

		/**
		 * Fires after the license verification.
		 *
		 * @since 4.18.0
		 *
		 * @param WP_Error|bool $license_response `WP_Error` on failure, `true` on success.
		 * @param string        $license_email    License email.
		 * @param string        $license_key      License key.
		 */
		do_action(
			'learndash_licensing_management_license_verified',
			! is_wp_error( $response ) ? true : $response,
			$email,
			$license_key
		);

		$this->update_license_status( $response, $email, $license_key );

		return ! is_wp_error( $response ) ? true : $response;
	}


	/**
	 * Query the site stats.
	 *
	 * @since 4.18.0
	 *
	 * @return array
	 */
	public function build_site_stats(): array {
		global $wp_version;

		return array(
			'versions' => array(
				'wp' => $wp_version,
			),
			'network'  => array(
				'multisite'         => (int) is_multisite(),
				'network_activated' => 0,
				'active_sites'      => $this->get_multisite_active_sites(),
			),
		);
	}

	/**
	 * Gets multi-site active site count.
	 *
	 * @since 4.18.0
	 *
	 * @return int
	 */
	public function get_multisite_active_sites(): int {
		global $wpdb;

		if ( ! is_multisite() ) {
			$active_sites = 1;
		} else {
			$active_sites = (int) $wpdb->get_var(
				"
					SELECT
						COUNT( `blog_id` )
					FROM
						`{$wpdb->blogs}`
					WHERE
						`public` = '1'
						AND `archived` = '0'
						AND `spam` = '0'
						AND `deleted` = '0'
				"
			);
		}

		return $active_sites;
	}


	/**
	 * Return all the projects, and cache it.
	 *
	 * @since 4.18.0
	 *
	 * @return array<string, mixed>|WP_Error Array of projects or WP_Error on failure.
	 */
	public function get_projects() {
		// Try to get valid cached data.
		$cached_projects = $this->get_cached_projects();

		// If this is error from the last cache, just return it.
		if ( is_wp_error( $cached_projects ) ) {
			return $cached_projects;
		}

		// If we have valid cached data, return it, even if it an empty array.
		if ( is_array( $cached_projects ) ) {
			return $this->filter_valid_projects( $cached_projects );
		}

		// No valid cache, fetch fresh data.
		return $this->fetch_and_cache_projects();
	}

	/**
	 * Gets cached projects if the cache is valid.
	 *
	 * @since 4.21.1
	 *
	 * @return array<string, mixed>|null|WP_Error Array of projects if cache is valid, WP_Error if an error occurred, or null if cache is invalid/expired.
	 */
	private function get_cached_projects() {
		$cached = $this->get_raw_projects_cache();

		// If no cache exists, return false.
		if (
			! isset( $cached['last_check'] )
			|| ! isset( $cached['expires_at'] )
		) {
			return null;
		}

		// Check if cache is still valid based on expiration time.
		$cache_valid = time() < $cached['expires_at'];

		// bail early.
		if ( ! $cache_valid ) {
			return null;
		}

		/**
		 * Projects data retrieved from the API.
		 *
		 * @var array<string,mixed>|WP_Error $projects
		 */
		$projects = $cached['projects'];

		return $projects;
	}

	/**
	 * Retrieves raw projects cache data.
	 *
	 * @since 4.21.1
	 *
	 * @return array<string, mixed> The raw cache data or false if not found.
	 */
	private function get_raw_projects_cache(): array {
		$result = get_site_option( self::OPTION_NAME_PROJECTS_API, [] );

		if ( ! is_array( $result ) ) {
			return [];
		}

		return $result;
	}

	/**
	 * Remove the domain from API side
	 *
	 * @since 4.18.0
	 *
	 * @return array|WP_Error
	 */
	public function remove_domain() {
		return $this->do_api_request(
			'/site/auth',
			'DELETE'
		);
	}

	/**
	 * Fetches fresh projects data from API and caches it.
	 *
	 * @since 4.21.1
	 *
	 * @return array<string, mixed>|WP_Error Array of projects or WP_Error on failure.
	 */
	private function fetch_and_cache_projects() {
		// Clear related caches.
		$this->clear_project_caches();

		// Fetch from API.
		$projects = $this->do_api_request( '/repo/plugins' );

		// Handle errors - cache errors.
		if ( is_wp_error( $projects ) ) {
			$this->cache_projects_data( $projects, $this->get_failed_response_cache_time() );

			return $projects;
		}

		// Ensure projects is an array and validate data.
		if ( ! is_array( $projects ) ) {
			return [];
		}

		// Validate project data.
		$projects = $this->filter_valid_projects( $projects );

		$cache_time = 12 * HOUR_IN_SECONDS;

		if ( empty( $projects ) ) {
			// If the filtered variable is empty, then cache it with a short period.
			$cache_time = $this->get_failed_response_cache_time();
		}

		// Cache the result for 12 hours.
		$this->cache_projects_data( $projects, $cache_time );

		// Update slugs option.
		$slugs = array_keys( $projects );

		update_site_option( self::OPTION_NAME_PLUGIN_SLUGS, $slugs );

		return $projects;
	}

	/**
	 * Gets the cache time for failed responses.
	 *
	 * @since 4.21.5
	 *
	 * @return int The cache time in seconds.
	 */
	private function get_failed_response_cache_time(): int {
		/**
		 * Filters the cache time for failed responses.
		 *
		 * @since 4.21.5
		 *
		 * @param int $cache_time The cache time in seconds.
		 *
		 * @return int The cache time in seconds.
		 */
		return apply_filters( 'learndash_module_licensing_failed_response_cache_time', self::$failed_response_cache_time );
	}

	/**
	 * Clears all project-related caches.
	 *
	 * @since 4.21.1
	 *
	 * @return void
	 */
	private function clear_project_caches(): void {
		delete_site_option( self::OPTION_NAME_FETCH_PROJECTS );
		delete_site_option( self::OPTION_NAME_UPDATE_PLUGINS_CACHE );
	}

	/**
	 * Caches projects data and updates related options.
	 *
	 * @since 4.21.1
	 *
	 * @param mixed $projects   Array of project data or WP_Error.
	 * @param int   $cache_time Time in seconds to cache the data.
	 *
	 * @return void
	 */
	private function cache_projects_data( $projects, int $cache_time = HOUR_IN_SECONDS * 12 ): void {
		// Update main cache.
		$cached = [
			'projects'   => $projects,
			'last_check' => time(),
			'expires_at' => time() + $cache_time,
		];

		update_site_option( self::OPTION_NAME_PROJECTS_API, $cached );
	}

	/**
	 * Filters projects to ensure all required fields are present and of correct type.
	 *
	 * @since 4.21.1
	 *
	 * @param array<string, mixed> $projects Array of project data.
	 *
	 * @return array<string, mixed> Filtered projects array with only valid items.
	 */
	private function filter_valid_projects( array $projects ): array {
		if ( empty( $projects ) ) {
			return [];
		}

		$filtered_projects = [];

		// URL validation.
		$url_validator = new Validations\Rules\Is_URL();

		// String validation.
		$string_validator = new Validations\Rules\Is_String();

		// Array validation.
		$array_validator = new Validations\Rules\Is_Array();

		foreach ( $projects as $key => $project ) {
			// Skip if project isn't an array.
			if ( ! is_array( $project ) ) {
				continue;
			}

			$validator = new Validator(
				[
					// Mandatory fields.
					'name'                  => [ 'required', $string_validator ],
					'latest_version'        => [ 'required', $string_validator ],
					'download_url'          => [ 'required', $string_validator, $url_validator ],
					'slug'                  => [ 'required', $string_validator ],

					// Version-related fields.
					'requires'              => [ 'optional', $string_validator ],
					'requires_php'          => [ 'optional', $string_validator ],
					'tested'                => [ 'optional', $string_validator ],

					// URIs, those can be empty.
					'plugin_uri'            => [ 'optional', $string_validator, $url_validator ],
					'author_uri'            => [ 'optional', $string_validator, $url_validator ],

					// Metadata fields.
					'project_id'            => [ 'optional', $string_validator ],
					'last_updated'          => [ 'optional', $string_validator ],
					'author'                => [ 'optional', $string_validator ],
					'requires_ld'           => [ 'optional', $string_validator ],
					'tags'                  => [ 'optional', $string_validator ],
					'product_type'          => [ 'optional', $string_validator ],
					'is_purchased'          => [ 'optional', 'boolean' ],
					'short_description'     => [ 'optional', $string_validator ],

					// Nested structures.
					'sections'              => [ 'optional', $array_validator ],
					'sections.description'  => [ 'optional', $string_validator ],
					'sections.installation' => [ 'optional', $string_validator ],
					'sections.changelog'    => [ 'optional', $string_validator ],

					'icons'                 => [ 'optional', $array_validator ],
					'icons.default'         => [ 'optional', $string_validator, $url_validator ],

				],
				$project
			);

			if ( $validator->passes() ) {
				$filtered_projects[ $key ] = $project;
			}
		}

		return $filtered_projects;
	}
}
