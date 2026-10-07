<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Notices;

/**
 * How a queued notice is drawn.
 *
 * Minimal markup so the library stays dependency-free, and its own class so a host wanting other
 * markup has one small thing to replace.
 *
 * @since 1.0.0
 */
class Renderer
{
    /**
     * The `notice-*` class each notice type renders with.
     *
     * A dependency notice reports a plugin that did not load at all, `notice-error` by WordPress
     * convention. The other three report a conflict already handled: the site works, so they warn.
     *
     * @since 1.0.0
     *
     * @var array<string,string>
     */
    private const CLASSES = [Writer::TYPE_MERGE => 'notice-warning', Writer::TYPE_CONFLICT => 'notice-warning', Writer::TYPE_STRANDING => 'notice-warning', Writer::TYPE_DEPENDENCY => 'notice-error'];
    /**
     * Print every message in the queue.
     *
     * `wp_kses_post()` rather than `esc_html()`, so a host's knowledge-base link or list survives
     * while a script does not; and `wpautop()` rather than a literal `<p>` around the message,
     * because a `<ul>` cannot legally sit inside one and would break straight back out of it.
     *
     * @since 1.0.0
     *
     * @param array<string,string> $queue Queue to draw, keyed `slug:type`.
     *
     * @return void
     */
    public function render(array $queue): void
    {
        foreach ($queue as $key => $message) {
            // Filtered before the emptiness check: `wp_kses_post( '<script></script>' )` is the
            // empty string, and an empty notice box reads as a bug.
            $message = trim(wp_kses_post($message));
            if ($message === '') {
                continue;
            }
            printf(
                '<div class="notice %s is-dismissible">%s</div>',
                esc_attr($this->notice_class((string) $key)),
                // Already filtered: escaping again would print a link as literal angle brackets.
                trim(wpautop($message))
            );
        }
    }
    /**
     * The `notice-*` class for a queue entry, taken from the type half of its `slug:type` key.
     *
     * @since 1.0.0
     *
     * @param string $key Queue key.
     *
     * @return string
     */
    private function notice_class(string $key): string
    {
        $parts = explode(':', $key);
        $type = (string) end($parts);
        // An unrecognised type — an older version's entry, or a host's own — is shown at the safe
        // severity rather than dropped.
        return self::CLASSES[$type] ?? 'notice-warning';
    }
}