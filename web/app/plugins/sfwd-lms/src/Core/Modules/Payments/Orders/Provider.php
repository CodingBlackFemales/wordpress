<?php
/**
 * LearnDash Payments Orders Provider class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Orders;

use LearnDash\Core\Modules\Payments\Orders\Admin\Actions\Delete;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Orders.
 *
 * @since 4.19.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.19.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Admin\Provider::class );

		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.19.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'transition_post_status',
			$this->container->callback(
				Delete::class,
				'send_to_trash'
			),
			10,
			3
		);

		add_action(
			'before_delete_post',
			$this->container->callback(
				Delete::class,
				'permanently_delete'
			)
		);

		add_action(
			'transition_post_status',
			$this->container->callback(
				Delete::class,
				'restore_from_trash'
			),
			10,
			3
		);
	}
}
