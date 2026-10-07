<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Deactivator_Interface;
/**
 * Turns a plugin off, the way WordPress's own unattended paths do.
 *
 * @since 1.0.0
 */
class Deactivator implements Deactivator_Interface
{
    use Loads_Plugin_Functions;
    /**
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return void
     */
    public function deactivate(string $basename): void
    {
        $this->load_plugin_functions();
        // Silent, because a flush_rewrite_rules() in the standalone's deactivation hook runs here
        // at plugins_loaded and regenerates the rules before init has registered a post type,
        // 404ing every custom permalink. And no $network_wide argument: its null default enters
        // both of core's branches, where a computed true would skip the blog one and strand an
        // entry for a plugin active in both.
        deactivate_plugins($basename, true);
    }
}