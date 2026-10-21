<?php
namespace PublishPress\Permissions\FileAccess;

require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');

/**
 * PP_RewriteRulesNetLegacy class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2018, Agapetry Creations LLC
 *
 */
class RewriteRulesNetLegacy
{
    private $include_pp_rules = true;

    private function default_file_rule_pos($rules, $default_file_redirect_rule = [])
    {
        if (!$default_file_redirect_rule) {
            $default_file_redirect_rule = [];
            $default_file_redirect_rule [] = 'RewriteRule ^([_0-9a-zA-Z-]+/)?files/(.+) wp-includes/ms-files.php?file=$2 [L]';  // WP 3.0 - subdomain
            $default_file_redirect_rule [] = 'RewriteRule ^files/(.+) wp-includes/ms-files.php?file=$1 [L]'; // WP 3.0 - subdirectory
        }

        foreach ($default_file_redirect_rule as $default_rule) {
            if ($pos_def = strpos($rules, $default_rule)) {
                return $pos_def;
            }
        }

        return false;
    }

    function update_main_rules($include_pp_rules = true)
    {
        $const_name = ($include_pp_rules) ? 'FLUSHING_RULES_PP' : 'CLEARING_RULES_PP';

        if (defined($const_name))
            return;

        define($const_name, true);

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');

        // Avoid file corruption by skipping if another flush was initiated < 5 seconds ago
        // Currently, this could leave .htaccess out of sync with settings (following an unusual option updating sequence), but that's the lesser failure
        if ($last_regen = get_site_option('presspermit_main_htaccess_date')) {
            if (intval($last_regen) > RewriteRules::timeGMT() - 5) {
                return;
            }
        }
        update_site_option('presspermit_main_htaccess_date', RewriteRules::timeGMT());  // stores to site_meta table for network installs.  Note: scoper_update_site_option is NOT equivalent

        // sleep() time is necessary to avoid .htaccess file i/o race conditions since other plugins (W3 Total Cache) may also perform or trigger .htaccess update, and those file operations don't all use flock
        // This update only occurs on plugin activation, the first time a MS site has an attachment to a private/restricted page, and on various plugin option changes.

        add_action('shutdown', [$this, 'apply_ms_htaccess_update']);
    }

    function apply_ms_htaccess_update()
    {
        sleep(2);
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
        $this->update_ms_htaccess($this->$include_pp_rules);
    }

    // directly inserts essential RS rules into the main wp-mu .htaccess file
    public static function update_ms_htaccess($include_pp_rules = true, $do_live_htaccess = false, $args = [])
    {
        if (file_exists(ABSPATH . '/wp-admin/includes/misc.php'))
            include_once(ABSPATH . '/wp-admin/includes/misc.php');

        if (file_exists(ABSPATH . '/wp-admin/includes/file.php'))
            include_once(ABSPATH . '/wp-admin/includes/file.php');

        if (!$include_pp_rules)
            delete_site_option('presspermit_file_filtered_sites');

        $home_path = NetworkLegacy::get_home_path();

        $sample_text = "\n# TO ENABLE FILE FILTERING, THE FOLLOWING RULES MUST BE COPIED INTO .htaccess right before the ms-files.php rules: \n#";

        // phpcs Note: Already confirmed file is writable

        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
        // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents

        if ($do_live_htaccess) {
            // writing to ACTUAL LIVE .htaccess file
            $output_path = $home_path . '.htaccess';
            if (!is_writable($output_path))
                return;

            // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
            $contents = file_get_contents($output_path);

            if ($pos_def = self::default_file_rule_pos($contents)) {  // true = insert into sample file
                // save backups first
                $backup_path = $output_path . '.backup-original';
                if (!file_exists($backup_path)) {
                    if (file_exists($output_path))
                        copy($output_path, $backup_path);
                }

                $backup_path = $output_path . '.backup';
                if (file_exists($output_path)) {
                    if (file_exists($backup_path))
                        unlink($backup_path);

                    copy($output_path, $backup_path);
                }

                $fp = fopen($output_path, 'w');

                if ($pos_pp_start = strpos($contents, "\n# BEGIN Press Permit"))
                    fwrite($fp, substr($contents, 0, $pos_pp_start));
                else
                    fwrite($fp, substr($contents, 0, $pos_def));

                if ($include_pp_rules) {
                    $main_rules = self::build_main_rules((array)$args);
                    fwrite($fp, $main_rules);
                }

                fwrite($fp, substr($contents, $pos_def));
                fclose($fp);

                if (!defined('PRESSPERMIT_LIMIT_HTACCESS_ROOT_REGEN')) {
                    if ($include_pp_rules)
                        FileAccess::flushAllFileRules();
                    else
                        FileAccess::clearAllFileRules();
                }
            }
        } else {
            // writing to sample file 
            $output_path = $home_path . '.htaccess-required-insertions';
            if (!is_writable($output_path))
                return;

            if (file_exists($output_path))
                unlink($output_path);

            $rules = self::build_main_rules((array)$args);
            file_put_contents($output_path, $rules);
        }
    }

    public static function build_main_rules($args = [])
    {
        $new_rules = "\n# BEGIN Press Permit\n";

        $new_rules .= "<IfModule mod_rewrite.c>\n";

        $new_rules .= "RewriteEngine On\n";

        $new_rules .= self::build_site_file_redirects($args);

        $new_rules .= "</IfModule>\n";

        $new_rules .= "\n# END Press Permit\n\n";

        return $new_rules;
    }

    // Note: This filter is never applied by WP Multisite  @todo: confirm
    // In case a modified or future MU regenerates the site .htaccess, filter contents to include PP rules
    public static function insert_main_rules($rules = '')
    {
        if ($pos_def = self::default_file_rule_pos($rules)) {
            $rules = substr($rules, 0, $pos_def) . self::build_main_rules() . substr($rules, $pos_def);
        }

        return $rules;
    }

    private function build_site_file_redirects($args = [])
    {
        global $wpdb, $base;

        $blog_id = get_current_blog_id();

        $defaults = ['ms_all_sites' => false, 'current_site_only' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');

        if (!RewriteRules::siteConfigSupportsRewrite())
            return '';

        $new_rules = '';
        $orig_blog_id = $blog_id;

        $strip_path = str_replace('\\', '/', trailingslashit(ABSPATH));

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/Analyst.php');

        $new_rules .= "\n#Run file requests through site-specific .htaccess to support filtering.\n";

        $file_filtered_sites = [];

        if ($current_site_only) {
            // phpcs Note: Direct query with custom set of column names to support legacy query

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT blog_id, path, domain FROM $wpdb->blogs WHERE blog_id = %d ORDER BY blog_id",
                    $blog_id
                )
            );
        } else {
            // phpcs Note: Direct query with custom set of column names to support legacy query

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results("SELECT blog_id, path, domain FROM $wpdb->blogs ORDER BY blog_id");
        }

        foreach ($results as $row) {
            switch_to_blog($row->blog_id);

            if (defined('PP_FILE_FILTER_ALL_SITES') || $ms_all_sites || $results = Analyst::identifyProtectedAttachments()) {
                $file_filtered_sites [] = $row->blog_id;

                // WP-mu content rules are only inserted if defined uploads path matches this default structure
                $dir = ABSPATH . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";
                $url = trailingslashit(site_url()) . UPLOADBLOGSDIR . "/{$row->blog_id}/files/";

                $uploads = apply_filters('upload_dir', ['path' => $dir, 'url' => $url, 'subdir' => '', 'basedir' => $dir, 'baseurl' => $url, 'error' => false]);

                $content_base = str_replace($strip_path, '', str_replace('\\', '/', $uploads['basedir']));

                // If a filter has changed basedir, don't filter file attachments for this site
                if (strpos($content_base, "/blogs.dir/{$row->blog_id}/files/")) {
                    if (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                        $domain = $row->domain;
                        if ($pos = strpos($domain, '.'))
                            $domain = substr($domain, 0, $pos);

                        $new_rules .= "RewriteCond %{HTTP_HOST} ^$domain\.(.*)\n";
                        $new_rules .= "RewriteRule ^files/(.+) {$content_base}$1 [L]\n";
                    } else {
                        $path = trailingslashit($row->path);

                        if ($base && ('/' != $base)) {
                            if (0 === strpos($path, $base))
                                $path = substr($path, strlen($base));
                        }

                        $new_rules .= "RewriteRule ^{$path}files/(.+) {$content_base}$1 [L]\n";  //RewriteRule ^blog1/files/(.*) wp-content/blogs.dir/2/files/$1 [L]
                    }
                }
            }
        }

        switch_to_blog($orig_blog_id);

        return $new_rules;
    }
}
