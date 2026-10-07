<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Checker_Interface;
/**
 * Plugin state, straight from WordPress.
 *
 * @since 1.0.0
 */
class Checker implements Checker_Interface
{
    use Loads_Plugin_Functions;
    /**
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return bool
     */
    public function is_active(string $basename): bool
    {
        $this->load_plugin_functions();
        // is_plugin_active() already ORs in the network check.
        return is_plugin_active($basename);
    }
    /**
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return bool
     */
    public function is_network_active(string $basename): bool
    {
        $this->load_plugin_functions();
        // Returns false off a network on its own, so callers need no is_multisite() test.
        return is_plugin_active_for_network($basename);
    }
}