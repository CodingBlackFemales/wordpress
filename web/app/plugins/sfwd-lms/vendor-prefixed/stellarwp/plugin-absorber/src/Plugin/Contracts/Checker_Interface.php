<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts;

/**
 * The library's one way of asking WordPress about a plugin. Bind a replacement to answer from
 * somewhere other than the active-plugins option.
 *
 * Separate from `Deactivator_Interface` because reading plugin state is a question anything may
 * ask, while turning a plugin off is an action one policy branch takes: a host making deactivation
 * a no-op should not have to reimplement the reading half to say so.
 *
 * @since 1.0.0
 */
interface Checker_Interface
{
    /**
     * Whether the plugin is active, in either scope.
     *
     * The question is "is the standalone's code going to run this request", which a network
     * activation answers yes just as surely as a site one.
     *
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return bool
     */
    public function is_active(string $basename): bool;
    /**
     * Whether the plugin is active across the whole network.
     *
     * Network scope only, where `is_active()` counts either. Must return `false` off a network, as
     * core's `is_plugin_active_for_network()` does, so no caller needs an `is_multisite()` guard.
     *
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return bool
     */
    public function is_network_active(string $basename): bool;
}