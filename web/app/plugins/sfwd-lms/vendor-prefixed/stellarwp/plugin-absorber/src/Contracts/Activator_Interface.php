<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Runs a sub-plugin's activation routine once, ever.
 *
 * A bundled plugin is `require_once`d rather than activated, so `register_activation_hook()` never
 * fires for it. Bind a replacement to change how "once, ever" is recorded.
 *
 * @since 1.0.0
 */
interface Activator_Interface
{
    /**
     * Run the sub-plugin's activation callback unless it has already run for this slug.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin that has just been loaded.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function maybe_run(Sub_Plugin $sub_plugin): void;
}