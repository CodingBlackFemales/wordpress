<?php
/**
 * Provider for initializing the REST API subsystem.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1;

use LearnDash\Core\Modules\REST\V1\Manifest\Manifest_Generator;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider for initializing the REST API subsystem.
 *
 * @since 4.25.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_manifest();
		$this->hooks();
	}

	/**
	 * Register manifest related container definitions.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function register_manifest(): void {
		$this->container->singleton( Manifest_Generator::class );

		$this->container->when( Manifest_Generator::class )
						->needs( '$spec' )
						->give( static fn(): array => OpenAPI::get_base_spec() );
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action(
			'rest_api_init',
			$this->container->callback( Controller::class, 'register_routes' )
		);
	}
}
