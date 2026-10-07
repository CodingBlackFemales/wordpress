<?php

/**
 * Templating functionality.
 *
 * @since 1.0.0
 *
 * @package StellarWP\Templates
 */
namespace LearnDash\Zapier\StellarWP\Templates;

use LearnDash\Zapier\StellarWP\Arrays\Arr;
use WP_Query;
/**
 * Handle views and template files.
 *
 * @since 1.0.0
 *
 * @package \StellarWP\Templates
 */
class Templates
{
    /**
     * Check to see if this is operating in the main loop.
     *
     * @since 1.0.0
     *
     * @param WP_Query $query The current query.
     *
     * @return bool
     */
    protected static function is_main_loop($query): bool
    {
        return $query->is_main_query();
    }
    /**
     * Look for the stylesheets. Fall back to $fallback path if the stylesheets can't be located or the array is empty.
     *
     * @since 1.0.0
     *
     * @param array|string $stylesheets Path to the stylesheet.
     * @param string       $fallback    Path to fallback stylesheet.
     *
     * @return string Path to stylesheet
     */
    public static function locate_stylesheet($stylesheets, string $fallback = ''): string
    {
        $stylesheets = Arr::wrap($stylesheets);
        if (empty($stylesheets)) {
            return $fallback;
        }
        foreach ($stylesheets as $filename) {
            if (file_exists(get_stylesheet_directory() . '/' . $filename)) {
                $located = trailingslashit(get_stylesheet_directory_uri()) . $filename;
                break;
            } elseif (file_exists(get_template_directory() . '/' . $filename)) {
                $located = trailingslashit(get_template_directory_uri()) . $filename;
                break;
            }
        }
        if (empty($located)) {
            return $fallback;
        }
        return $located;
    }
    /**
     * Add our own method is_embed to check by WordPress Version and function is_embed
     * to prevent fatal errors in WordPress 4.3 and earlier
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function is_embed(): bool
    {
        global $wp_version;
        if (version_compare($wp_version, '4.4', '<') || !function_exists('is_embed')) {
            return false;
        }
        return is_embed();
    }
}