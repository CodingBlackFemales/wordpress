<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts\Activator_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
/**
 * Default activation tracking: a slug => true map in one option.
 *
 * @since 1.0.0
 */
class Activator implements Activator_Interface
{
    /**
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin that has just been loaded.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function maybe_run(Sub_Plugin $sub_plugin): void
    {
        $callback = $sub_plugin->get_activation_callback();
        if ($callback === null) {
            return;
        }
        $slug = $sub_plugin->get_slug();
        $done = $this->completed();
        if (!empty($done[$slug])) {
            return;
        }
        // Recorded after the callback, never before: recording first would freeze a callback that
        // throws halfway, since the next request would skip it rather than retry.
        $callback($sub_plugin);
        $done[$slug] = true;
        update_site_option($this->option_name(), $done);
    }
    /**
     * Slugs whose activation callback has already run, keyed by slug.
     *
     * A site option, not a plain one: `deactivate_plugins()` is network-wide on multisite, so the
     * record is too. The cost is that a site created afterwards finds it set and never runs the
     * callback; per-site work belongs in an `Activator_Interface` of the host's own.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return array<string,mixed>
     */
    private function completed(): array
    {
        $done = get_site_option($this->option_name(), []);
        // A corrupted option would otherwise fatal on the array read, on every request.
        return is_array($done) ? $done : [];
    }
    /**
     * The option every slug's activation record lives in.
     *
     * Private, unlike `Notices\Store::option_name()`: nothing outside reads it, and a host wanting
     * the bookkeeping elsewhere binds `Activator_Interface` instead.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    private function option_name(): string
    {
        return Config::get_option_name('activations');
    }
}