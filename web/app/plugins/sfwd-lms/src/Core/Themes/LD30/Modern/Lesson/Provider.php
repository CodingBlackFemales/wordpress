<?php
/**
 * Provider for LD30 Modern Lesson Page.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Lesson;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Themes\LD30\Modern\Settings;
use StellarWP\Learndash\lucatume\DI52\Container;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.24.0
 */
class Provider extends ServiceProvider {
	/**
	 * Settings instance.
	 *
	 * @since 4.24.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Provider constructor.
	 *
	 * @since 4.24.0
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
	 * @since 4.24.0
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
	 * @since 4.24.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Lesson template context.

		add_filter(
			'learndash_template_view_context',
			$this->container->callback(
				Template::class,
				'add_additional_context'
			),
			10,
			5
		);

		// Mark complete/Incomplete button.

		add_filter(
			'learndash_mark_complete_form_atts',
			$this->container->callback(
				Template::class,
				'add_mark_complete_button_attributes'
			),
			10,
			2
		);

		add_filter(
			'learndash_mark_incomplete_form_atts',
			$this->container->callback(
				Template::class,
				'add_mark_incomplete_button_attributes'
			),
			10,
			2
		);

		add_filter(
			'learndash_mark_complete_input_button_html',
			$this->container->callback(
				Template::class,
				'add_mark_complete_button_icon'
			),
			10,
			6
		);

		add_filter(
			'learndash_mark_complete_timer_html',
			$this->container->callback(
				Template::class,
				'add_mark_complete_timer_html'
			),
			10,
			2
		);
	}

	/**
	 * Controls whether the LD30 Modern Lesson Page functionality should be ran.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$settings = $this->settings->get();

		return $settings['course_enabled'];
	}
}
