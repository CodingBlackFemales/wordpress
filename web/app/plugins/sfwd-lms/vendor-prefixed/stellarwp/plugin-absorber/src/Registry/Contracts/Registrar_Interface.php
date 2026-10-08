<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Contracts;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Holds the registered sub-plugins. Bind a replacement to change registration behavior globally.
 *
 * @since 1.0.0
 */
interface Registrar_Interface
{
    /**
     * Register a sub-plugin. A slug may only be registered once.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin to register.
     *
     * @throws Config_Exception When the slug is already registered.
     *
     * @return void
     */
    public function register(Sub_Plugin $sub_plugin): void;
    /**
     * Every registered sub-plugin, in registration order.
     *
     * @since 1.0.0
     *
     * @return array<string,Sub_Plugin> Keyed by slug.
     */
    public function all(): array;
}