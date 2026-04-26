<?php
/**
 * Provider for LD30 Modern Course Page.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Course;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Themes\LD30\Modern\Settings;
use StellarWP\Learndash\lucatume\DI52\Container;

/**
 * Class Provider for initializing theme implementations and hooks.
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

		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Course template context.

		add_filter(
			'learndash_template_view_context',
			$this->container->callback(
				Template::class,
				'add_additional_context'
			),
			10,
			5
		);

		// Change the payment button label on the course page.

		add_filter(
			'learndash_payment_button_label_course',
			$this->container->callback(
				Template::class,
				'change_payment_button_label'
			),
			10,
			3
		);

		// Change payment button classes on the course page.

		add_filter(
			'learndash_payment_button_classes',
			$this->container->callback(
				Template::class,
				'change_payment_button_classes'
			),
		);

		// Change the free payment button on the course page.

		add_filter(
			'learndash_payment_button_free',
			$this->container->callback( Template::class, 'change_free_payment_button' ),
			10,
			2
		);
	}

	/**
	 * Controls whether the LD30 Modern Course Page functionality should be ran.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$settings = $this->settings->get();

		return $settings['course_enabled'];
	}
}
