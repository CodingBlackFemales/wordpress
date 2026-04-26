<?php
/**
 * Admin Banner Provider.
 *
 * @package LearnDash\Core
 *
 * @since 4.25.4
 */

namespace LearnDash\Core\Modules\Admin\Banner;

use LearnDash\Core\Container;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Modules\Admin\Banner\Banners;
use LearnDash\Core\Modules\Admin\Banner\Contracts\Banner;

/**
 * Admin Banner Provider class.
 *
 * @since 4.25.4
 */
class Provider extends ServiceProvider {
	/**
	 * Registers services in the container.
	 *
	 * @since 4.25.4
	 *
	 * @return void
	 */
	public function register(): void {
		// Register the admin banner service.
		$this->container->singleton( Admin_Banner_Service::class, Admin_Banner_Service::class );
		$this->hooks();
	}

	/**
	 * Registers the banners. These will be added and removed as needed for promotional banners.
	 *
	 * @since 4.25.4
	 *
	 * @return void
	 */
	protected function register_banners(): void {
		/**
		 * The banner service singleton.
		 *
		 * @var Admin_Banner_Service $banner_service The admin banner service.
		 */
		$banner_service = $this->container->get( Admin_Banner_Service::class );

		// Register the promotional banner.
		$banner_service->register_banner( Banners\Black_Friday_Promotion_2025::class );
		$banner_service->register_banner( Banners\V5_0_Update_Banner::class );
	}

	/**
	 * Initializes WordPress hooks.
	 *
	 * @since 4.25.4
	 *
	 * @return void
	 */
	private function hooks(): void {
		/**
		 * The banner service singleton.
		 *
		 * @var Admin_Banner_Service $banner_service The admin banner service.
		 */
		$banner_service = $this->container->get( Admin_Banner_Service::class );

		// Register the banners.
		$this->register_banners();

		// Hook to initialize all banners.
		add_action(
			'admin_init',
			[ $banner_service, 'initialize_banners' ]
		);
	}
}
