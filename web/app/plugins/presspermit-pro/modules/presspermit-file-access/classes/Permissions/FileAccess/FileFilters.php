<?php
namespace PublishPress\Permissions\FileAccess;

class FileFilters
{
    public static function userCanReadFile($file, &$return_attachment_id, &$matched_published_post, $uploads = '')
    {
        global $wpdb, $current_user;

        $pp = presspermit();

        // don't filter the direct file URL request if filtering is disabled, or if the request is from wp-admin
        if (!presspermit()->filteringEnabled()) {
            return true;
        }

        $user = presspermit()->getUser();

        if ($can_read = $pp->isContentAdministrator()) {
            return true;
        }

        $results = self::getFilePosts($file, compact('uploads'));

        $matched_published_post = [];
        $return_attachment_id = 0;

        $listed_post_tt_ids = [];

        // optional wp-admin perf enhancement for logged Authors / Editors: no file filtering queries
        if (!$can_read && defined('PP_NO_ADMIN_FILE_FILTERING') && !empty($current_user) && !empty($current_user->allcaps['upload_files'])) {
            if (!empty($_SERVER['HTTP_REFERER']) && (false !== strpos(esc_url_raw($_SERVER['HTTP_REFERER']), get_option('siteurl') . '/wp-admin'))) {
                $can_read = true;
            }
        }

        if (empty($results)) {
            $type_obj = get_post_type_object('attachment');
            return $can_read || !empty($current_user->allcaps[$type_obj->cap->edit_others_posts]);
        } else {
            $media_filtered = in_array('attachment', $pp->getEnabledPostTypes(), true);

            $post_ids = [];
            foreach ($results as $attachment) {
                $post_ids []= $attachment->ID;
            }

            $post_id_csv = implode("','", $post_ids);

            foreach ($results as $attachment) {
                $check_id = 0;

                // If multiple attachment records matched the filename but the current result has no key stored, continue to next result 
                if (count($results) > 1) {
                    if (!isset($result_num)) $result_num = 0;
                    $result_num++;

                    if ($result_num < count($results) && !$key = get_post_meta($attachment->ID, '_rs_file_key')) {
                        continue;
                    }
                }

                // exceptions assigned directly for file
                if ($media_filtered && !$can_read) {
                    $user->retrieveExceptions('read', 'post', ['post_types' => 'attachment', 'via_item_source' => 'post', 'item_id' => $attachment->ID, 'assign_for' => 'item']);

                    foreach (['include', 'exclude', 'additional'] as $mod) {
                        if (isset($user->except['read_post']['post'][''][$mod]['attachment']['']) && in_array($attachment->ID, $user->except['read_post']['post'][''][$mod]['attachment'][''])) {
                            $check_id = $attachment->ID;
                            break;
                        }
                    }
                        
                    if (empty($check_id)) {
                        // exceptions assigned for a term which the file has assigned to it
                        $user->retrieveExceptions('read', 'post', ['post_types' => 'attachment', 'via_item_source' => 'term', 'assign_for' => 'item']);
                        
                        if (!empty($user->except['read_post']['term'])) {
                            foreach(array_keys($user->except['read_post']['term']) as $taxonomy ) {
                                if (!empty($user->except['read_post']['term'][$taxonomy]['additional']['attachment'][''])) {
                                    if (!isset($listed_post_tt_ids[$taxonomy])) {

                                        // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve posts without further filtering

                                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                        $results = $wpdb->get_results(
                                            "SELECT object_id, term_taxonomy_id"                                    // phpcs Note: Post ID clause construced and sanitized above
                                            . "FROM $wpdb->term_relationships WHERE object_id IN ('$post_id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                        );

                                        foreach($results as $row) {
                                            $listed_post_tt_ids[$taxonomy][$row->object_id][] = $row->term_taxonomy_id;
                                        }
                                    }

                                    if (
                                        !empty($listed_post_tt_ids[$taxonomy][$row->object_id]) 
                                        && array_intersect(
                                            $listed_post_tt_ids[$taxonomy][$row->object_id], 
                                            $user->except['read_post']['term'][$taxonomy]['additional']['attachment']['']
                                        )
                                    ) {
                                        $check_id = $attachment->ID;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $user->except = [];
                }

                if (empty($check_id)) {
                    if (!$attachment->post_parent && defined('PRESSPERMIT_ATTACHMENT_POSTMETA_QUERY')) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        if ($post_parent_id = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = %d ORDER BY post_id LIMIT 1",
                                $attachment->ID
                            )
                        )) {
                            $attachment->post_parent = $post_parent_id;
                        }
                    }

                    if ($attachment->post_parent) {
                        if ($parent_post = get_post($attachment->post_parent)) {
                            // Only return content that is attached to published (potentially including private) posts/pages
                            // If some other statuses for published posts are introduced in later WP versions, 
                            // the failure mode here will be to overly suppress attachments
                            $status_obj = get_post_status_object($parent_post->post_status);

                            if (empty($status_obj) || ($status_obj->internal && ('trash' != $parent_post->post_status))) {  // allow for preview viewing when parent post is unpublished but editable, viewing attachments to trashed posts if they have exceptions directly assigned
                                continue;
                            }

                            $matched_published_post[$parent_post->post_type] = $attachment->post_name;

                            $check_id = $attachment->post_parent;
                        }
                    }
                }

                if ($can_read || current_user_can("read_post", $check_id) || current_user_can("edit_post", $check_id)) {  // edit_post allowance is for preview viewing when parent post is unpublished but editable
                    $return_attachment_id = $attachment->ID;
                    break;

                // Teaser: pass through file under some conditions
                } elseif (defined('PRESSPERMIT_TEASER_VERSION') && !defined('PPTX_DISABLE_THUMB_PASSTHRU')) {
                    // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve posts without further filtering

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    if ($thumbnail_post_ids = $wpdb->get_col(
                        $wpdb->prepare(
                            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
                            $attachment->ID
                        )
                    )) {
                        $teased_post_types = apply_filters('presspermit_teased_post_types', []);

                        $teased_types_csv = implode("','", array_map('sanitize_key', $teased_post_types));
                        $thumbnail_ids_csv = implode("','", array_map('intval', $thumbnail_post_ids));

                        // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve posts without further filtering

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        if ($thumbnail_posts = $wpdb->get_results(
                            "SELECT DISTINCT post_type, post_status FROM $wpdb->posts"
                            . " WHERE post_type IN ('$teased_types_csv') AND ID IN ('$thumbnail_ids_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        )) {
                            $valid_stati = get_post_stati(['public' => true], 'names');

                            // also account for posts/pages which are teased due to private visibility (but not if all relevant private posts/pages are hidden)
                            if ($pp->getOption('teaser_hide_custom_private_only')) {
                                $valid_stati[] = 'private';
                            }

                            $private_stati = get_post_stati(['private' => true], 'names');

                            // "Featured Image" / "Private Posts" are single global teaser
                            // settings now (see issue #2518), not per-post-type.
                            $hide_thumbnail = $pp->getOption('teaser_opt_hide_thumbnail');
                            $public_posts_only = $pp->getOption('teaser_opt_public_posts_only');

                            foreach ($thumbnail_posts as $_post) {
                                if ($hide_thumbnail) {
                                    continue; // Skip passthrough if hiding thumbnails
                                }

                                if (in_array($_post->post_status, $valid_stati, true) || (in_array($_post->post_status, $private_stati, true) && !$public_posts_only)) {
                                    $teaser_passthrough = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!empty($teaser_passthrough)) {
                        $return_attachment_id = $attachment->ID;
                        break;
                    }
                }
            }
        }

        if (!empty($attachment) && !$matched_published_post) {
           $matched_published_post['attachment'] = $attachment->post_name;
        }

        return !empty($return_attachment_id);
    }

    // handle access to uploaded file where request was a direct file URL, which was rewritten according to our .htaccess addition
    public static function parseQueryForDirectAccess(&$query)
    {
        $file = $query->query_vars['attachment'];

        $uploads = FileAccess::getUploadInfo();

        $return_attachment_id = 0;
        $matched_published_post = [];
        if (self::userCanReadFile($file, $return_attachment_id, $matched_published_post, $uploads)) {
            self::returnFile($file, $return_attachment_id);
            return;
        }

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/FileDenial.php');
        FileDenial::response404($file, $matched_published_post, $uploads, $return_attachment_id);
    }

    private static function getFilePostIds($file, $args = [])
    {
        $ids = [];

        if ($results = self::getFilePosts($file, $args)) {
            foreach ($results as $row)
                $ids [] = $row->ID;
        }

        return $ids;
    }

    private static function getFilePosts($file, $args = [])
    {
        $defaults = ['uploads' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        global $wpdb;

        if (!is_array($uploads) || empty($uploads['basedir'])) {
            $uploads = FileAccess::getUploadInfo();
        }

        // auto-resized copies have -NNNxNNN suffix, but the base filename is stored as attachment.  Strip the suffix out for db query.
        $orig_file = preg_replace("/-[0-9]{2,4}x[0-9]{2,4}\./", '.', $file);

        // manually resized copies have -eNNNNNNNNNNNNN suffix, but the base filename is stored as attachment.  Strip the suffix out for db query.
        $orig_file = preg_replace("/-e[0-9]{13}\./", '.', $orig_file);

        $orig_file_url = $uploads['baseurl'] . "/$orig_file";

        $rewrite_base = $uploads['baseurl'];
        if (defined('PP_FILE_ROOT')) {
            $pos = strpos($rewrite_base, PP_FILE_ROOT);
            if ($pos) {
                $rewrite_base = substr($rewrite_base, $pos);
            }
        }

        $relative_path = str_replace(trailingslashit($rewrite_base), '', $orig_file_url);

        // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve a post without further filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s", $relative_path));

        $id_csv = ($post_ids) ? implode("','", array_map('intval', $post_ids)) : '';

        // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve a post without further filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_parent, post_name FROM $wpdb->posts WHERE post_type = 'attachment'"
                . " AND guid = %s OR ID IN ('$id_csv') ORDER BY post_modified_gmt DESC",   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                $orig_file_url
            ), 
            OBJECT_K
        );

        return $results;
    }

    private static function returnFile($file_path, $attachment_id = 0)
    {
        $uploads = FileAccess::getUploadInfo();

        if (false === strpos($file_path, $uploads['basedir'])) {
            $file_path = untrailingslashit($uploads['basedir']) . "/$file_path";
        }

        $file_url = str_replace(untrailingslashit($uploads['basedir']), untrailingslashit($uploads['baseurl']), $file_path);

        if (!$attachment_id) {
            global $wpdb;  // we've already confirmed that this user can read the file; if it is attached to more than one post any corresponding file key will do

            // Resized copies have -NNNxNNN suffix, but the base filename is stored as attachment.  Strip the suffix out for db query.
            $orig_file_url = preg_replace("/-[0-9]{2,4}x[0-9]{2,4}./", '.', $file_url);

            // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve a post without further filtering
            // Prefer attachments that already have a _rs_file_key to avoid picking orphaned records from cloned/migrated sites.

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT p.ID FROM $wpdb->posts p"
                    . " LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_rs_file_key'"
                    . " WHERE p.post_type = 'attachment' AND p.guid = %s"
                    . " ORDER BY (pm.meta_value IS NOT NULL) DESC LIMIT 1",
                    $orig_file_url
                )
            );

            // Fallback: guid may contain a different domain (e.g. on cloned/migrated sites),
            // or the guid-matched attachment may be an orphaned record with no file key (e.g. after migration).
            // Query by _wp_attached_file meta to find an attachment that has a file key.
            if (!$attachment_id || !get_post_meta($attachment_id, '_rs_file_key', true)) {
                $relative_path = str_replace(trailingslashit($uploads['baseurl']), '', $orig_file_url);

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($meta_attachment_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT pm1.post_id FROM $wpdb->postmeta pm1"
                        . " INNER JOIN $wpdb->postmeta pm2 ON pm1.post_id = pm2.post_id AND pm2.meta_key = '_rs_file_key'"
                        . " WHERE pm1.meta_key = '_wp_attached_file' AND pm1.meta_value = %s LIMIT 1",
                        $relative_path
                    )
                )) {
                    $attachment_id = $meta_attachment_id;
                }
            }

            if (!$attachment_id) {
                return;
            }
        }

        if (!$key = get_post_meta($attachment_id, '_rs_file_key')) {
            // The key was lost from DB, so regenerate it (and files / uploads .htaccess)
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
            RewriteRules::resyncFileRules();

            // If the key is still not available, fail out to avoid recursion
            if (!$key = get_post_meta($attachment_id, '_rs_file_key')) {
                exit(0);
            }
        } elseif (!empty($_SERVER['REQUEST_URI']) && strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'rs_file_key')) {
            // Apparantly, the .htaccess rules contain an entry for this file, but with invalid file key.  URL with this valid key already passed through RewriteRules.  
            // Regenerate .htaccess file in uploads folder, but don't risk recursion by redirecting again.  Note that Firefox browser cache may need to be cleared following this error.
            $last_resync = get_option('presspermit_last_htaccess_resync');
            if ((!$last_resync) || (time() - $last_resync > 3600)) {  // prevent abuse (mismatched .htaccess keys should not be a frequent occurance)
                update_option('presspermit_last_htaccess_resync', time());
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
                RewriteRules::resyncFileRules();
            }
            exit(0);  // If htaccess rewrite was instantaneous, we could just continue without this exit.  But settle for the one-time image access failure to avoid a redirect loop on delayed file update.
        }

        if (is_array($key))
            $key = reset($key);

        if (is_multisite() && get_site_option('ms_files_rewriting')) {
            $basedir = wp_parse_url($uploads['basedir']);
            $baseurl = wp_parse_url($uploads['baseurl']);

            global $base;

            $file_url = str_replace(ABSPATH, $baseurl['scheme'] . '://' . $baseurl['host'] . $base, $file_path);
            $file_url = str_replace('\\', '/', $file_url);
        }

        $redirect = $file_url . "?rs_file_key=$key";

        usleep(10);
        wp_redirect($redirect);
        exit(0);
    }
}
