<?php
/**
 * 5.0 Update Banner.
 *
 * @package LearnDash\Core
 *
 * @since 4.25.5
 */

namespace LearnDash\Core\Modules\Admin\Banner\Banners;

use LearnDash\Core\Modules\Admin\Banner\Contracts\Banner;
use LearnDash\Core\Utilities\Location;
use LearnDash\Hub\Component\API;
use LearnDash\Hub\Component\Projects;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;

/**
 * 5.0 Update Banner class.
 *
 * Displays a banner with information for the 5.0 update.
 *
 * @since 4.25.5
 */
class V5_0_Update_Banner implements Banner {
	/**
	 * Banner ID.
	 *
	 * @since 4.25.5
	 *
	 * @var string
	 */
	private const BANNER_ID = 'learndash-5-0-update';

	/**
	 * The version of the 5.0 update.
	 *
	 * @since 4.25.5
	 *
	 * @var string
	 */
	private const VERSION = '5.0.0-dev';

	/**
	 * Gets the banner ID.
	 *
	 * @since 4.25.5
	 *
	 * @return string
	 */
	public function get_banner_id(): string {
		return self::BANNER_ID;
	}

	/**
	 * Registers the banner with WordPress hooks.
	 *
	 * @since 4.25.5
	 *
	 * @return AdminNotice
	 */
	public function register(): AdminNotice {
		// Display the banner using StellarWP Admin Notices.
		return AdminNotices::show(
			$this->get_banner_id(),
			sprintf(
				// translators: placeholder: %1$s: Opening anchor tag, %2$s: Closing anchor tag.
				__( 'LearnDash 5.0 is a major update. Prepare your site first to ensure a smooth, error-free upgrade. %1$sGet the checklist.%2$s', 'learndash' ),
				'<a href="https://go.learndash.com/5update" target="_blank">',
				'</a>'
			)
		)
			->when( fn() => $this->should_show() )
			->asWarning()
			->autoParagraph();
	}

	/**
	 * Determines if the banner should be shown.
	 *
	 * @since 4.25.5
	 *
	 * @return bool
	 */
	private function should_show(): bool {
		if ( ! $this->is_valid_location() ) {
			return false;
		}

		// If we've already installed 5.0.0-dev or higher, bail.
		if ( $this->is_plugin_at_version() ) {
			return false;
		}

		// Check if the available update version is >= 5.0.0-dev.
		return $this->is_valid_update_version();
	}

	/**
	 * Determines if the current location is valid for the banner.
	 *
	 * @since 4.25.5
	 *
	 * @return bool True if the current location is valid for the banner, false otherwise.
	 */
	private function is_valid_location(): bool {
		return Location::is_learndash_admin_page()
			|| Location::is_plugins_page()
			|| Location::is_updates_page();
	}

	/**
	 * Determines if the plugin is already at or exceeds the version specified for the notice.
	 *
	 * @since 4.25.5
	 *
	 * @return bool
	 */
	private function is_plugin_at_version(): bool {
		return $this->is_required_version( constant( 'LEARNDASH_VERSION' ) );
	}

	/**
	 * Determines if the version of the available update matches or exceeds the version specified for the notice.
	 *
	 * @since 4.25.5
	 *
	 * @return bool True if the given update version matches or exceeds the version specified for the notice, false otherwise.
	 */
	private function is_valid_update_version(): bool {
		$api           = new API();
		$projects_data = $api->get_projects();

		// If we get a WP_Error or empty data, bail.
		if (
			is_wp_error( $projects_data )
			|| empty( $projects_data )
		) {
			return false;
		}

		// Get plugin data of all installed projects.
		$projects           = new Projects();
		$installed_projects = $projects->get_installed_projects( $projects_data );

		$project_key = trim(
			str_replace(
				constant( 'WP_PLUGIN_DIR' ),
				'',
				constant( 'LEARNDASH_LMS_PLUGIN_DIR' )
			),
			'/'
		);

		// If we cannot find data for the LearnDash project, bail.
		if ( ! isset( $installed_projects[ $project_key ] ) ) {
			return false;
		}

		$learndash_project = $installed_projects[ $project_key ];

		// Check if there's an update available.
		if (
			! isset( $learndash_project['has_update'] )
			|| ! $learndash_project['has_update']
			|| ! isset( $learndash_project['latest_version'] )
		) {
			return false;
		}

		return $this->is_required_version( $learndash_project['latest_version'] );
	}

	/**
	 * Determines if the given version matches or exceeds the version specified for the notice.
	 *
	 * @since 4.25.5
	 *
	 * @param string $version The version to check.
	 *
	 * @return bool True if the given version matches or exceeds the version specified for the notice, false otherwise.
	 */
	private function is_required_version( string $version ): bool {
		return version_compare(
			learndash_sanitize_version_string( $version ),
			learndash_sanitize_version_string( self::VERSION ),
			'>='
		);
	}
}
