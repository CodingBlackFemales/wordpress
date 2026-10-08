<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Hook_Prefix;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Plugin_Capability;
/**
 * Whether this request may have a conflict resolved at all.
 *
 * Separate from the resolver, and asked ahead of it, so a host binding its own `Resolver_Interface`
 * cannot drop a gate by omission. Two methods because the cheap request gate runs first, while the
 * user gate waits: `current_user_can()` pins the current user ahead of an SSO or JWT plugin.
 *
 * @since 1.0.0
 */
class Gatekeeper
{
    use Guards_Hook_Prefix;
    use Guards_Plugin_Capability;
    /**
     * Admin scripts that exist only to perform work.
     *
     * Each redirects or prints a result, so there is no page here to resolve on — only work to
     * interrupt.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    private const ACTION_ENDPOINTS = ['admin-post.php', 'admin-ajax.php', 'update.php', 'export.php'];
    /**
     * Query args that name an action for the current screen to perform.
     *
     * Both, because core's list tables carry a second bulk selector that submits as `action2`,
     * leaving `action` at its empty value.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    private const ACTION_ARGS = ['action', 'action2'];
    /**
     * The value core gives an action arg when no bulk action is selected.
     *
     * A list table submits its selector whether or not anything was chosen, so this arrives on
     * ordinary paging and search requests.
     *
     * @since 1.0.0
     *
     * @var string
     */
    private const NO_ACTION = '-1';
    /**
     * Whether this request is the kind conflict resolution may run on.
     *
     * The hook prefix is checked last because it is the only one of these that reports to the
     * developer: checked first, it would log a _doing_it_wrong() from every front-end request rather
     * than from the admin request that was about to resolve something.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function request_may_resolve(): bool
    {
        return $this->is_interactive_admin_request() && !$this->is_action_request() && self::has_hook_prefix();
    }
    /**
     * Whether the current user may have a conflict resolved on their request.
     *
     * plugins_loaded dispatches well before auth_redirect(), so an unauthenticated GET of any admin
     * URL gets this far. It gates every policy, not only the destructive one, and that costs nothing:
     * Notices\Presenter::render() refuses to render or clear for a user this same guard turns away.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function user_may_resolve(): bool
    {
        return self::user_may_manage_plugins();
    }
    /**
     * Whether this request is one a person is watching in wp-admin.
     *
     * Resolution deactivates a plugin and ends the request, so unguarded it turns a visitor's
     * checkout POST into a 302 that drops the order. is_admin() alone is not enough: admin-ajax.php
     * and admin-post.php both define WP_ADMIN.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_interactive_admin_request(): bool
    {
        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }
        if (wp_doing_cron() || wp_doing_ajax()) {
            return false;
        }
        // Only a GET: a redirect discards whatever was submitted -- as it would for a form posted to
        // options.php, which defines WP_ADMIN and which wp_doing_ajax() does not catch. Deferring to
        // the next page view costs nothing, since the standalone is still there to detect.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }
        return is_admin();
    }
    /**
     * Whether this GET is asking wp-admin to do something rather than to draw something.
     *
     * A GET is not automatically safe to discard: resolving on `plugins.php?action=activate` exits
     * inside the request plugin_sandbox_scrape() replays, and core reports the plugin as fatal. Blunt
     * on purpose -- any action arg at all, since a known-safe list would have to stay right forever.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function is_action_request(): bool
    {
        if (in_array($this->current_script(), self::ACTION_ENDPOINTS, true)) {
            return true;
        }
        foreach (self::ACTION_ARGS as $arg) {
            $raw = $_GET[$arg] ?? null;
            // Anything but a string names no action core could dispatch on.
            if (!is_string($raw)) {
                continue;
            }
            // Compared as it arrived: admin.php dispatches admin_action_{$action} on the raw value, so
            // sanitize_key() would empty an action named outside a-z0-9_- and admit that request. Not
            // unslashed either, for the reason Conflict\Resolver reads the request URI raw -- core
            // slashes in wp_magic_quotes(), after plugins_loaded, so stripslashes() would only damage
            // the value, turning '\' into '' and '-\1' into '-1', both of which read here as no action
            // at all.
            if ($raw !== '' && $raw !== self::NO_ACTION) {
                return true;
            }
        }
        return false;
    }
    /**
     * The admin script this request is running, as a bare file name.
     *
     * $pagenow is set before any plugin loads and is already a bare name in all three admins, where
     * matching on a path would have to know each prefix. The fallback is not superstition: core
     * derives $pagenow from PHP_SELF, which some SAPI and proxy configurations leave empty.
     *
     * @since 1.0.0
     *
     * @return string Lower-cased file name, or an empty string when the request names none.
     */
    private function current_script(): string
    {
        $candidates = [$GLOBALS['pagenow'] ?? null, $_SERVER['SCRIPT_NAME'] ?? null, $_SERVER['PHP_SELF'] ?? null];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            // Read as the SAPI left it. wp_magic_quotes() slashes $_SERVER, and wp-settings.php
            // calls it after do_action( 'plugins_loaded' ), so nothing here has been slashed yet and
            // wp_unslash() would only delete backslashes that arrived in the path itself.
            return strtolower(basename($candidate));
        }
        return '';
    }
}