<?php
/**
 * LearnDash Harbor Provider class.
 *
 * @since 5.1.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore LDRP LDGR
 */

namespace LearnDash\Core\Libraries\Harbor;

use LearnDash\Core\App;
use LearnDash\Core\Licensing\Status_Checker;
use StellarWP\Learndash\LiquidWeb\Harbor\Harbor as LiquidWebHarbor;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\LiquidWeb\Harbor\Config;

/**
 * Service provider class for initializing the Harbor library.
 *
 * @since 5.1.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 5.1.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_actions();
	}

	/**
	 * Register actions.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	public function register_actions(): void {
		add_action( 'plugins_loaded', [ $this, 'configure' ], 1 ); // Run after LD is loaded.

		// LearnDash LMS is itself a premium plugin, so always declare a premium plugin exists.
		add_filter( 'lw_harbor/premium_plugin_exists', '__return_true' );

		// Register the LearnDash LMS legacy keys.
		add_filter( 'lw-harbor/legacy_licenses', [ $this,'register_legacy_licenses' ] );

		// Register legacy licenses for installed premium addons.
		add_filter( 'lw-harbor/legacy_licenses', [ $this->build_addon_legacy_licenses(), 'register' ] );
	}

	/**
	 * Configure and initialize the Harbor library.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	public function configure(): void {
		Config::set_plugin_basename( LEARNDASH_LMS_PLUGIN_KEY );
		Config::set_container( App::container() );
		LiquidWebHarbor::init();

		// Register the Licensing submenu under the LearnDash LMS top-level menu.
		lw_harbor_register_submenu( 'learndash-lms' );
	}

	/**
	 * Registers the legacy licenses.
	 *
	 * @since 5.1.0
	 *
	 * @param array<int,array<string,mixed>> $licenses The legacy licenses.
	 *
	 * @return array<int,array<string,mixed>> The legacy licenses.
	 */
	public function register_legacy_licenses( array $licenses ): array {
		$license_key = get_option( LEARNDASH_LICENSE_KEY );

		if ( empty( $license_key ) ) {
			return $licenses;
		}

		$status_data = Status_Checker::get_status( Status_Checker::$licensing_slug_learndash_core );

		$is_active  = isset( $status_data['status'] ) && Status_Checker::does_status_allow_access( $status_data['status'] );
		$expires_at = $status_data['expiry'] ?? '';

		$plugin_slugs = array_merge(
			[ 'sfwd-lms' ],
			$this->get_free_plugins()
		);

		// The 'name' is intentionally omitted; Harbor resolves the human-readable name from the slug.
		foreach ( $plugin_slugs as $slug ) {
			$licenses[] = [
				'key'             => $license_key,
				'slug'            => $slug,
				'product'         => 'learndash',
				'use_for_updates' => true,
				'is_active'       => $is_active,
				'page_url'        => admin_url( 'admin.php?page=learndash_hub_licensing' ),
				'expires_at'      => $expires_at,
			];
		}

		return $licenses;
	}

	/**
	 * Returns the slugs of free LearnDash plugins that share the LearnDash LMS license.
	 *
	 * Harbor resolves the human-readable name from each slug.
	 *
	 * @since 5.1.3
	 *
	 * @return array<string>
	 */
	private function get_free_plugins(): array {
		return [
			'learndash-integrity',
			'learndash-certificate-builder',
			'learndash-notifications',
			'learndash-achievements',
			'learndash-migration',
			'learndash-elementor',
			'learndash-woocommerce',
			'ld-tec',
			'learndash-zapier',
			'learndash-paidmemberships',
			'learndash-memberpress',
			'learndash-gravity-forms',
			'learndash-bbpress',
			'learndash-thrivecart',
			'learndash-samcart',
			'ld-multilingual',
			'learndash-restrict-content-pro',
		];
	}

	/**
	 * Builds the Addon_Legacy_Licenses instance with the full addon map.
	 *
	 * Each entry maps an ABSPATH constant (signals the plugin is installed) to:
	 *   - harbor_slug:       the slug used in the lw-harbor/legacy_licenses filter
	 *   - status_slug:       the slug used for Status_Checker::get_status()
	 *   - subscription_slug: the product_slug returned by Status_Checker::get_legacy_license_keys()
	 *
	 * The human-readable plugin name is intentionally omitted; Harbor resolves it from the slug.
	 *
	 * @since 5.1.0
	 *
	 * @return Addon_Legacy_Licenses
	 */
	private function build_addon_legacy_licenses(): Addon_Legacy_Licenses {
		return new Addon_Legacy_Licenses(
			[
				'LEARNDASH_GRADEBOOK_DIR'   => [
					'harbor_slug'       => 'learndash-gradebook',
					'status_slug'       => Status_Checker::$licensing_slug_gradebook,
					'subscription_slug' => 'learndash-gradebook',
				],
				'LEARNDASH_GROUPS_PLUS_DIR' => [
					'harbor_slug'       => 'learndash-groups-plus',
					'status_slug'       => Status_Checker::$licensing_slug_groups_plus,
					'subscription_slug' => 'groups-management',
				],
				'INSTRUCTOR_ROLE_ABSPATH'   => [
					'harbor_slug'       => 'instructor-role',
					'status_slug'       => Status_Checker::$licensing_slug_instructor_role,
					'subscription_slug' => 'instructor-role',
				],
				'LEARNDASH_NOTES_DIR'       => [
					'harbor_slug'       => 'learndash-notes',
					'status_slug'       => Status_Checker::$licensing_slug_notes,
					'subscription_slug' => 'learndash-notes',
				],
				'LDRP_PLUGIN_DIR'           => [
					'harbor_slug'       => 'learndash-propanel',
					'status_slug'       => Status_Checker::$licensing_slug_learndash_propanel_addon,
					'subscription_slug' => 'learndash-propanel',
				],
				'RRF_PLUGIN_PATH'           => [
					'harbor_slug'       => 'wdm-course-review',
					'status_slug'       => Status_Checker::$licensing_slug_reviews_plus,
					'subscription_slug' => 'wdm-ld-rating-review-and-feedback',
				],
				'WDM_LDGR_PLUGIN_DIR'       => [
					'harbor_slug'       => 'ld-group-registration',
					'status_slug'       => Status_Checker::$licensing_slug_group_registration,
					'subscription_slug' => 'groups-management',
				],
			]
		);
	}
}
