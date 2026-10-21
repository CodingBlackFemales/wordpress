<?php
namespace PublishPress\Permissions\FileAccess;

class RuleFlush
{
    public static function requestedFileRuleFlush()
    {
        if (defined('PRESSPERMIT_LIMIT_HTACCESS_REQUESTED_REGEN')) {
            return;
        }

        if ($key = presspermit()->getOption('file_filtering_regen_key')) {
            if (PWP::is_GET('key', $key)) {  // user must store their own non-null key before this will work
                self::requestedFlushAllFileRules(['echo' => true]);
            } else
                esc_html_e('Invalid argument.', 'presspermit');
        } else
            esc_html_e('Please configure File Access options!', 'presspermit');

        exit(0);
    }

    // Handles this site synchronously (so an external cron ping gets an immediate response), then
    // hands off the rest of the network to RuleFlushBatch instead of looping every site inline —
    // this method is unauthenticated (gated only by a shared-secret key), so it must not block on
    // a full-network loop. Other callers of flushAllFileRules() are unaffected.
    public static function requestedFlushAllFileRules($args = [])
    {
        FileAccess::flushFileRules(['regenerate_keys' => true]);

        if (!empty($args['echo'])) {
            esc_html_e("File attachment access keys and rewrite rules were regenerated for this site (" . get_bloginfo() . ")");
            echo '<br /><br />';
        }

        if (is_multisite()) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RuleFlushBatch.php');
            $scheduled = RuleFlushBatch::start(get_current_blog_id());

            if (!empty($args['echo'])) {
                if ($scheduled) {
                    esc_html_e('Rule expiration for other network sites has been queued and will process in the background via WP-Cron.', 'presspermit');
                } else {
                    esc_html_e('A background rule-expiration batch for other sites is already in progress.', 'presspermit');
                }
            }
        }
    }

    public static function flushAllFileRules($args=[])
    {
        self::requestedFlushAllFileRules($args);
    }

    public static function expireRemoteSiteFileRules()
    {
        global $wpdb;

        // phpcs Note: Direct query on posts table during admin operation for efficient and reliable bulk deletion of postmeta

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_rs_file_key'");
        update_option('presspermit_file_rules_expired', true);
        update_option('presspermit_regenerate_file_keys', true);
    }

    // remove rules from every .htaccess file in the wp-MU "files" folders
    public static function clearAllFileRules()
    {
        global $wpdb;

        $blog_id = get_current_blog_id();

        $blog_ids = (is_multisite()) ? get_sites(['fields' => 'ids']) : [1];
        $orig_blog_id = $blog_id;

        foreach ($blog_ids as $id) {
            if (is_multisite()) {
                switch_to_blog($id);
            }

            $uploads = FileAccess::getUploadInfo();
            $htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
            if (file_exists($htaccess_path)) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
                RewriteRules::insertWithMarkers($htaccess_path, 'Press Permit', '');
            }
        }

        if (is_multisite()) {
            switch_to_blog($orig_blog_id);
        }
    }
}
