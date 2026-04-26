<?php
/**
 * LearnDash Plugin Requirements Checker
 *
 * This file contains the CheckPluginsRequirements class which checks the requirements
 * of LearnDash plugins before they are installed.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Hub\Controller
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Component\API;
use LearnDash\Hub\Component\Projects;
use LearnDash\Hub\Traits\License;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckPluginsRequirements
 *
 * This class checks the requirements of LearnDash plugins before they are installed.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Hub\Controller
 */
class CheckPluginsRequirements {
	use License;

	/**
	 * Register the necessary hooks.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'upgrader_source_selection', [ $this, 'is_ld_version_compatible' ], 20 );
	}

	/**
	 * Check if the LearnDash version is compatible with the plugin requirements.
	 *
	 * @param string|\WP_Error $source The source of the plugin being installed.
	 *
	 * @since 4.18.0
	 *
	 * @return string|\WP_Error The source of the plugin or a WP_Error if requirements are not met.
	 */
	public function is_ld_version_compatible( $source ) {
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$plugin_slug  = basename( $source );
		$plugin_slugs = get_option( API::OPTION_NAME_PLUGIN_SLUGS );

		if ( ! is_array( $plugin_slugs ) || ! in_array( $plugin_slug, $plugin_slugs, true ) ) {
			return $source;
		}

		$api            = new API();
		$project_helper = new Projects();

		$projects = $api->get_projects();

		// If an error occurred retrieving plugin data, allow plugin installation.
		if ( ! is_array( $projects ) ) {
			return $source;
		}

		$plugin_data = $project_helper->look_project( $plugin_slug, $projects );

		if ( ! is_array( $plugin_data ) ) {
			return $source;
		}

		$is_compatibility = is_learndash_version_compatible( $plugin_data, $this->get_learndash_core_version() );

		if ( is_wp_error( $is_compatibility ) ) {
			return $is_compatibility;
		}

		return $source;
	}
}
