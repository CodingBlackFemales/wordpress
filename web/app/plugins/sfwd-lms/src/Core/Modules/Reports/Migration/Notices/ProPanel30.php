<?php
/**
 * ProPanel 3.0 migration notices.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Migration\Notices;

use LearnDash\Core\Licensing;
use LearnDash\Core\Modules\AJAX\Notices\Dismisser;

/**
 * ProPanel 3.0 migration notices.
 *
 * @since 4.17.0
 */
class ProPanel30 {
	/**
	 * Notice for ProPanel 3.0 migration.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const NOTICE_PROPANEL30_MIGRATION = 'notice_propanel30_migration';

	/**
	 * Outputs the admin notice regarding ProPanel 3.0 migration.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function displays_admin_notice() {
		// Only show notices to admin users and on LD admin pages.
		if (
			! learndash_is_admin_user()
			|| ! learndash_should_load_admin_assets()
			) {
			return;
		}

		// Bail if the user has dismissed the notice.
		if ( Dismisser::is_dismissed( self::NOTICE_PROPANEL30_MIGRATION ) ) {
			return;
		}

		// Output the notice.

		printf(
			'<div class="notice notice-success is-dismissible %1$s" data-nonce="%2$s" data-id="%3$s"><p>%4$s</p></div>',
			esc_attr( Dismisser::$classname ),
			esc_attr( learndash_create_nonce( Dismisser::$action ) ),
			esc_attr( self::NOTICE_PROPANEL30_MIGRATION ),
			wp_kses(
				learndash_cloud_is_enabled()
					? $this->get_message_for_cloud_users()
					: $this->get_message_for_non_cloud_users(),
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			)
		);
	}


	/**
	 * Returns the message for cloud users.
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	private function get_message_for_cloud_users(): string {
		return sprintf(
			// translators: %1$s: HTML link start tag, %2$s: HTML link end tag.
			__(
				'LearnDash reports are getting revamped! Basic reports have been added to LearnDash Core and we\'ve upgraded ProPanel. %1$sLearn more.%2$s',
				'learndash'
			),
			'<a href="https://go.learndash.com/reportsmerge" target="_blank">',
			'</a>'
		);
	}

	/**
	 * Returns the message for non-cloud users.
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	private function get_message_for_non_cloud_users(): string {
		$propanel_license = Licensing\Status_Checker::get_status(
			Licensing\Status_Checker::$licensing_slug_learndash_propanel
		);

		$propanel_license_type = ! empty( $propanel_license ) && Licensing\Status_Checker::does_status_allow_access( $propanel_license['status'] )
			? $propanel_license['type']
			: '';
		$propanel_installed    = $this->does_legacy_propanel_exist();

		// ProPanel is installed and the license is current.

		if (
			$propanel_installed
			&& $propanel_license_type === 'current'
		) {
			return sprintf(
			// translators: %1$s: HTML link tag to the LearnDash documentation, %2$s: Closing HTML link tag.
				__(
					'LearnDash reports are getting revamped! Basic reports have been added to LearnDash Core and we\'ve upgraded ProPanel. %1$sLearn more.%2$s',
					'learndash'
				),
				'<a href="https://go.learndash.com/reportsmerge" target="_blank">',
				'</a>'
			);
		}

		// ProPanel is installed and the license is legacy.

		if (
			$propanel_installed
			&& $propanel_license_type === 'legacy'
		) {
			return sprintf(
				// translators: %1$s: HTML link tag to the admin dashboard, %2$s: Closing HTML link tag, %3$s: HTML link tag to the LearnDash documentation, %4$s: Closing HTML link tag.
				__(
					'ProPanel Legacy is now integrated into LearnDash Core! You can continue to view your reports in the %1$sAdmin Dashboard%2$s. For enhanced reporting features, consider upgrading to our new ProPanel 3.0 add-on. Please note, if you haven\'t upgraded yet, you won\'t see an update available for ProPanel 3.0. %3$sLearn more.%4$s',
					'learndash'
				),
				'<a href="' . esc_url( admin_url() ) . '">',
				'</a>',
				'<a href="https://go.learndash.com/reportsmerge" target="_blank">',
				'</a>'
			);
		}

		// ProPanel is not installed or the license is not set.

		return sprintf(
			// translators: %1$s: HTML link tag to the admin dashboard, %2$s: Closing HTML link tag, %3$s: HTML link tag to the LearnDash documentation, %4$s: Closing HTML link tag.
			__(
				'We\'ve added basic reporting to LearnDash! View your reports in your %1$sAdmin Dashboard%2$s. %3$sLearn more.%4$s',
				'learndash'
			),
			'<a href="' . esc_url( admin_url() ) . '">',
			'</a>',
			'<a href="https://go.learndash.com/reportsmerge" target="_blank">',
			'</a>'
		);
	}

	/**
	 * Returns whether the legacy ProPanel plugin exists in the WP_PLUGINS directory.
	 *
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	private function does_legacy_propanel_exist(): bool {
		// Find the ProPanel plugin file in any directory in the WP_PLUGINS directory.
		$propanel_file = glob( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'learndash_propanel.php' );

		if ( empty( $propanel_file ) ) {
			return false;
		}

		// Check the plugin version to ignore newer versions.

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/**
		 * Loop through the files and check the ProPanel version.
		 * We may have more than one installation of ProPanel if it was installed in a custom directory (deactivated)
		 * and the final LearnDash LMS - Reports update was ran and installed ProPanel in the default directory.
		 */

		foreach ( $propanel_file as $file ) {
			$propanel_data = get_plugin_data( $file );

			if ( version_compare( $propanel_data['Version'], '3.0.0-dev', '>=' ) ) {
				return false; // It's the new ProPanel version.
			}
		}

		// All ProPanel installations are legacy.

		return true;
	}
}
