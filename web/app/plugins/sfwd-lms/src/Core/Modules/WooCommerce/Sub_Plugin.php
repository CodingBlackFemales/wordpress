<?php
/**
 * Bundled LearnDash LMS - WooCommerce Integration add-on class.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\WooCommerce;

use LearnDash\Core\Libraries\Plugin_Absorber\Autoloaded_Sub_Plugin;
use LearnDash\Core\Utilities\Dependency_Checker;
use Learndash_WooCommerce_Cron;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Absorber;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;

/**
 * Registers the bundled WooCommerce Integration add-on with the Plugin Absorber.
 *
 * Autoloading is arranged by {@see Autoloaded_Sub_Plugin}: the namespace is mapped only when the Absorber loads the
 * bundled copy.
 *
 * No `activation_callback` on the Absorber registration. The Absorber runs one once ever, per site option, and this
 * add-on's cron already reschedules on `admin_init`. Deactivation is the gap: a bundled module is never deactivated on
 * its own, so {@see self::deactivate()} is put on LearnDash's own action instead.
 *
 * @since 5.2.0
 */
class Sub_Plugin extends Autoloaded_Sub_Plugin {
	/**
	 * Minimum WooCommerce version the integration supports.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const WOOCOMMERCE_MIN_VERSION = '4.5.0';

	/**
	 * Slug the Plugin Absorber knows the bundled WooCommerce Integration by.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'learndash-woocommerce';
	}

	/**
	 * Clears the add-on's scheduled cron event when LearnDash is deactivated.
	 *
	 * A bundled module is never deactivated on its own, so LearnDash's deactivation is the only one it has. Left alone,
	 * the event stays scheduled with none of the add-on's callbacks loaded to answer it.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function deactivate(): void {
		Learndash_WooCommerce_Cron::deregister_hook();
	}

	/**
	 * PSR-4 prefixes the bundled WooCommerce Integration ships.
	 *
	 * @since 5.2.0
	 *
	 * @return array<string,string>
	 */
	protected function get_psr4_prefixes(): array {
		return [
			'LearnDash\\WooCommerce\\' => LEARNDASH_LMS_PLUGIN_DIR . 'includes/woocommerce/src/App',
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
				'slug'                       => $this->get_slug(),
				'bundled_plugin_file'        => LEARNDASH_LMS_PLUGIN_DIR . 'includes/woocommerce/learndash_woocommerce.php',
				'plugin_loaded_constant'     => 'LEARNDASH_WOOCOMMERCE_PLUGIN_PATH',
				'standalone_plugin_basename' => 'learndash-woocommerce/learndash_woocommerce.php',
				'conflict_notice_message'    => function (): string {
					return $this->get_conflict_notice_message();
				},
				'enabled'                    => function (): bool {
					return $this->should_load();
				},
				'dependency_check'           => function (): bool {
					return $this->dependencies_are_met();
				},
				'dependency_notice_message'  => function (): string {
					return $this->get_dependency_notice_message();
				},
			]
		);
	}

	/**
	 * Whether the bundled WooCommerce Integration should be loaded at all.
	 *
	 * WooCommerce being present is asked here rather than left to `dependency_check`, so a site that does not run
	 * WooCommerce skips the add-on the way a site that turned it off does: silently, and invisibly to the conflict pass,
	 * which never deactivates a standalone copy to make room for a bundled one that was not going to load. That leaves
	 * the dependency check the one case a notice helps with -- WooCommerce active, but older than the integration
	 * supports.
	 *
	 * `class_exists()` rather than the basename the dependency check looks up, because it is also true of a copy
	 * installed as a must-use plugin, in a renamed directory, or activated network-wide.
	 *
	 * Asked ahead of the parent, which applies a filter: a site with no WooCommerce has nothing for host code to decide,
	 * and both the conflict pass and the load pass read this on every request.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	protected function should_load(): bool {
		return class_exists( 'WooCommerce' ) && parent::should_load();
	}

	/**
	 * Whether WooCommerce is active at a supported version.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	private function dependencies_are_met(): bool {
		$checker = new Dependency_Checker();

		$checker->set_dependencies(
			[
				'woocommerce/woocommerce.php' => [
					'label'            => '<a href="https://woocommerce.com/">WooCommerce</a>',
					'class'            => 'WooCommerce',
					'min_version'      => self::WOOCOMMERCE_MIN_VERSION,
					'version_constant' => 'WC_VERSION',
				],
			]
		);

		return $checker->check_dependency_results();
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
				'%1$sThe WooCommerce integration is now part of LearnDash LMS.%2$s %3$sThe WooCommerce integration features have been added directly into LearnDash — no separate plugin needed.%4$sIf the LearnDash LMS - WooCommerce Integration add-on was previously installed, it\'s no longer required and can be safely uninstalled.%5$s',
				'learndash'
			),
			'<strong>',
			'</strong>',
			'<p>',
			'<br>',
			'</p>'
		);
	}

	/**
	 * Message shown when WooCommerce is missing or too old.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	private function get_dependency_notice_message(): string {
		return sprintf(
			// translators: %s: minimum WooCommerce version.
			__( 'LearnDash LMS - WooCommerce Integration requires <a href="https://woocommerce.com/">WooCommerce</a> %s or later to be active.', 'learndash' ),
			self::WOOCOMMERCE_MIN_VERSION
		);
	}
}
