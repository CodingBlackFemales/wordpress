<?php
/**
 * Legacy licensing assets loader.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Legacy;

use LearnDash\Hub\Component\Projects;
use LearnDash\Hub\Controller\Licensing_Settings;
use LearnDash\Hub\Controller\Licensing_Settings_Section;
use LearnDash\Hub\Controller\Projects_Controller;
use LearnDash\Hub\Controller\RemoteBanners;
use LearnDash_Settings_Page;
use LearnDash_Settings_Section;
use ReflectionException;
use ReflectionMethod;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use WP_Screen;

/**
 * Legacy licensing assets loader.
 *
 * @since 4.18.0
 */
class Assets {
	/**
	 * Asset Group to register our Assets to and enqueue from.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	public static string $group = 'learndash-licensing';

	/**
	 * Base directory to use for loading Licensing JS Assets from.
	 * This is relative to the plugin directory for LearnDash.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private const JS_ASSET_DIR = 'includes/licensing/assets/scripts';

	/**
	 * Base directory to use for loading Licensing CSS Assets from.
	 * This is relative to the plugin directory for LearnDash.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private const CSS_ASSET_DIR = 'includes/licensing/assets/css';

	/**
	 * Registers assets to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$this->register_scripts();
		$this->register_styles();
	}

	/**
	 * Enqueues assets registered to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}

	/**
	 * Register JS scripts to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	private function register_scripts(): void {
		$settings_page_instance       = LearnDash_Settings_Page::get_page_instance( 'LearnDash\Hub\Controller\Licensing_Settings' );
		$settings_section_instance    = LearnDash_Settings_Section::get_section_instance( 'LearnDash\Hub\Controller\Licensing_Settings_Section' );
		$projects_controller_instance = new Projects_Controller();

		// Passed in to the conditions of our Assets so we can call private methods.
		$that = $this;

		// Script used to display Remote Banners.
		Asset::add(
			'learndash-hub-remote-script',
			'remote.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::JS_ASSET_DIR, false )
		->add_to_group( self::$group )
		->set_dependencies( 'jquery' )
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->has_remote_banners();
			}
		)
		->add_localize_script(
			'ld_hub_remote',
			[
				'nonce' => wp_create_nonce( RemoteBanners::DISMISS_REMOTE_ACTION ),
			]
		)
		->register();

		$project_controller_make_data = [];

		// Avoid expensive operations if we're not on the Add-ons page.

		if ( $this->should_load_learndash_hub_projects_assets() ) {
			// Workaround to avoid a fatal error at the first time the page is loaded after the L&M plugin replacement.

			try {
				$reflection = new ReflectionMethod( $projects_controller_instance, 'make_data' );

				if ( $reflection->isPublic() ) {
					$project_controller_make_data = $projects_controller_instance->make_data();
				} else {
					$project_service = new Projects();

					$project_controller_make_data = [
						'last_check'        => $projects_controller_instance->format_date_time( $project_service->get_project_check_time() ),
						'projects'          => $project_service->get_projects( [] ),
						'installedProjects' => $project_service->get_installed_projects( [] ),
						'categories'        => $project_service->get_projects_category( [] ),
						'premiumProjects'   => $project_service->get_premium_projects( [] ),
						'affProjects'       => $project_service->get_aff_projects(),
						'nonces'            => array(
							'handle_plugin' => wp_create_nonce( 'ld_hub_plugin_handle' ),
							'refresh_repo'  => wp_create_nonce( 'ld_hub_refresh_repo' ),
							'bulk_action'   => wp_create_nonce( 'ld_hub_bulk_action' ),
						),
						'adminUrl'          => admin_url( 'admin.php?page=learndash-hub' ),
						'externalUrl'       => admin_url( 'plugin-install.php?s=learndash&tab=search&type=tag' ),
					];
				}
			} catch ( ReflectionException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Expected.
				// Do nothing.
			}
		}

		if (
			! $settings_page_instance instanceof Licensing_Settings
			|| ! $settings_section_instance instanceof Licensing_Settings_Section
		) {
			return;
		}

		// Intentionally not associated with the Group as it is a dependency.
		Asset::add(
			'learndash-hub-select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
			'4.1.0-rc.0'
		)
		->add_dependency( 'jquery' )
		->register();

		// Script used on LearnDash LMS -> Settings -> LMS License.
		Asset::add(
			'learndash-hub-licensing',
			'licensing.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::JS_ASSET_DIR, false )
		->add_to_group( self::$group )
		->set_dependencies(
			'react',
			'react-dom',
			'wp-i18n'
		)
		/**
		 * If we do not set an Action that matches the Action we're enqueue-ing the whole Group at,
		 * then it will be forcibly enqueue'd regardless of the result of our condition.
		 */
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->is_lms_license_page()
					&& $that->can_view_licensing();
			}
		)
		->add_localize_script(
			'Hub',
			[
				'nonces'      => [
					'sign_out' => wp_create_nonce( 'ld_hub_sign_out' ),
				],
				'rootUrl'     => admin_url( '/admin.php?page=learndash_hub_licensing' ),
				'email'       => $settings_page_instance->get_hub_email(),
				'license_key' => $settings_page_instance->get_license_key(),
			]
		)
		->register();

		// Script used on LearnDash LMS -> Settings -> Advanced -> License Visibility.
		Asset::add(
			'learndash-hub-settings',
			'settings.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::JS_ASSET_DIR, false )
		->add_to_group( self::$group )
		->set_dependencies(
			'react',
			'react-dom',
			'wp-i18n',
			'learndash-hub-select2'
		)
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->is_license_visibility_page()
					&& $that->is_licensed()
					&& $that->can_view_licensing();
			}
		)
		->add_localize_script(
			'Hub',
			$settings_section_instance->make_data()
		)
		->register();

		// Script used on LearnDash LMS -> Add-ons.
		Asset::add(
			'learndash-hub-projects',
			'projects.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::JS_ASSET_DIR, false )
		->add_to_group( self::$group )
		->set_dependencies(
			'react',
			'react-dom',
			'wp-i18n'
		)
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->should_load_learndash_hub_projects_assets();
			}
		)
		->add_localize_script(
			'Hub',
			$project_controller_make_data
		)
		->register();
	}

	/**
	 * Register CSS styles to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	private function register_styles(): void {
		// Passed in to the conditions of our Assets so we can call private methods.
		$that = $this;

		// Intentionally not associated with the Group as it is a dependency.
		Asset::add(
			'learndash-hub-select2-css',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
			'4.1.0-rc.0'
		)
		->register();

		// Intentionally not associated with the Group as it is a dependency.
		Asset::add(
			'learndash-hub-fontawesome',
			'fontawesome.css',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->add_style_data( 'rtl', true )
		->set_path( self::CSS_ASSET_DIR, false )
		->register();

		// CSS that is used for all Licensing pages.
		Asset::add(
			'learndash-hub',
			'app.css',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::CSS_ASSET_DIR, false )
		->add_style_data( 'rtl', true )
		->add_to_group( self::$group )
		->set_dependencies(
			'learndash-hub-select2-css',
			'learndash-hub-fontawesome'
		)
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->can_view_licensing()
					&& (
						$that->is_addons_page()
						|| $that->is_lms_license_page()
						|| $that->is_license_visibility_page()
					);
			}
		)
		->register();

		// CSS used for Remote Banners.
		Asset::add(
			'learndash-hub-remote-styles',
			'remote.css',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
		->set_path( self::CSS_ASSET_DIR, false )
		->add_style_data( 'rtl', true )
		->add_to_group( self::$group )
		->set_action(
			'admin_enqueue_scripts'
		)
		->set_condition(
			static function () use ( $that ) {
				return $that->has_remote_banners();
			}
		);
	}

	/**
	 * Returns whether we should load LearnDash Hub Projects assets or not.
	 *
	 * @since 4.18.0.1
	 *
	 * @return bool
	 */
	private function should_load_learndash_hub_projects_assets(): bool {
		return is_admin()
			&& $this->is_addons_page()
			&& $this->is_licensed()
			&& $this->can_view_licensing();
	}

	/**
	 * Returns if we're on the LearnDash LMS -> Add-ons page.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function is_addons_page(): bool {
		$current_screen = get_current_screen();

		return $current_screen instanceof WP_Screen
			&& $current_screen->id === 'learndash-lms_page_learndash-hub';
	}

	/**
	 * Returns if we're on the LearnDash LMS -> Settings -> LMS License page.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function is_lms_license_page(): bool {
		$current_screen = get_current_screen();

		return $current_screen instanceof WP_Screen
			&& $current_screen->id === 'admin_page_learndash_hub_licensing';
	}

	/**
	 * Returns if we're on the LearnDash LMS -> Settings -> Advanced -> License Visibility page.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function is_license_visibility_page(): bool {
		$current_screen = get_current_screen();
		$section        = SuperGlobals::get_get_var( 'section-advanced', '' );

		return $current_screen instanceof WP_Screen
			&& $current_screen->id === 'admin_page_learndash_lms_advanced'
			&& $section === 'setting_lms_licensing';
	}

	/**
	 * Returns if the user can view the licensing pages.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function can_view_licensing(): bool {
		$settings_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash\Hub\Controller\Licensing_Settings' );

		if ( ! $settings_page_instance instanceof Licensing_Settings ) {
			return false;
		}

		return $settings_page_instance->is_user_allowed();
	}

	/**
	 * Returns whether the currently entered LearnDash License is valid.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function is_licensed(): bool {
		$settings_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash\Hub\Controller\Licensing_Settings' );

		if ( ! $settings_page_instance instanceof Licensing_Settings ) {
			return false;
		}

		return $settings_page_instance->is_signed_on();
	}

	/**
	 * Returns if we have Remote Banners applicable to the current page or not.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function has_remote_banners(): bool {
		$remote_banners_instance = new RemoteBanners();

		// Workaround to avoid a fatal error at the first time the page is loaded after the L&M plugin replacement.

		try {
			$reflection = new ReflectionMethod( $remote_banners_instance, 'filter_displayable_banners' );
			if ( ! $reflection->isPublic() ) {
				return false;
			}

			// Call the function and return the correct data.

			return ! empty(
				$remote_banners_instance->filter_displayable_banners(
					$remote_banners_instance->get_banners()
				)
			);
		} catch ( ReflectionException $e ) {
			return false;
		}
	}
}
