<?php
namespace PublishPress\Permissions\FileAccess;

if (is_multisite() && get_site_option('ms_files_rewriting')) {
    require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
}

/**
 * RewriteRules class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2024, PublishPress
 *
 */
class RewriteRules
{
    public static function insertWithMarkers($file_path, $marker_text, $insertion)
    {
        if ($insertion || file_exists($file_path)) {
		    if (function_exists('insert_with_markers')) {
		        $retval = insert_with_markers($file_path, $marker_text, explode("\n", $insertion));
		    } else {
			    $retval = self::doInsertWithMarkers($file_path, $marker_text, explode("\n", $insertion));
		    }
        }
    }

    public static function siteConfigSupportsRewrite()
    {
        // don't risk leaving custom .htaccess files in content folder at deactivation due to difficulty of reconstructing custom path for each blog
        if (is_multisite() && get_site_option('ms_files_rewriting')) {
            global $pagenow;

            $blog_id = get_current_blog_id();

            if ('site-new.php' == $pagenow)
                return true;

            if (UPLOADS != UPLOADBLOGSDIR . "/$blog_id/files/")
                return false;

            if (BLOGUPLOADDIR != WP_CONTENT_DIR . "/blogs.dir/$blog_id/files/")
                return false;
        }

        return true;
    }

    public static function updateSiteFileRules($args = [])
    {
        $blog_id = get_current_blog_id();

        $defaults = ['include_pp_rules' => true, 'regenerate_keys' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        update_option('presspermit_file_htaccess_date', self::timeGMT());

        if (!self::siteConfigSupportsRewrite()) {
            return;
        } else {
            $rules = ($include_pp_rules) ? self::buildSiteFileRules($args) : '';

            if (defined('PP_NGINX_CFG_PATH')) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNginx.php');
                $args['regenerate_keys'] = false;
                $rules_nginx = ($include_pp_rules) ? RewriteRulesNginx::buildSiteFileConfig($args) : '';
            }
        }

        $uploads = FileAccess::getUploadInfo();

        // If a filter has changed MU basedir, don't filter file attachments for this site because we might not be able to regenerate the basedir for rule removal at RS deactivation
        if (!is_multisite() || !get_site_option('ms_files_rewriting') || (strpos($uploads['basedir'], "/blogs.dir/$blog_id/files") || (false !== strpos($uploads['basedir'], trailingslashit(WP_CONTENT_DIR) . 'uploads')))) {

            if (!defined('PP_NGINX_CFG_PATH') || !defined('PP_NO_HTACCESS')) {
                $htaccess_path = trailingslashit($uploads['basedir']) . '.htaccess';
                self::insertWithMarkers($htaccess_path, 'Press Permit', $rules);
            }

            if (defined('PP_NGINX_CFG_PATH')) {
                // prior to 2.1.16, main site rules marker did not include " - site 1" suffix. Clear this old instance if it exists to prevent ambiguity on subsequent updates. 
                if (is_multisite() && is_main_site()) {
                    RewriteRulesNginx::insertWithMarkers(PP_NGINX_CFG_PATH, 'Press Permit', [], ['invalidate_marker_suffix' => ' - site', 'update_only' => true]);
                }

                self::insertWithMarkers(PP_NGINX_CFG_PATH, "Press Permit - site $blog_id", $rules_nginx);
            }
        }
    }

    static function &buildSiteFileRules($args = [])
    {
        $defaults = ['regenerate_keys' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        global $wpdb;

        $home_root = wp_parse_url(get_option('home'));
        $home_root = (isset($home_root['path'])) ? trailingslashit($home_root['path']) : '/';

        $uploads = FileAccess::getUploadInfo();

        $baseurl = trailingslashit($uploads['baseurl']);

        $arr_url = wp_parse_url($baseurl);
        $rewrite_base = $arr_url['path'];

        $has_postmeta = [];

        if (!$regenerate_keys) {
            // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($key_results = $wpdb->get_results(
                "SELECT pm.meta_value, p.guid, p.ID FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON p.ID = pm.post_id WHERE pm.meta_key = '_rs_file_key'"
            )) {
                foreach ($key_results as $row) {
                    $has_postmeta[$row->ID] = $row->meta_value;
                }
            }
        }

        $new_rules = '';

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/Analyst.php');
        if (!$attachment_results = Analyst::identifyProtectedAttachments()) {
            return $new_rules;
        }

        $apache_ver = (function_exists('apache_get_version')) ? apache_get_version() : '';
        $matches = [];
        preg_match('/Apache[\/ ]([0-9.]+)/', $apache_ver, $matches);

        if (!empty($matches[1]) && version_compare($matches[1], '2.4', '>')) {
            $new_rules .= "<files .htaccess>\n";
            $new_rules .= "Require all denied\n";
            $new_rules .= "</files>\n\n";
        } else {
            $new_rules .= "<files .htaccess>\n";
            $new_rules .= "order allow,deny\n";
            $new_rules .= "deny from all\n";
            $new_rules .= "</files>\n\n";
        }

        $new_rules .= "<IfModule mod_rewrite.c>\n";
        $new_rules .= "RewriteEngine On\n";
        $new_rules .= "RewriteBase $rewrite_base\n\n";

        if (presspermit()->getOption('file_access_apply_redirect')) {
            $main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&pp_rewrite=1 [NC,L,R=301]\n";
        
        } elseif (defined('PRESSPERMIT_FILE_ACCESS_DISCARD_QUERYSTRING')) {
        	$main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&pp_rewrite=1 [NC,L]\n";
        } else {
            $main_rewrite_rule = "RewriteRule ^(.*) {$home_root}index.php?attachment=$1&pp_rewrite=1 [NC,L,QSA]\n";
        }

        $htaccess_urls = [];

        $attachment_id_csv = implode("','", array_map('intval', array_keys($attachment_results)));

        // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $unfiltered_ids = $wpdb->get_col(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering'"
            . " AND meta_value = '0' AND post_id IN ('$attachment_id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        if ($pass_small_thumbs = presspermit()->getOption('small_thumbnails_unfiltered')) {
            $thumbnail_ids_csv = implode("','", array_map('intval', array_keys($attachment_results)));

            // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $thumb_filtered_ids = $wpdb->get_col(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pp_file_filtering'"
                . " AND meta_value = 'all' AND post_id IN ('$thumbnail_ids_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        } else {
            $thumb_filtered_ids = [];
        }

        $thumb_width = (int)get_option('thumbnail_size_w');
        $thumb_height = (int)get_option('thumbnail_size_h');

        foreach ($attachment_results as $row) {
            if (false === strpos($row->guid, $baseurl))  // no need to include any attachments which are not in the uploads folder
                continue;

            if (in_array($row->ID, $unfiltered_ids))
                continue;

            if (!empty($has_postmeta[$row->ID])) {
                $key = $has_postmeta[$row->ID];
            } else {
                $key = urlencode(str_replace('.', '', uniqid(strval(rand()), true)));  // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
            }

            if (!isset($has_postmeta[$row->ID]) || ($key != $has_postmeta[$row->ID])) {
                update_post_meta($row->ID, "_rs_file_key", $key);
                $has_postmeta[$row->ID] = $key;

                // Propagate the same key to any duplicate attachment records sharing the same file path
                // (e.g. orphaned records from site cloning/migration with a stale guid domain).
                $rel_path_for_sync = str_replace($baseurl, '', $row->guid);

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $duplicate_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s AND post_id != %d",
                        $rel_path_for_sync,
                        $row->ID
                    )
                );

                foreach ($duplicate_ids as $dup_id) {
                    update_post_meta((int)$dup_id, '_rs_file_key', $key);
                    $has_postmeta[(int)$dup_id] = $key;
                }
            }

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

            if (0 === strpos($row->post_mime_type, 'image') && $pos_ext = strrpos($file_path, '\.')) {
                $thumb_path = substr($file_path, 0, $pos_ext);
                $ext = substr($file_path, $pos_ext + 2);

                $new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '(|-scaled|-[0-9]{2,4}x[0-9]{2,4})\.' . $ext . "$ [NC]\n";  // covers main file and thumbnails that use standard naming pattern
                if ($pass_small_thumbs && !in_array($row->ID, $thumb_filtered_ids))
                    $new_rules .= "RewriteCond %{REQUEST_URI} !^(.*)" . $thumb_width . 'x' . $thumb_height . "\.jpg$ [NC]\n";

                $new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
                $new_rules .= $main_rewrite_rule;

                // if resized image file(s) exist, include rules for them
                $guid_pos_ext = strrpos($rel_path, '.');
                $pattern = $uploads['path'] . '/' . substr($rel_path, 0, $guid_pos_ext) . '-??????????????' . substr($rel_path, $guid_pos_ext);
                if (glob($pattern)) {
                    $new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$thumb_path" . '-[0-9,a-f]{14}\.' . $ext . "$ [NC]\n";
                    $new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
                    $new_rules .= $main_rewrite_rule;
                }
            } else {
                $new_rules .= "RewriteCond %{REQUEST_URI} ^(.*)/$file_path" . "$ [NC]\n";
                $new_rules .= "RewriteCond %{QUERY_STRING} !^(.*)rs_file_key=$key(.*)\n";
                $new_rules .= $main_rewrite_rule;
            }
        } // end foreach protected attachment

        if (is_multisite() && Network::msBlogsRewriting()) {
            $file_filtered_sites = (array)get_site_option('presspermit_file_filtered_sites');
            
            if (!in_array(get_current_blog_id(), $file_filtered_sites)) {
                // this site needs a file redirect rule in root .htaccess
                NetworkLegacy::flushMainRules();
            }
        }

        $new_rules .= "</IfModule>\n";

        return $new_rules;
    }

    // called by agp_returnFile() in abnormal cases where file access is approved, but key for protected file is lost/corrupted in postmeta record or .htaccess file
    public static function resyncFileRules()
    {
        // Don't allow this to execute too frequently, to prevent abuse or accidental recursion
        if (self::timeGMT() - get_option('presspermit_last_htaccess_resync') > 30) {
            update_option('presspermit_last_htaccess_resync', self::timeGMT());

            // Only the files / uploads .htaccess for current site is regenerated
            FileAccess::flushFileRules();

            usleep(10000); // Allow 10 milliseconds for server to regather itself following .htaccess update
        }
    }

    public static function timeGMT()
    {
        return strtotime(gmdate("Y-m-d H:i:s"));
    }
	
	/**
	 * Inserts an array of strings into a file (.htaccess ), placing it between
	 * BEGIN and END markers.
	 *
	 * Replaces existing marked info. Retains surrounding
	 * data. Creates file if none exists.
	 *
	 * @since 1.5.0
	 *
	 * @param string       $filename  Filename to alter.
	 * @param string       $marker    The marker to alter.
	 * @param array|string $insertion The new content to insert.
	 * @return bool True on write success, false on failure.
	 */
	private static function doInsertWithMarkers( $filename, $marker, $insertion ) {
		// phpcs Note: Already confirmed file is writable

        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_touch
        // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
        // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_ftruncate

        if ( ! file_exists( $filename ) ) {
			if ( ! is_writable( dirname( $filename ) ) ) {
				return false;
			}
			if ( ! touch( $filename ) ) {
				return false;
			}
		} elseif ( ! is_writable( $filename ) ) {
			return false;
        }

		if ( ! is_array( $insertion ) ) {
			$insertion = explode( "\n", $insertion );
		}

		$start_marker = "# BEGIN {$marker}";
		$end_marker   = "# END {$marker}";

		$fp = fopen( $filename, 'r+' );
		if ( ! $fp ) {
			return false;
		}

		// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
		flock( $fp, LOCK_EX );

		$lines = array();
		while ( ! feof( $fp ) ) {
			$lines[] = rtrim( fgets( $fp ), "\r\n" );
		}

		// Split out the existing file into the preceding lines, and those that appear after the marker
		$pre_lines    = $post_lines = $existing_lines = array();
		$found_marker = $found_end_marker = false;
		foreach ( $lines as $line ) {
			if ( ! $found_marker && false !== strpos( $line, $start_marker ) ) {
				$found_marker = true;
				continue;
			} elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) ) {
				$found_end_marker = true;
				continue;
			}
			if ( ! $found_marker ) {
				$pre_lines[] = $line;
			} elseif ( $found_marker && $found_end_marker ) {
				$post_lines[] = $line;
			} else {
				$existing_lines[] = $line;
			}
		}

		// Check to see if there was a change
		if ( $existing_lines === $insertion ) {
			flock( $fp, LOCK_UN );
			fclose( $fp );

			return true;
		}

		// Generate the new file data
		$new_file_data = implode(
			"\n",
			array_merge(
				$pre_lines,
				array( $start_marker ),
				$insertion,
				array( $end_marker ),
				$post_lines
			)
		);

		// Write to the start of the file, and truncate it to that length
		fseek( $fp, 0 );
		$bytes = fwrite( $fp, $new_file_data );
		if ( $bytes ) {
			ftruncate( $fp, ftell( $fp ) );
		}
		fflush( $fp );
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return (bool) $bytes;
	}
}
