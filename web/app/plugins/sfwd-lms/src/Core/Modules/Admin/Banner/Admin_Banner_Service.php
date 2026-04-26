<?php
/**
 * Admin Banner Service.
 *
 * @package LearnDash\Core
 *
 * @since 4.25.4
 */

namespace LearnDash\Core\Modules\Admin\Banner;

use LearnDash\Core\Modules\Admin\Banner\Contracts\Banner;

/**
 * Admin Banner Service class.
 *
 * Handles registering individual banners and ensures hooks are not called multiple times.
 *
 * @since 4.25.4
 */
class Admin_Banner_Service {
	/**
	 * Array of registered banners.
	 *
	 * @since 4.25.4
	 *
	 * @var array<string,string>
	 */
	private array $registered_banners = [];

	/**
	 * Static flag to track if banners have been registered to prevent duplicate hook calls.
	 *
	 * @since 4.25.4
	 *
	 * @var bool
	 */
	private static bool $banners_registered = false;

	/**
	 * Registers a banner.
	 *
	 * @since 4.25.4
	 *
	 * @param string $banner_class The banner class name to register.
	 *
	 * @return void
	 */
	public function register_banner( string $banner_class ): void {
		$this->registered_banners[ $banner_class ] = $banner_class;
	}

	/**
	 * Gets the registered banners.
	 *
	 * @since 4.25.4
	 *
	 * @return array<string,string>
	 */
	public function get_registered_banners(): array {
		return $this->registered_banners;
	}

	/**
	 * Initializes all registered banners with WordPress hooks.
	 *
	 * @since 4.25.4
	 *
	 * @return void
	 */
	public function initialize_banners(): void {
		// Check if banners are already registered to prevent duplicate hook calls.
		if ( self::$banners_registered ) {
			return;
		}

		/**
		 * Filters the list of registered banner classes before initialization.
		 *
		 * @since 4.25.4
		 *
		 * @param array<string,string> $registered_banners Array of registered banner class names.
		 *
		 * @return array<string,string> List of banner class names.
		 */
		$banner_classes = apply_filters(
			'learndash_admin_banners',
			$this->registered_banners
		);

		foreach ( $banner_classes as $banner_class ) {
			if ( ! class_exists( $banner_class ) ) {
				continue;
			}

			$banner = new $banner_class();

			if ( ! $banner instanceof Banner ) {
				continue;
			}

			$banner_id = $banner->get_banner_id();

			if ( empty( $banner_id ) ) {
				continue;
			}

			$banner->register();
		}

		// Mark banners as registered.
		self::$banners_registered = true;
	}
}
