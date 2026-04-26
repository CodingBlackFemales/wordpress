<?php
/**
 * Provider for LD30 Modern Ajax functionality.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Ajax;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Themes\LD30\Modern\Settings;
use StellarWP\Learndash\lucatume\DI52\Container;

/**
 * Class Provider for initializing LD30 Modern Ajax functionality.
 *
 * @since 4.21.0
 */
class Provider extends ServiceProvider {
	/**
	 * Settings instance.
	 *
	 * @since 4.22.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Provider constructor.
	 *
	 * @since 4.22.0
	 *
	 * @param Container $container The DI container instance.
	 * @param Settings  $settings  The settings instance.
	 */
	public function __construct( Container $container, Settings $settings ) {
		parent::__construct( $container );

		$this->settings = $settings;
	}

	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->should_load() ) {
			return;
		}

		$this->container->register( Pagination\Provider::class );
	}

	/**
	 * Controls whether the LD30 Modern Ajax functionality should be ran.
	 *
	 * @since 4.22.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$settings = $this->settings->get();

		return $settings['course_enabled'];
	}
}
