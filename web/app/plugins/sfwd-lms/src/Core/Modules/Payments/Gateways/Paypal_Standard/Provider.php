<?php
/**
 * LearnDash PayPal Standard Provider class.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for PayPal Standard.
 *
 * @since 4.25.3
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Migration\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.3
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Register admin assets.
		add_action(
			'current_screen',
			$this->container->callback(
				Assets::class,
				'register_admin_assets'
			)
		);

		// Enqueue admin assets.
		add_action(
			'admin_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_admin_assets'
			)
		);

		// Register public assets.
		add_action(
			'wp_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'register_public_assets'
			)
		);

		add_action(
			'learndash_paypal_standard_migration_shortcode_after',
			$this->container->callback(
				Assets::class,
				'enqueue_public_assets'
			)
		);

		// Register REST endpoints.
		add_filter(
			'learndash_rest_endpoints',
			function ( $endpoints ) {
				return array_merge(
					$endpoints,
					[
						Endpoints\Migration\Setup_Token::class,
						Endpoints\Migration\Payment_Token::class,
					]
				);
			}
		);
	}
}
