<?php
/**
 * Main provider class file.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash\Notifications;

use LearnDash\Notifications\Compat\WPML;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class the plugin.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( WPML::class );
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		$wpml = $this->container->make( WPML::class );
		if ( $wpml instanceof WPML ) {
			$wpml->listen();
		}

		add_filter(
			'learndash_loggers',
			function ( array $loggers ): array {
				$loggers[] = new Logger();

				return $loggers;
			}
		);
	}
}
