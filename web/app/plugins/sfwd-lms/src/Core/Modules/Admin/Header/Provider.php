<?php
/**
 * LearnDash Admin Header Provider class.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin\Header;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Admin Header additions.
 *
 * @since 4.23.1
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.23.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Course::class );
		$this->container->singleton( Lesson::class );
		$this->container->singleton( Topic::class );
		$this->container->singleton( Quiz::class );
		$this->container->singleton( Question::class );
		$this->container->singleton( Certificate::class );
		$this->container->singleton( Group::class );
		$this->container->singleton( Exam::class );
		$this->container->singleton( Assignment::class );
		$this->container->singleton( Transaction::class );
		$this->container->singleton( Coupon::class );
		$this->container->singleton( Course_Wizard::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.23.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_header_buttons',
			$this->container->callback( Course_Wizard::class, 'add_header_buttons' ),
			1
		);
	}
}
