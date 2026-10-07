<?php
/**
 * Bundled LearnDash LMS - Notifications add-on class.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore shcedule
 */

namespace LearnDash\Core\Notifications;

use LearnDash\Core\Libraries\Plugin_Absorber\Autoloaded_Sub_Plugin;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Absorber;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;

/**
 * Registers the bundled Notifications add-on with the Plugin Absorber.
 *
 * No `dependency_check`: the add-on's only dependency was LearnDash itself, which is the plugin it now ships inside.
 *
 * No `activation_callback` either. The Absorber runs one once ever, per site option, and the add-on's activation only
 * schedules cron events, which its deactivation clears whenever LearnDash is deactivated. A once-ever callback would
 * not put them back on the next activation, so `Provider` puts {@see self::activate()} and {@see self::deactivate()}
 * on LearnDash's own actions instead. A module whose setup has to run before `init`, or that creates something a
 * deactivation leaves in place, is the one that wants the key.
 *
 * @since 5.2.0
 */
class Sub_Plugin extends Autoloaded_Sub_Plugin {
	/**
	 * Slug the Plugin Absorber knows the bundled add-on by.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const SLUG = 'learndash-notifications';

	/**
	 * Slug the Plugin Absorber knows the bundled add-on by.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Schedules the add-on's cron events when LearnDash is activated.
	 *
	 * Not only for a first install. {@see self::deactivate()} clears those events, and
	 * `LD_Notifications_Update::update_plugin_cron_shcedule()` reschedules only on a version bump, so without this they
	 * would stay cleared after a deactivation and reactivation at the same version.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function activate(): void {
		learndash_notifications_activate();
	}

	/**
	 * Clears the add-on's scheduled cron events when LearnDash is deactivated.
	 *
	 * A bundled module is never deactivated on its own, so LearnDash's deactivation is the only one it has. Left alone,
	 * the events stay scheduled with none of the add-on's callbacks loaded to answer them.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function deactivate(): void {
		learndash_notifications_deactivate();
	}

	/**
	 * Catches the add-on up on anything an activation would have set up.
	 *
	 * Runs on every load of the bundled copy, because the scheduled work can go missing with no activation to put it
	 * back: a standalone copy clears it when deactivated. Keyed off the state, so the order does not matter, and
	 * guarded so an ordinary load does nothing.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function loaded(): void {
		learndash_notifications_ensure_scheduled();
	}

	/**
	 * PSR-4 prefixes the bundled copy ships.
	 *
	 * The add-on's own `composer.json` mapped this prefix at this directory. It is registered from the Absorber's
	 * load announcement rather than in this plugin's `composer.json`, so an active standalone copy of the add-on is
	 * never served these classes in place of its own.
	 *
	 * @since 5.2.0
	 *
	 * @return array<string,string>
	 */
	protected function get_psr4_prefixes(): array {
		return [
			'LearnDash\\Notifications\\' => LEARNDASH_LMS_PLUGIN_DIR . 'includes/notifications/src/Notifications',
		];
	}

	/**
	 * Registers the add-on with the Plugin Absorber.
	 *
	 * @since 5.2.0
	 *
	 * @throws Config_Exception If the sub-plugin configuration is unusable.
	 *
	 * @return void
	 */
	protected function register(): void {
		Absorber::register(
			[
				'slug'                       => self::SLUG,
				'bundled_plugin_file'        => LEARNDASH_LMS_PLUGIN_DIR . 'includes/notifications/learndash-notifications.php',
				'plugin_loaded_constant'     => 'LEARNDASH_NOTIFICATIONS_PLUGIN_PATH',
				'standalone_plugin_basename' => 'learndash-notifications/learndash-notifications.php',
				'enabled'                    => function (): bool {
					return $this->should_load();
				},
				'conflict_notice_message'    => function (): string {
					return $this->get_conflict_notice_message();
				},
			]
		);
	}

	/**
	 * Message shown when a standalone copy of the add-on is found.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	private function get_conflict_notice_message(): string {
		return sprintf(
			// translators: %1$s - strong opening tag, %2$s - strong closing tag, %3$s - paragraph opening tag, %4$s - line break tag, %5$s - paragraph closing tag.
			__(
				'%1$sNotifications are now part of LearnDash LMS.%2$s %3$sThe notification features have been added directly into LearnDash, with no separate plugin needed.%4$sIf the LearnDash LMS - Notifications add-on was previously installed, it\'s no longer required and can be safely uninstalled.%5$s',
				'learndash'
			),
			'<strong>',
			'</strong>',
			'<p>',
			'<br>',
			'</p>'
		);
	}
}
