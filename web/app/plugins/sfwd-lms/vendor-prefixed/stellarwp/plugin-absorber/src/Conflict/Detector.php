<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Checker_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Loads_Plugin_Functions;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Whether a bundled sub-plugin's standalone counterpart is still active.
 *
 * Its own class so the conflict step has something cheap to ask before `current_user_can()`, which
 * pins the current user for the rest of the request. It changes no plugin's activation state. Not
 * `final`: it is bound by class name, the seam a host rebinds and a test subclasses.
 *
 * @since 1.0.0
 */
class Detector
{
    use Loads_Plugin_Functions;
    /**
     * @since 1.0.0
     *
     * @var Reader
     */
    private $registry;
    /**
     * @since 1.0.0
     *
     * @var Checker_Interface
     */
    private $plugin_checker;
    /**
     * Whether the configured host basename has been looked up in this request.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    private $host_plugin_looked_up = false;
    /**
     * @since 1.0.0
     *
     * @param Reader            $registry       Which sub-plugins are registered.
     * @param Checker_Interface $plugin_checker Whether the standalone is active.
     */
    public function __construct(Reader $registry, Checker_Interface $plugin_checker)
    {
        $this->registry = $registry;
        $this->plugin_checker = $plugin_checker;
    }
    /**
     * Whether any registered sub-plugin's standalone counterpart is currently active.
     *
     * Short-circuits: the caller only needs to know whether the rest of the conflict step is worth
     * entering.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return bool
     */
    public function has_conflict(): bool
    {
        // The reader rather than a registrar of our own: it drains the buffered registrations first.
        foreach ($this->registry->all() as $sub_plugin) {
            if ($this->is_in_conflict($sub_plugin)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Whether this sub-plugin's standalone counterpart is active and ours to act on.
     *
     * Policy is not consulted: a sub-plugin set to defer is still in conflict, and leaving it alone
     * is the resolver's decision.
     *
     * The `should_load` filter is, and it is the one piece of host code this class reads. It decides
     * whether the bundled copy will be in memory at all, one priority behind this — so a sub-plugin
     * the host vetoes there is in conflict with nothing: deactivating its standalone would take away
     * the only copy of that code the site has, and the merge notice would tell the owner the bundled
     * copy had taken over. Invisible here exactly as a disabled sub-plugin already is.
     *
     * Asked last, so the sub-plugin has to be enabled, name a standalone, and have that standalone
     * actually running before any of it executes — the order the load pass asks it in, last of its
     * own gates, and it keeps a filter that is arbitrary host code off every admin GET with no
     * conflict to resolve.
     *
     * Nothing here catches: `Boot\Scheduler` wraps the whole conflict step in `catch ( Throwable )`
     * and `Conflict\Resolver` catches per sub-plugin behind that, so a host filter that throws is
     * already reported and already survivable from both callers.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin to test.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return bool
     */
    public function is_in_conflict(Sub_Plugin $sub_plugin): bool
    {
        if (!$sub_plugin->is_enabled() || !$sub_plugin->has_standalone_plugin()) {
            return false;
        }
        if (!$this->plugin_checker->is_active($sub_plugin->get_standalone_plugin_basename())) {
            return false;
        }
        // The same two arguments the load pass passes, so a host wires one filter and sees one
        // signature wherever it is asked from.
        $should_load = apply_filters(Config::get_hook_name('should_load'), true, $sub_plugin);
        return (bool) $should_load;
    }
    /**
     * Whether deactivating this standalone would strand sites the bundled copy will never reach.
     *
     * `deactivate_plugins()` pulls a network-active standalone from *every* site, while the bundled
     * copy loads only where the host does -- so a network-active standalone under a host that is not
     * network-active leaves those sites with nothing. False off a network: `is_network_active()` is.
     *
     * The basename itself is checked against the installed plugins on the way past, because a name no
     * plugin answers to answers "not network-active" for ever, and that is indistinguishable from the
     * guard working: the standalone is left active on a network where nothing would have been
     * stranded, and the notice comes back on every admin page load telling the owner to
     * network-activate a plugin that already runs everywhere. A typo does it, so does an mu-plugin or
     * a plugin behind a symlink that `plugin_basename()` cannot round-trip, and so does passing
     * `__FILE__` where `plugin_basename( __FILE__ )` was meant. The answer is not changed by the
     * report -- a name this library cannot resolve is not consent to take a plugin off every site on
     * a network -- and the developer is told which one, which is the only thing that fixes it.
     *
     * The lookup sits behind both cheap guards. `get_plugins()` parses the header of every plugin
     * installed, so it is paid for only where the answer is about to matter: a configured host, and a
     * standalone that really is network-active.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin whose standalone is active.
     *
     * @return bool
     */
    public function deactivation_would_strand_sites(Sub_Plugin $sub_plugin): bool
    {
        $host_basename = Config::get_host_plugin_basename();
        if ($host_basename === '') {
            return false;
        }
        if (!$this->plugin_checker->is_network_active($sub_plugin->get_standalone_plugin_basename())) {
            return false;
        }
        $this->report_a_host_basename_no_plugin_answers_to($host_basename);
        return !$this->plugin_checker->is_network_active($host_basename);
    }
    /**
     * Tell the developer when the configured host basename names nothing that is installed.
     *
     * Here rather than in `Config::set_host_plugin_basename()`, which is where a reader looks first:
     * that is a static setter a host calls at plugin-file scope, and `get_plugins()` lives in
     * `wp-admin/includes/plugin.php`, which is not loaded then and which nothing should be loading
     * that early to validate an argument. The honest place is the one point of use, on the request
     * where the value is about to decide whether a standalone is deactivated.
     *
     * Once per request, and per instance rather than through a static: the detector is a container
     * singleton, so one instance is one request, and the host hears about the mistake again on the
     * next one until it is fixed. A static flag would report from the first request a PHP worker
     * served and then stay quiet for every request that worker went on to serve -- and it would need
     * a reset for the suite's benefit, which is API this library would then support for ever.
     *
     * @since 1.0.0
     *
     * @param string $host_basename Host plugin basename, as the host configured it.
     *
     * @return void
     */
    private function report_a_host_basename_no_plugin_answers_to(string $host_basename): void
    {
        if ($this->host_plugin_looked_up) {
            return;
        }
        $this->host_plugin_looked_up = true;
        $this->load_plugin_functions();
        if (array_key_exists($host_basename, get_plugins())) {
            return;
        }
        _doing_it_wrong(self::class . '::report_a_host_basename_no_plugin_answers_to', sprintf('The host plugin basename "%s" names no installed plugin, so the multisite stranding ' . 'guard reads the host as never network-active: a standalone that would strand no ' . 'site is left active and its notice recurs on every admin page load. ' . 'Config::set_host_plugin_basename() takes plugin_basename( __FILE__ ), not __FILE__.', $host_basename), '1.0.0');
    }
}