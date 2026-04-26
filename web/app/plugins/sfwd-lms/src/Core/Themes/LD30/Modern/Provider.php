<?php
/**
 * Provider for LD30 Modern Variations.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
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
		$this->register_configuration_hooks();

		if ( ! $this->should_load() ) {
			return;
		}

		$this->container->singleton( Features::class );
		$this->container->register( Group\Provider::class );
		$this->container->register( Course\Provider::class );
		$this->container->register( Lesson\Provider::class );
		$this->container->register( Topic\Provider::class );
		$this->container->register( Ajax\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks for configuration.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	private function register_configuration_hooks(): void {
		// Hydrate for new install.
		add_action(
			'learndash_initialization_new_install',
			$this->container->callback( Features::class, 'action_set_new_install_appearance' )
		);

		// Migrate for an existing data set.
		add_action(
			'learndash_version_upgraded',
			$this->container->callback( Features::class, 'migrate_updated_appearance_field' )
		);
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		add_filter(
			'learndash_theme_supports_views',
			$this->container->callback(
				Features::class,
				'enable_view_support'
			),
			10,
			3
		);

		add_filter(
			'learndash_remove_template_content_filter',
			$this->container->callback(
				Features::class,
				'remove_template_content_filter'
			)
		);

		add_filter(
			'learndash_template_filename',
			$this->container->callback(
				Features::class,
				'load_modern_templates'
			),
			20, // learndash_30_template_filename() is filtered at 10.
			6
		);

		add_filter(
			'learndash_wrapper_class',
			$this->container->callback(
				Features::class,
				'update_wrapper_class'
			),
			10,
			2
		);

		add_action(
			'init',
			$this->container->callback(
				Assets::class,
				'register_scripts'
			)
		);
	}

	/**
	 * Controls whether the LD30 Modern functionality should be ran.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$settings = $this->settings->get();

		return $settings['course_enabled'] || $settings['group_enabled'];
	}
}
