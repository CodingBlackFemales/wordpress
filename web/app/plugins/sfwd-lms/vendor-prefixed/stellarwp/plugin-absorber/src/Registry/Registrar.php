<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Registry;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Contracts\Registrar_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Default registry: a plain slug => Sub_Plugin map.
 *
 * @since 1.0.0
 */
class Registrar implements Registrar_Interface
{
    /**
     * @var array<string,Sub_Plugin>
     */
    private $sub_plugins = [];
    /**
     * Register a sub-plugin.
     *
     * A slug is an identity, not a key this map happens to use: it also names the sub-plugin's
     * notices and its once-ever activation record, so letting a second registration win would
     * silently drop the first from the load and hand it that record.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin to register.
     *
     * @throws Config_Exception When the slug is already registered.
     *
     * @return void
     */
    public function register(Sub_Plugin $sub_plugin): void
    {
        $slug = $sub_plugin->get_slug();
        if (isset($this->sub_plugins[$slug])) {
            // Both files, because the two registrations routinely come from different host plugins
            // and the stack trace shows only the one that lost.
            throw new Config_Exception(sprintf('Two sub-plugins are registered under the slug "%1$s": %2$s and %3$s.' . ' A slug must identify exactly one sub-plugin.', $slug, $this->sub_plugins[$slug]->get_bundled_plugin_file(), $sub_plugin->get_bundled_plugin_file()));
        }
        $this->sub_plugins[$slug] = $sub_plugin;
    }
    /**
     * @since 1.0.0
     *
     * @return array<string,Sub_Plugin>
     */
    public function all(): array
    {
        return $this->sub_plugins;
    }
}