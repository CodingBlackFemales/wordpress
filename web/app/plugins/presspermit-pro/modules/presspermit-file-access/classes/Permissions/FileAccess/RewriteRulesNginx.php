<?php
namespace PublishPress\Permissions\FileAccess;

/**
 * Rewrite class
 *
 * Nginx configuration file generation.
 * 
 * Currently requires separate server intervention to trigger Nginx config reload.
 * To output Nginx rewrite rules, define the following constants in wp-config.php:
 *	
 *		define( 'PP_NGINX_CFG_PATH', '/path/to/your/supplemental/file.conf' );
 *		define( 'PP_FILE_ROOT', '/wp-content' );  // typical configuration (modify with actual path to folder your uploads folder is in, relative to http root) 
 *
 *	NOTE that you will need to provide your own server scripts to trigger an Nginx reload upon config file update.
 *	
 *	On network installations, rules from all sites are inserted into the same file, specified by PP_NGINX_CFG_PATH.  Each site's rules are preceded by a distinguishing comment tag.
 *	
 *	To disable .htaccess output, define the following constant (in addition to PP_NGINX_CFG_PATH) :
 *	
 *		define( 'PP_NO_HTACCESS', true );
 *	
 *	You may manually force regeneration of Nginx or .htaccess rules by creating the file defined in this constant:
 *	
 *		define( 'PP_FILE_REGEN_TRIGGER', '/path/to/your/trigger/file' ); 
 * 
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2018, Agapetry Creations LLC
 *
 */
class RewriteRulesNginx
{
    static function &buildSiteFileConfig($args = [])
    {
        $defaults = ['regenerate_keys' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        global $wpdb;

        $home_root = wp_parse_url(get_option('home'));

        if (!isset($home_root['path'])) {
            $home_root['path'] = '/';
        }

        $home_root = trailingslashit($home_root['path']);

        $uploads = FileAccess::getUploadInfo();

        $baseurl = trailingslashit($uploads['baseurl']);

        $arr_url = wp_parse_url($baseurl);

        $rewrite_base = $arr_url['path'];

        if (defined('PP_FILE_ROOT')) {
            $pos = strpos($rewrite_base, PP_FILE_ROOT);
            if ($pos) {
                $rewrite_base = substr($rewrite_base, $pos);
            }
        }

        $file_keys = [];
        $has_postmeta = [];

        if (!$regenerate_keys) {
            // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($key_results = $wpdb->get_results(
                "SELECT pm.meta_value, p.guid, p.ID FROM $wpdb->postmeta AS pm"
                . " INNER JOIN $wpdb->posts AS p ON p.ID = pm.post_id WHERE pm.meta_key = '_rs_file_key'"
            )) {
                foreach ($key_results as $row) {
                    $file_keys[$row->guid] = $row->meta_value;
                    $has_postmeta[$row->ID] = $row->meta_value;
                }
            }
        }

        $new_rules = "\n";

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/Analyst.php');
        if (!$attachment_results = Analyst::identifyProtectedAttachments()) {
            return $new_rules;
        }

        if (is_multisite())
            $new_rules .= "location ~* $rewrite_base" . ' {' . "\n";
        else
            $new_rules .= "location $rewrite_base" . ' {' . "\n";

        $main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&pp_rewrite=1 [NC,L]\n";

        $htaccess_urls = [];

        $attachment_id_csv = implode("','", array_map('intval', array_keys($attachment_results)));

        // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $unfiltered_ids = $wpdb->get_col(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering'"
            . "AND meta_value = '0' AND post_id IN ('$attachment_id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        if ($pass_small_thumbs = presspermit()->getOption('small_thumbnails_unfiltered')) {
            // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $thumb_filtered_ids = $wpdb->get_col(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering'"
                . " AND meta_value = 'all' AND post_id IN ('$attachment_id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        } else {
            $thumb_filtered_ids = [];
        }

        foreach ($attachment_results as $row) {
            if (false === strpos($row->guid, $baseurl))  // no need to include any attachments which are not in the uploads folder
                continue;

            if (in_array($row->ID, $unfiltered_ids))
                continue;

            if (!empty($file_keys[$row->guid])) {
                $key = $file_keys[$row->guid];
            } else {
                $key = urlencode(str_replace('.', '', uniqid(strval(rand()), true)));  // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
                $file_keys[$row->guid] = $key;
            }

            if (!isset($has_postmeta[$row->ID]) || ($key != $has_postmeta[$row->ID]))
                update_post_meta($row->ID, "_rs_file_key", $key);

            if (isset($htaccess_urls[$row->guid]))  // if a file is attached to multiple protected posts, use a single rewrite rule for it
                continue;

            $htaccess_urls[$row->guid] = true;

            $rel_path = str_replace($baseurl, '', $row->guid);

            // escape spaces
            $file_path = str_replace(' ', '\s', $rel_path);

            // escape horiz tabs (yes, at least one user has them in filenames)
            $file_path = str_replace(chr(9), '\t', $file_path);

            // strip out all other nonprintable characters.  Affected files will not be filtered, but we avoid 500 error.  Possible TODO: advisory in file attachment utility
            $file_path = preg_replace('/[\x00-\x1f\x7f]/', '', $file_path);

            // escape all other regular expression operator characters
            $file_path = preg_replace('/[\^\$\.\+\[\]\(\)\{\}]/', '\\\$0', $file_path);

            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            $redirect_path = $rel_path; // urlencode($rel_path);
            $new_rules .= 'if ( $arg_rs_file_key != "' . $key . '" ) { rewrite ^(.*)/' . $file_path . '$ "' . "{$home_root}index.php?attachment=" . $redirect_path . '&pp_rewrite=1" last; }' . "\n";

            if (0 === strpos($row->post_mime_type, 'image') && $pos_ext = strrpos($file_path, '\.')) {
                $thumb_path = substr($file_path, 0, $pos_ext);
                $ext = substr($file_path, $pos_ext + 2);

                // if resized image file(s) exist, include rules for them
                $guid_pos_ext = strrpos($rel_path, '.');
                $pattern = $uploads['path'] . '/' . substr($rel_path, 0, $guid_pos_ext) . '-*x*' . substr($rel_path, $guid_pos_ext);
                foreach (glob($pattern) as $thumbfile) {
                    if (preg_match('/(.*)-(|scaled|[0-9]{2,4}x[0-9]{2,4}).jpg$/', $thumbfile)) {

                        $thumb_rel_path = str_replace(trailingslashit($uploads['path']), '', $thumbfile);
                        
                        // escape spaces
                        $file_path = str_replace(' ', '\s', $thumb_rel_path);

                        // escape horiz tabs (yes, at least one user has them in filenames)
                        $file_path = str_replace(chr(9), '\t', $file_path);

                        // strip out all other nonprintable characters.  Affected files will not be filtered, but we avoid 500 error.  Possible TODO: advisory in file attachment utility
                        $file_path = preg_replace('/[\x00-\x1f\x7f]/', '', $file_path);

                        // escape all other regular expression operator characters
                        $file_path = preg_replace('/[\^\$\.\+\[\]\(\)\{\}]/', '\\\$0', $file_path);

                        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                        $redirect_path = $thumb_rel_path; // urlencode($thumb_rel_path);
                        $new_rules .= 'if ( $arg_rs_file_key != "' . $key . '" ) { rewrite ^(.*)/' . $file_path . '$ "' . "{$home_root}index.php?attachment=" . $redirect_path . '&pp_rewrite=1" last; }' . "\n";
                    }
                }
            }
        } // end foreach protected attachment

        $new_rules .= '}' . "\n";

        return $new_rules;
    }

    public static function insertWithMarkers($filename, $marker, $insertion, $args = [])
    {
        $defaults = ['invalidate_marker_suffix' => '', 'update_only' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        // phpcs Note: Already confirmed file is writable

        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
        // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite

        if (!file_exists($filename) || is_writable($filename)) {
            if (!file_exists($filename)) {
                $markerdata = '';
            } else {
                $markerdata = explode("\n", implode('', file($filename)));
            }

            if (!$f = @fopen($filename, 'w'))
                return false;

            $foundit = false;
            if ($markerdata) {
                $state = true;
                foreach ($markerdata as $n => $markerline) {
                    $pos = strpos($markerline, '# BEGIN ' . $marker);
                    if ((false !== $pos) && $invalidate_marker_suffix) {
                        if (strpos($markerline, $invalidate_marker_suffix, $pos + strlen('# BEGIN ') + strlen($marker) - 1))
                            $pos = false;
                    }

                    if ($pos !== false)
                        $state = false;
                    if ($state) {
                        if ($n + 1 < count($markerdata))
                            fwrite($f, "{$markerline}\n");
                        else
                            fwrite($f, "{$markerline}");
                    }

                    $pos = strpos($markerline, '# END ' . $marker);
                    if ((false !== $pos) && $invalidate_marker_suffix) {
                        if (strpos($markerline, $invalidate_marker_suffix, $pos + strlen('# END ') + strlen($marker) - 1))
                            $pos = false;
                    }

                    if ($pos !== false) {
                        fwrite($f, "# BEGIN {$marker}\n");
                        if (is_array($insertion))
                            foreach ($insertion as $insertline)
                                fwrite($f, "{$insertline}\n");
                        fwrite($f, "# END {$marker}\n");
                        $state = true;
                        $foundit = true;
                    }
                }
            }

            if (!$foundit && !$update_only) {
                fwrite($f, "\n# BEGIN {$marker}\n");
                foreach ($insertion as $insertline)
                    fwrite($f, "{$insertline}\n");
                fwrite($f, "# END {$marker}\n");
            }
            fclose($f);

            return true;
        } else {
            return false;
        }
    }
}
