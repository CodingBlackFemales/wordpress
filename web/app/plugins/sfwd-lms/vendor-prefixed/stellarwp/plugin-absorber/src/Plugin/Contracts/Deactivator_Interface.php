<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts;

/**
 * The library's one way of turning a plugin off.
 *
 * Bind a replacement to make deactivation a no-op where plugin state is managed outside WordPress.
 *
 * @since 1.0.0
 */
interface Deactivator_Interface
{
    /**
     * Deactivate the plugin in every scope it is active in.
     *
     * Called unattended, during plugins_loaded. An implementation that returns without deactivating
     * leaves two copies of the same plugin to load, which is the failure this library prevents.
     *
     * @since 1.0.0
     *
     * @param string $basename Plugin basename.
     *
     * @return void
     */
    public function deactivate(string $basename): void;
}