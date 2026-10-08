<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin;

/**
 * Makes WordPress's plugin functions available to the class using it.
 *
 * A trait rather than a collaborator: no state, no decision and no alternative implementation, so
 * an interface and a binding would buy a seam nobody would bind to.
 *
 * @since 1.0.0
 */
trait Loads_Plugin_Functions
{
    /**
     * WordPress only loads these in the admin, and we run at plugins_loaded on every request.
     *
     * Guarded on deactivate_plugins() rather than is_plugin_active(), because the latter is a common
     * third-party shim: a shimmed copy would leave the rest of the file unloaded, and the first call
     * to a function nobody shimmed then fatals.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function load_plugin_functions(): void
    {
        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }
}