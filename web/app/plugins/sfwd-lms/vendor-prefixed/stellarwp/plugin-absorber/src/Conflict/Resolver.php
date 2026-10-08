<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Contracts\Resolver_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict_Policy;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts\Writer_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Deactivator_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Hook_Prefix;
use Throwable;
/**
 * Default conflict resolution: act on each conflicting sub-plugin per its policy.
 *
 * Whether there is anything to resolve and who may have it resolved are `Detector`'s and
 * `Gatekeeper`'s, asked by the conflict step before this class is built at all.
 *
 * @since 1.0.0
 */
class Resolver implements Resolver_Interface
{
    use Guards_Hook_Prefix;
    /**
     * @since 1.0.0
     *
     * @var Reader
     */
    private $registry;
    /**
     * @since 1.0.0
     *
     * @var Detector
     */
    private $detector;
    /**
     * @since 1.0.0
     *
     * @var Deactivator_Interface
     */
    private $plugin_deactivator;
    /**
     * @since 1.0.0
     *
     * @var Writer_Interface
     */
    private $notices;
    /**
     * @since 1.0.0
     *
     * @var Redirector
     */
    private $redirector;
    /**
     * @since 1.0.0
     *
     * @param Reader                $registry           Which sub-plugins are registered.
     * @param Detector              $detector           Whether a sub-plugin is in conflict.
     * @param Deactivator_Interface $plugin_deactivator Turns the standalone off.
     * @param Writer_Interface      $notices            Where the user is told what happened.
     * @param Redirector            $redirector         Where the user lands afterwards.
     */
    public function __construct(Reader $registry, Detector $detector, Deactivator_Interface $plugin_deactivator, Writer_Interface $notices, Redirector $redirector)
    {
        $this->registry = $registry;
        $this->detector = $detector;
        $this->plugin_deactivator = $plugin_deactivator;
        $this->notices = $notices;
        $this->redirector = $redirector;
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function resolve_all(): void
    {
        // Without a prefix there is no way to tell the site owner why a plugin of theirs went off,
        // and deactivating without explaining it is worse than leaving the conflict standing.
        if (!self::has_hook_prefix()) {
            return;
        }
        // "A standalone is gone", not "a deactivation was attempted": redirecting on the second loops.
        $standalone_gone = false;
        // The reader rather than a registrar of our own: it drains the buffered registrations first.
        foreach ($this->registry->all() as $sub_plugin) {
            // The policy, the notice message and the standalone's own deactivation hook are all
            // arbitrary code. Caught per sub-plugin, so one throw does not take the rest with it.
            try {
                if (!$this->detector->is_in_conflict($sub_plugin)) {
                    continue;
                }
                if ($this->resolve($sub_plugin)) {
                    $standalone_gone = true;
                }
            } catch (Throwable $thrown) {
                _doing_it_wrong(self::class . '::resolve_all', sprintf('The conflict for "%s" threw while being resolved, so it was abandoned: %s', $sub_plugin->get_slug(), $thrown->getMessage()), '1.0.0');
            }
        }
        // After the loop, never inside it: an `exit` on the first of two active standalones leaves the
        // second running and takes the load pass at the next priority with it. And only where one
        // really went away — a request that deactivated nothing would redirect into the same conflict.
        if ($standalone_gone) {
            $this->redirect();
        }
    }
    /**
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin whose standalone is active.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return bool Whether the standalone is gone -- not whether deactivating it was attempted.
     */
    protected function resolve(Sub_Plugin $sub_plugin): bool
    {
        $policy = $sub_plugin->get_conflict_policy();
        // A filter may return anything, and falling through to deactivate() would turn a plugin off
        // on the strength of a typo.
        if (!Conflict_Policy::is_valid($policy)) {
            $policy = Conflict_Policy::NOTICE_ONLY;
        }
        switch ($policy) {
            case Conflict_Policy::DEFER:
                // The standalone wins. Its own constant makes the load path skip the bundled copy.
                return false;
            case Conflict_Policy::DEACTIVATE:
                // Deactivating network-wide would pull the standalone from sites the host never
                // reaches, where nothing loads the bundled copy. Leave it -- the load guard defers
                // the bundled copy network-wide instead -- and say why.
                if ($this->detector->deactivation_would_strand_sites($sub_plugin)) {
                    $this->notices->queue_stranding_notice($sub_plugin);
                    return false;
                }
                return $this->deactivate($sub_plugin);
            // NOTICE_ONLY, and any valid policy this switch has grown no branch for. The default only
            // talks: a policy nobody wrote must never be read as consent to turn a plugin off.
            default:
                $this->notices->queue_conflict_notice($sub_plugin);
                return false;
        }
    }
    /**
     * Turn the standalone off, and report the merge only if it really went off.
     *
     * The answer is also what decides the redirect. Sending the user back to the screen they asked for
     * while the standalone is still running arrives at the same conflict, deactivates to no effect and
     * redirects again -- until the browser gives up with the whole of wp-admin out of reach, and with
     * every one of those requests exiting before `all_admin_notices` could draw anything.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin whose standalone is active.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return bool Whether the standalone is gone -- not whether deactivating it was attempted.
     */
    protected function deactivate(Sub_Plugin $sub_plugin): bool
    {
        $this->plugin_deactivator->deactivate($sub_plugin->get_standalone_plugin_basename());
        // Asked again rather than assumed: an mu-plugin filtering `option_active_plugins` puts it
        // straight back, and either plugin seam may have been rebound to something that never turns
        // it off.
        if ($this->detector->is_in_conflict($sub_plugin)) {
            // Said nothing about, and reported nowhere. This is the DEFER outcome by another route --
            // the standalone is running, so its own guard constant stands the bundled copy down -- and
            // every way of arriving here is a site's configuration rather than the host's mistake.
            return false;
        }
        // Queued per deactivation, so a site with two standalones gets one notice per plugin it lost.
        $this->notices->queue_merge_notice($sub_plugin);
        return true;
    }
    /**
     * Re-request the current screen, now that the standalone's code is out of the way.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function redirect(): void
    {
        // Boot\Scheduler runs this sequence inline when a host boots too late, behind a
        // _doing_it_wrong() that prints on a debugging site — so wp_safe_redirect() would set no
        // Location and the exit would end the request on a blank page. Falling through renders it.
        if (headers_sent()) {
            return;
        }
        // Read raw, not through wp_unslash(). Core adds the slashes in wp_magic_quotes(), which
        // wp-settings.php calls *after* do_action( 'plugins_loaded' ) -- so at priority 5 there are
        // none on this value and unslashing is a plain stripslashes() over the URL bar. An admin
        // searching for a Windows path who trips a conflict would be sent back to a search for
        // "C:projects", which is the one thing the redirect exists to get right. Reached from the
        // inline fallback a too-late boot reports, this can run after the slashing instead, where the
        // cost is a stray backslash in a re-encoded query arg rather than a deleted one.
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        // $_SERVER carries whatever the SAPI put there, so the promised string is made one here.
        if (!is_string($request_uri)) {
            $request_uri = '';
        }
        wp_safe_redirect($this->redirector->after_deactivation($request_uri));
        exit;
    }
}