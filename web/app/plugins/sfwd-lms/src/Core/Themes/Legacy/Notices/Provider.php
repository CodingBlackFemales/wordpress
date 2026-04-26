<?php
/**
 * Provider for the Legacy Theme Notices.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\Legacy\Notices;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.21.4
 */
class Provider extends ServiceProvider {
	/**
	 * The key of the option that LearnDash stores its Theme settings in.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const THEME_OPTION_KEY = 'learndash_settings_courses_themes';

	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_settings_section_save_fields_' . self::THEME_OPTION_KEY,
			$this->container->callback(
				Support_End::class,
				'clear_dismissal'
			),
			10,
			4
		);

		add_action(
			'admin_init',
			$this->container->callback(
				Support_End::class,
				'register_admin_notices'
			)
		);
	}
}
