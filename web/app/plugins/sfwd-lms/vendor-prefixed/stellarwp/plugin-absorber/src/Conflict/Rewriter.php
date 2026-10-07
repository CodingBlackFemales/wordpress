<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Rewrites the fatal-error screen an absorbed standalone earns when someone activates it.
 *
 * The one conflict the load guard cannot prevent: core includes the plugin being activated *after*
 * the bundled copy, so the re-declaration really does fatal and all this can do is reword core's
 * sentence, with the sub-plugin's own `conflict_notice_message`. Core's `error_scrape` iframe comes
 * out with it, since it re-runs the same fatal with errors on display. Not `final`: bound by class
 * name.
 *
 * @since 1.0.0
 */
class Rewriter
{
    /**
     * @since 1.0.0
     *
     * @var Reader
     */
    private $registry;
    /**
     * @since 1.0.0
     *
     * @param Reader $registry Which sub-plugins are registered.
     */
    public function __construct(Reader $registry)
    {
        $this->registry = $registry;
    }
    /**
     * The markup to print in place of the one WordPress was about to.
     *
     * Handed back untouched unless the request is a nonce-verified activation or resume error, on a
     * plugins screen, for a standalone this library has registered — and unless one of the two
     * sentences core reports a plugin fatal with is still there to swap out.
     *
     * @since 1.0.0
     *
     * @param string $markup Notice markup WordPress is about to print.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function rewrite(string $markup): string
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return $markup;
        }
        $screen = get_current_screen();
        // Both plugin lists: wp-admin/network/plugins.php is a one-line require of the other, but
        // WP_Screen appends `-network` to the id there -- and on a default multisite it is the only
        // screen an absorbed standalone can be reactivated from.
        if ($screen === null || !in_array($screen->id, ['plugins', 'plugins-network'], true)) {
            return $markup;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this read *is* the nonce.
        $nonce = isset($_GET['_error_nonce']) ? sanitize_text_field(wp_unslash($_GET['_error_nonce'])) : '';
        if ($nonce === '') {
            return $markup;
        }
        $sub_plugin = $this->find_by_activation_error($nonce) ?? $this->find_by_resume_error($nonce);
        if ($sub_plugin === null) {
            return $markup;
        }
        $message = $sub_plugin->get_conflict_notice_message(sprintf('%s is bundled with this plugin and loads automatically. The standalone copy cannot' . ' be activated alongside it.', $sub_plugin->get_slug()));
        // Sanitised before the emptiness check rather than after: wp_kses_post( '<script></script>' )
        // is the empty string, and swapping core's wording for nothing leaves a blank notice box.
        $message = trim(wp_kses_post($message));
        if ($message === '') {
            return $markup;
        }
        foreach ($this->sentences_core_reports_a_fatal_with() as $sentence) {
            if (strpos($markup, $sentence) === false) {
                continue;
            }
            return $this->without_the_error_scrape(str_replace($sentence, $message, $markup));
        }
        // Neither sentence is in there, so something else wrote this notice. Its iframe stays: with no
        // explanation to put in front of it, core's diagnostic is the only thing saying anything.
        return $markup;
    }
    /**
     * The two sentences core reports a sandboxed plugin fatal with, exactly as it prints them.
     *
     * Read back through `__()` against core's own text domain rather than written out as literals: an
     * English needle would match nothing on a site running WordPress in another language. Resume sits
     * beside activation because a standalone that fatals on an ordinary request is paused by core --
     * the activation sandbox pauses nothing, having defined WP_SANDBOX_SCRAPING -- and the Resume link
     * then re-runs the same re-declaration.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    private function sentences_core_reports_a_fatal_with(): array
    {
        return [
            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- core's own string, matched on purpose.
            __('Plugin could not be activated because it triggered a <strong>fatal error</strong>.', 'default'),
            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- core's own string, matched on purpose.
            __('Plugin could not be resumed because it triggered a <strong>fatal error</strong>.', 'default'),
        ];
    }
    /**
     * The registered sub-plugin an activation-error request names, if the nonce agrees.
     *
     * The `plugin` argument is unslashed and no further: core mints the nonce from the unslashed
     * value verbatim, so sanitizing a folder name holding a '%xx' sequence would make both the nonce
     * check and the registry lookup miss, silently. is_string() because '?plugin[]=x' arrives as an
     * array, which wp_verify_nonce() would convert rather than refuse.
     *
     * @since 1.0.0
     *
     * @param string $nonce The `_error_nonce` this request carries.
     *
     * @return Sub_Plugin|null
     */
    private function find_by_activation_error(string $nonce): ?Sub_Plugin
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below, before
        // anything is acted on.
        $basename = isset($_GET['plugin']) ? wp_unslash($_GET['plugin']) : '';
        if (!is_string($basename) || $basename === '') {
            return null;
        }
        // Looked up first: there is no nonce work to do for a plugin this library does not own.
        $sub_plugin = $this->find_by_standalone_basename($basename);
        if ($sub_plugin === null) {
            return null;
        }
        return wp_verify_nonce($nonce, 'plugin-activation-error_' . $basename) ? $sub_plugin : null;
    }
    /**
     * The registered sub-plugin a resume-error request is about, if the nonce agrees.
     *
     * Identified from the nonce alone, since core's resume redirect carries no `plugin` argument to
     * read: every registered standalone is offered to it in turn and the one it was signed for
     * answers. Gated on `error=resuming`, the request core dispatches that wording on, because
     * otherwise somebody else's failed activation fires `wp_verify_nonce_failed` once per sub-plugin
     * -- which security plugins hook to rate-limit.
     *
     * @since 1.0.0
     *
     * @param string $nonce The `_error_nonce` this request carries.
     *
     * @return Sub_Plugin|null
     */
    private function find_by_resume_error(string $nonce): ?Sub_Plugin
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which screen this is, not
        // an act to authorise; the nonce below is what authorises.
        $error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';
        if ($error !== 'resuming') {
            return null;
        }
        foreach ($this->registry->all() as $sub_plugin) {
            if (!$sub_plugin->has_standalone_plugin()) {
                continue;
            }
            $action = 'plugin-resume-error_' . $sub_plugin->get_standalone_plugin_basename();
            if (wp_verify_nonce($nonce, $action)) {
                return $sub_plugin;
            }
        }
        return null;
    }
    /**
     * The registered sub-plugin a standalone basename belongs to, if any.
     *
     * Read through the reader rather than a registrar of its own: it drains the buffered
     * registrations first.
     *
     * @since 1.0.0
     *
     * @param string $basename Standalone plugin basename named by the request.
     *
     * @return Sub_Plugin|null
     */
    private function find_by_standalone_basename(string $basename): ?Sub_Plugin
    {
        foreach ($this->registry->all() as $sub_plugin) {
            if ($sub_plugin->get_standalone_plugin_basename() === $basename) {
                return $sub_plugin;
            }
        }
        return null;
    }
    /**
     * The same notice with core's activation-sandbox iframe taken out of it.
     *
     * Core appends an `action=error_scrape` iframe to the sentence just replaced, and that request
     * re-runs the sandbox with `display_errors` forced on -- so the raw `Cannot redeclare …` prints
     * under the explanation, contradicting it. Matched on `error_scrape` inside the opening tag rather
     * than on the element core built, which would mean reproducing `add_query_arg()`, `urlencode()`
     * and `esc_url()` over a filterable URL and missing silently once one of them changed.
     *
     * Forward-looking as it stands: `wp_admin_notice()` has echoed through `wp_kses_post()` since 6.4,
     * which allows no `<iframe>`, so core strips this one itself before it reaches a browser.
     *
     * @since 1.0.0
     *
     * @param string $markup Notice markup whose sentence has already been rewritten.
     *
     * @return string
     */
    private function without_the_error_scrape(string $markup): string
    {
        $stripped = preg_replace('#<iframe\b[^>]*\berror_scrape\b[^>]*>\s*</iframe>#i', '', $markup);
        // Null means the match itself failed -- a backtrack limit on a notice far larger than core's.
        // The reworded sentence is worth keeping on its own.
        return is_string($stripped) ? $stripped : $markup;
    }
}