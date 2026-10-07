<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict;

/**
 * Where the user lands after a standalone has been deactivated.
 *
 * The destination comes from the *current* request, not the referrer, which for an admin arriving
 * from a bookmark names somewhere they never asked to go. The URI is never trusted as a URL -- only
 * the screen and the query survive it. `wp_safe_redirect()` and the `exit` stay in Resolver.
 *
 * @since 1.0.0
 */
class Redirector
{
    /**
     * Where to send the user after deactivating.
     *
     * Re-requesting the screen the user is already on is the point, not a waste: only a fresh request
     * sheds the standalone's code from memory, and it cannot loop. The parameter is documented
     * `string` and declared as nothing, so a filtered $_SERVER value cannot TypeError from here.
     *
     * @since 1.0.0
     *
     * @param string $request_uri The current request URI, i.e. $_SERVER['REQUEST_URI'].
     *
     * @return string Absolute admin URL to send the user to.
     */
    public function after_deactivation($request_uri): string
    {
        // A non-string meets no other refusal; an empty string names no screen.
        if (!is_string($request_uri) || $request_uri === '') {
            return $this->admin_url_for('plugins.php');
        }
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $screen = $this->screen_from_path(is_string($path) ? $path : '');
        // Names no admin screen -- a front-end permalink, a traversal attempt -- nothing to re-render.
        if ($screen === '') {
            return $this->admin_url_for('plugins.php');
        }
        // The exception to re-requesting: reloading either update screen re-runs an update.
        if ($screen === 'update.php' || $screen === 'update-core.php') {
            return $this->admin_url_for('plugins.php');
        }
        return $this->admin_url_for($screen . $this->query_string($request_uri));
    }
    /**
     * The admin screen a request path names, or an empty string if it names none.
     *
     * Read from the path's basename rather than the URI: the request URI is a bare path on a site
     * that may sit in a subdirectory, behind a TLS-terminating proxy, or under the network or user
     * admin, so nothing built from admin_url() would recognise it. It also keeps a crafted URI out.
     *
     * @since 1.0.0
     *
     * @param string $path Path component of the current request URI.
     *
     * @return string A screen name ending in '.php', or '' for a path that names no admin screen.
     */
    private function screen_from_path(string $path): string
    {
        $screen = basename($path);
        // Anchored with \z rather than $, which in PCRE also matches before a trailing newline -- so
        // "edit.php\n" would satisfy $ and leave here inside the one value this class promises is
        // validated. The pattern runs first: it is what makes the name safe to put after a directory.
        if ((bool) preg_match('/^[A-Za-z0-9_-]+\.php\z/', $screen) && $this->is_admin_screen($screen)) {
            return $screen;
        }
        // The admin roots name the dashboard by leaving it out, as core's own /wp-admin/ link does.
        // Matched against the end of the path rather than its last segment alone, or a front-end
        // permalink ending in /network/ would read as the network admin's root.
        $trimmed = rtrim($path, '/');
        foreach (['/wp-admin', '/wp-admin/network', '/wp-admin/user'] as $root) {
            if (substr($trimmed, -strlen($root)) === $root) {
                return 'index.php';
            }
        }
        return '';
    }
    /**
     * Whether the admin this request belongs to has a screen of that name to be sent back to.
     *
     * Well formed is not the same as naming a screen: `wp-login.php` satisfies the pattern above and
     * would be rebuilt as an admin URL for a file that is not there. Asked of the filesystem rather
     * than a list of core's screens, since a plugin's screens are core's files with a `page` arg.
     *
     * @since 1.0.0
     *
     * @param string $screen A screen name that has passed the pattern in screen_from_path().
     *
     * @return bool
     */
    private function is_admin_screen(string $screen): bool
    {
        if (is_network_admin()) {
            return is_file(ABSPATH . 'wp-admin/network/' . $screen);
        }
        if (is_user_admin()) {
            return is_file(ABSPATH . 'wp-admin/user/' . $screen);
        }
        return is_file(ABSPATH . 'wp-admin/' . $screen);
    }
    /**
     * The current request's query, re-encoded, ready to append to a screen name.
     *
     * The query carries which list, page and filter the user was looking at, so dropping it would
     * re-render something else. Rebuilt with http_build_query() rather than add_query_arg(), which
     * passes $urlencode as false -- a value holding an '&' would add a parameter of its own.
     *
     * @since 1.0.0
     *
     * @param string $request_uri The current request URI.
     *
     * @return string Either an empty string or a leading-'?' query string.
     */
    private function query_string(string $request_uri): string
    {
        $query = wp_parse_url($request_uri, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return '';
        }
        $args = [];
        wp_parse_str($query, $args);
        $args = $this->without_line_breaks($args);
        if ($args === []) {
            return '';
        }
        $rebuilt = http_build_query($args, '', '&', PHP_QUERY_RFC3986);
        return $rebuilt === '' ? '' : '?' . $rebuilt;
    }
    /**
     * The parsed query with CR, LF and NUL taken out of every string in it, and nothing else.
     *
     * All that is left to protect is that the destination cannot end a header; http_build_query()
     * re-encodes both halves of every pair. Deliberately not sanitize_text_field(): wp_parse_str()
     * has already url-decoded these, so a search for '100%ab' would be re-run as '100'.
     *
     * @since 1.0.0
     *
     * @param array<array-key,mixed> $args Query arguments as wp_parse_str() produced them.
     *
     * @return array<array-key,mixed>
     */
    private function without_line_breaks(array $args): array
    {
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $args[$key] = $this->without_line_breaks($value);
                continue;
            }
            if (is_string($value)) {
                $args[$key] = str_replace(["\r", "\n", "\x00"], '', $value);
            }
        }
        return $args;
    }
    /**
     * An absolute admin URL for a screen, on whichever of the three admins this request belongs to.
     *
     * admin_url() always names a site's own admin, so a super admin resolving from /wp-admin/network/
     * would be thrown onto the current blog's screens -- where a network-activated standalone is not
     * manageable at all. Both branches read constants defined before wp-load.php, so already set here.
     *
     * @since 1.0.0
     *
     * @param string $screen Screen name, optionally with its query string.
     *
     * @return string
     */
    private function admin_url_for(string $screen): string
    {
        if (is_network_admin()) {
            return network_admin_url($screen);
        }
        if (is_user_admin()) {
            return user_admin_url($screen);
        }
        return admin_url($screen);
    }
}