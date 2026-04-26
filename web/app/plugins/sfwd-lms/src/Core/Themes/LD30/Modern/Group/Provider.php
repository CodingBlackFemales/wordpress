<?php
/**
 * Provider for LD30 Modern Group Page.
 *
 * @since 4.22.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Group;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Themes\LD30\Modern\Settings;
use StellarWP\Learndash\lucatume\DI52\Container;
use LearnDash\Core\Themes\LD30\Modern\Group\Settings as Group_Settings;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.22.0
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
	 * @since 4.22.0
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
	 * @since 4.22.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Add group template context.

		add_filter(
			'learndash_template_view_context',
			$this->container->callback(
				Template::class,
				'add_additional_context'
			),
			10,
			5
		);

		// Load Course Grid assets.

		add_filter(
			'learndash_course_grid_post_extra_course_grids',
			$this->container->callback(
				Template::class,
				'load_course_grid_assets'
			),
			10,
			2
		);

		// Remove the progress bar for unenrolled courses.

		add_filter(
			'learndash_course_grid_template_post_shortcode_attributes',
			$this->container->callback(
				Template::class,
				'remove_progress_bar_for_unenrolled_courses'
			),
			10,
			3
		);

		// Add a caret icon to the continue button on the group page.

		add_filter(
			'learndash_course_grid_template_post_attributes',
			$this->container->callback(
				Template::class,
				'add_icon_to_continue_button'
			),
			10,
			3
		);

		// Change the payment button label on the group page.

		add_filter(
			'learndash_payment_button_label_group',
			$this->container->callback(
				Template::class,
				'change_payment_button_label'
			),
			10,
			3
		);

		// Change payment button classes on the group page.

		add_filter(
			'learndash_payment_button_classes',
			$this->container->callback(
				Template::class,
				'change_payment_button_classes'
			),
		);

		// Change the free payment button on the group page.

		add_filter(
			'learndash_payment_button_free',
			$this->container->callback( Template::class, 'change_free_payment_button' ),
			10,
			2
		);

		// Remove the custom course pagination settings from the group display content settings metabox.

		add_filter(
			'learndash_settings_fields',
			$this->container->callback( Group_Settings::class, 'disable_custom_course_pagination' ),
			10,
			2
		);
	}

	/**
	 * Controls whether the LD30 Modern Group Page functionality should be ran.
	 *
	 * @since 4.22.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$settings = $this->settings->get();

		return $settings['group_enabled'];
	}
}
