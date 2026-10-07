<?php
/**
 * Bundled sub-plugin interface.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries\Plugin_Absorber\Contracts;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;

/**
 * Interface that defines the contract for an add-on bundled inside this plugin.
 *
 * @since 5.2.0
 */
interface Sub_Plugin {
	/**
	 * Everything the bundled add-on needs done while this plugin boots.
	 *
	 * This is what the add-on's service provider calls, and it is the only entry point. Registering with the Absorber
	 * is the least of what an add-on may have to do here, and none of it is its caller's business.
	 *
	 * Called from this plugin's own bootstrap, ahead of the library reading its registry at `plugins_loaded`
	 * priority 5. `Absorber::register()` only buffers, so it needs neither the library booted nor a container set.
	 *
	 * @since 5.2.0
	 *
	 * @throws Config_Exception If the sub-plugin configuration is unusable.
	 *
	 * @return void
	 */
	public function boot(): void;
}
