<?php

/**
 * Clears third-party page caches after a Revision is applied to its published post.
 *
 * Creating, submitting, scheduling or declining a Revision does not change the
 * published post and therefore must not invalidate its public page cache.
 */
class RevisionaryProCacheCompatibility
{
    public function __construct()
    {
        add_action('revision_applied', [$this, 'purgePublishedPost'], 20, 2);
    }

    public function purgePublishedPost($postId, $revision = null)
    {
        $postId = absint($postId);

        if (!$postId || !get_post($postId)) {
            return;
        }

        // LiteSpeed Cache and W3 Total Cache are handled by Revisionary core.

        if ($this->pluginActive('wp-fastest-cache/wpFastestCache.php')) {
            if (function_exists('wpfc_clear_post_cache_by_id')) {
                wpfc_clear_post_cache_by_id($postId);
            } else {
                // Compatibility with releases predating the public wrapper.
                do_action('wpfc_clear_post_cache_by_id', false, $postId);
            }
        }

        if ($this->pluginActive('wp-super-cache/wp-cache.php')
            && function_exists('wp_cache_post_change')
        ) {
            $GLOBALS['super_cache_enabled'] = 1;
            wp_cache_post_change($postId);
        }

        if ($this->pluginActive('wp-optimize/wp-optimize.php')
            && class_exists('WPO_Cache_Rules')
            && is_callable(['WPO_Cache_Rules', 'instance'])
        ) {
            $cacheRules = WPO_Cache_Rules::instance();
            if (is_callable([$cacheRules, 'purge_post_on_update'])) {
                $cacheRules->purge_post_on_update($postId);
            }
            if (is_callable([$cacheRules, 'purge_archive_pages_on_post_update'])) {
                $cacheRules->purge_archive_pages_on_post_update($postId);
            }
        }

        if ($this->pluginActive('breeze/breeze.php')) {
            if (has_action('purge_post_cache')) {
                do_action('purge_post_cache', $postId);
            } else {
                do_action('breeze_clear_all_cache');
            }
        }

        if ($this->pluginActive('speedycache/speedycache.php')
            && class_exists('SpeedyCache\\Delete')
            && is_callable(['SpeedyCache\\Delete', 'on_status_change'])
        ) {
            // A publish-to-publish transition clears the post and related caches.
            \SpeedyCache\Delete::on_status_change('publish', 'publish', get_post($postId));
        }
    }

    private function pluginActive($plugin)
    {
        static $activePlugins = null;

        if (null === $activePlugins) {
            $activePlugins = array_fill_keys((array) get_option('active_plugins', []), true);

            if (is_multisite()) {
                $activePlugins += (array) get_site_option('active_sitewide_plugins', []);
            }
        }

        return isset($activePlugins[$plugin]);
    }
}
