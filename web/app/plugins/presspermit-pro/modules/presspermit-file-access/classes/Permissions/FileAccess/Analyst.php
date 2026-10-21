<?php
namespace PublishPress\Permissions\FileAccess;

/**
 * PP_Analyst class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2017, Agapetry Creations LLC
 *
 */

class Analyst
{
    public static function identifyProtectedAttachments()
    {
        global $wpdb;

        // note: currently, if a post type or taxonomy applies include exceptions, all posts will have their attachments protected

        $pp = presspermit();

        $pp_enabled_types = $pp->getEnabledPostTypes();
        $pp_enabled_taxonomies = $pp->getEnabledTaxonomies();

        $types_csv = implode("','", array_map('sanitize_key', $pp_enabled_types));

        // phpcs Note: Direct query on plugin, posts, terms tables for periodic indexing of posts that are read-restricted

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        // per-post 'exclude' exceptions
        $exc_posts = apply_filters(
            'presspermit_file_access_blocked_posts', 
            $wpdb->get_col(
                "SELECT i.item_id FROM $wpdb->ppc_exception_items AS i"
                . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id"
                . " AND e.via_item_source = 'post'"
                . " AND e.for_item_type IN ('$types_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND e.operation = 'read'"
                . " AND e.mod_type = 'exclude'" 
                . " AND ( e.for_item_type = 'attachment' OR i.item_id IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent > 0 ) )"
            )
        );

        // per-post 'include' exceptions (To simplify rule generation queries, handle include exceptions by treating all posts of related type as protected.  userCanReadFile() will clear up any false positives.)
        $inc_posts = apply_filters(
            'presspermit_file_access_post_inclusions_active',
            $exc_types = $wpdb->get_col(
                "SELECT DISTINCT e.for_item_type FROM $wpdb->ppc_exceptions AS e"
                . " INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id AND e.via_item_source = 'post'"
                . " AND e.operation = 'read' AND e.mod_type = 'include'"
                . " AND e.for_item_type IN ('$types_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            )
        );

        $exc_posts = apply_filters(
            'presspermit_file_access_protected_posts', 
            array_merge($exc_posts, $inc_posts)
        );

        $taxonomies_csv = implode("','", array_map('sanitize_key', $pp_enabled_taxonomies));

        // per-term 'include' exceptions
        $exc_taxonomies = $wpdb->get_results(
            "SELECT DISTINCT e.via_item_type, e.for_item_type FROM $wpdb->ppc_exceptions AS e"
            . " INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id"
            . " AND e.via_item_source = 'term'"
            . " AND e.operation = 'read'"
            . " AND e.mod_type = 'include'"
            . " AND e.via_item_type IN ('$taxonomies_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Get protected post types from per-term 'include' exceptions
        if ($exc_taxonomies) {
            $exc_taxonomies_by_type = [];
            foreach ($exc_taxonomies as $row) {  // for_item_type is post type, via_item_type is taxonomy
                $exc_taxonomies_by_type[$row->for_item_type][$row->via_item_type] = true;
            }

            if (!empty($exc_taxonomies_by_type[''])) {
                // get all related post types for each taxonomy that has universal include exceptions
                foreach (array_keys($exc_taxonomies_by_type['']) as $taxonomy) {
                    $tx_obj = get_taxonomy($taxonomy);
                    $exc_types = array_merge($exc_types, array_intersect((array)$tx_obj->object_type, $pp_enabled_types));
                }

                unset($exc_taxonomies_by_type['']);
            }

            $exc_taxonomies_by_type = apply_filters('presspermit_file_access_protected_taxonomies_by_type', $exc_taxonomies_by_type);

            $exc_types = array_merge($exc_types, array_intersect(array_keys($exc_taxonomies_by_type), $pp_enabled_types));
        }

        $exc_types = apply_filters('presspermit_file_access_protected_types', $exc_types);

        // per-term 'exclude' exceptions
        $exc_terms = $wpdb->get_results(
            "SELECT i.item_id, e.for_item_type FROM $wpdb->ppc_exception_items AS i "
            . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id"
            . " AND e.via_item_source = 'term'"
            . " AND e.operation = 'read'"
            . " AND e.mod_type = 'exclude'"
            . " AND e.via_item_type IN ('$taxonomies_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        $exc_terms = apply_filters('presspermit_file_access_blocked_terms', $exc_terms);

        // convert per-term excludes to per-post
        if ($exc_terms) {
            $term_assoc_where = [];

            $exc_terms_by_type = [];
            foreach ($exc_terms as $row) {
                $exc_terms_by_type[$row->for_item_type][$row->item_id] = true;
            }

            $exc_terms_by_type = apply_filters('presspermit_file_access_blocked_terms_by_type', $exc_terms_by_type);

            if (isset($exc_terms_by_type[''])) {
                $exc_terms_csv = implode("','", array_map('intval', array_keys($exc_terms_by_type[''])));

                $exc_posts = array_merge(
                    $exc_posts, 
                    $wpdb->get_col(
                        "SELECT ID FROM $wpdb->posts AS p"
                        . " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id"
                        . " WHERE 1=1 AND tr.term_taxonomy_id IN ('$exc_terms_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        . " AND ( p.post_type = 'attachment' OR p.ID IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' ) )"
                    )
                );

                $universal_terms = $exc_terms_by_type[''];
                unset($exc_terms_by_type['']);
            } else {
                $universal_terms = [];
            }

            foreach (array_keys($exc_terms_by_type) as $for_type) {
                if ($exc_terms_by_type[$for_type] = array_diff_key($exc_terms_by_type[$for_type], $universal_terms)) {
                    $exc_terms_csv = implode("','", array_map('intval', array_keys($exc_terms_by_type[$for_type])));

                    $exc_posts = array_merge(
                        $exc_posts, 
                        $wpdb->get_col(
                            $wpdb->prepare(
                                "SELECT ID FROM $wpdb->posts AS p"
                                . " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id"
                                . " WHERE 1=1 AND p.post_type = %s AND tr.term_taxonomy_id IN ('$exc_terms_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                . " AND ( p.post_type = 'attachment' OR p.ID IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' ) )",

                                $for_type
                            )
                        )
                    );
                }
            }

            $exc_posts = apply_filters('presspermit_file_access_term_blocked_posts', $exc_posts);
        }

        if ($exc_types) {
            $exc_types_csv = implode("','", array_map('sanitize_key', array_unique($exc_types)));
        } else {
            $exc_types_csv = '';
        }

        if ($exc_posts) {
            $exc_posts_csv = implode("','", array_map('intval', array_unique($exc_posts)));
        } else {
            $exc_posts_csv = '';
        }

        $status_csv = implode("','", array_map(
            'sanitize_key', 
             array_merge(
                get_post_stati(['private' => true]),
                get_post_stati(['moderation' => true]),
                ['draft', 'pending', 'future', 'trash']
            ))
        );

        // Special Case: per-file 'additional' exceptions for wp_all metagroup.  These will override implicit privacy (based on parent post) as long as no corresponding per-post exclude exceptions are directly set for the file.
        $all_group = $pp->groups()->getMetagroup('wp_role', 'wp_all');

        $types_csv = implode("','", array_map('sanitize_key', $pp_enabled_types));

        $public_attachments = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT i.item_id FROM $wpdb->ppc_exception_items AS i"
                . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id"
                . " AND e.agent_type = 'pp_group'"
                . " AND e.agent_id = %d"
                . " AND e.via_item_source = 'post'"
                . " AND e.operation = 'read'"
                . " AND e.mod_type = 'additional'"
                . " AND ( e.for_item_type = 'attachment' )"
                . " AND e.for_item_type IN ('$types_csv')",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                
                $all_group->ID
            )
        );
        
        if ($public_attachments = array_diff($public_attachments, $exc_posts)) {
            $public_id_csv = implode("','", array_map('intval', $public_attachments));
        } else {
            $public_id_csv = '';
        }

        // TODO: settings UI
        if (defined('PPFF_EXCLUDE_MIME_TYPES')) {
            $mime_types_csv = implode("','", array_map('sanitize_key', explode(",", PPFF_EXCLUDE_MIME_TYPES)));
            $exclude_mime_types = " AND post_mime_type NOT IN ('$mime_types_csv')";
        } else {
            $exclude_mime_types = '';
        }

        if (defined('PPFF_INCLUDE_MIME_TYPES')) {
            $mime_types_csv = implode("','", array_map('sanitize_key', explode(",", PPFF_INCLUDE_MIME_TYPES)));
            $include_mime_types = " AND post_mime_type IN ('$mime_types_csv')";
        } else {
            $include_mime_types = '';
        }

        // phpcs Note: query clauses constructed and sanitized above

        // ==== account for parent post permissions ====
        $results = apply_filters(
            'presspermit_file_access_all_protected_posts',
            $wpdb->get_results(
                "SELECT ID, guid, post_mime_type FROM $wpdb->posts"
                . " WHERE post_type = 'attachment' $exclude_mime_types $include_mime_types" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND ID NOT IN ('$public_id_csv')"                                       // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND ( post_parent IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts"
                . " WHERE $wpdb->posts.post_status IN ('$status_csv')"                      // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " OR post_type IN ('$exc_types_csv') OR ID IN ('$exc_posts_csv') ) )"     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " ORDER BY ID DESC", 
            
                OBJECT_K
            )
        );

        // ==== account for direct file exceptions ====
        if ($pp->getOption('unattached_files_private') && $pp->getOption('attached_files_private'))
            $attachment_clause = ' OR 1=1';
        elseif ($pp->getOption('unattached_files_private'))
            $attachment_clause = ' OR post_parent < 1';
        elseif ($pp->getOption('attached_files_private'))
            $attachment_clause = ' OR post_parent > 0';
        else
            $attachment_clause = '';

        // phpcs Note: query clauses constructed and sanitized above

        if ($_results = apply_filters(
            'presspermit_file_access_directly_protected_attachments',
            $wpdb->get_results(
                "SELECT ID, guid, post_mime_type FROM $wpdb->posts"
                . " WHERE post_type = 'attachment' $exclude_mime_types $include_mime_types"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND ID NOT IN ('$public_id_csv')"                                       // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND ( ID IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts"
                . " WHERE $wpdb->posts.post_status IN ('$status_csv')"                      // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " OR post_type IN ('$exc_types_csv')"                                     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " OR ID IN ('$exc_posts_csv') ) $attachment_clause )"                     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " ORDER BY ID DESC", OBJECT_K)
            )
        ) {    
            $results = $results + $_results;
        }

        if ($results) {
            $uploads_ok = (($uploads = wp_upload_dir()) && false === $uploads['error']);

            if ($uploads_ok) {
                $id_csv = implode("','", array_map('intval', array_keys($results)));

                $attached_file_meta = $wpdb->get_results(
                    "SELECT post_id, meta_value FROM $wpdb->postmeta "
                    . "WHERE meta_key = '_wp_attached_file' AND post_id IN ('$id_csv')"     // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                );

                // now replace guid with _wp_attached_file postmeta, if stored and valid 
                foreach ($attached_file_meta AS $row) {
                    $filename = $row->meta_value;

					// For rewrite rules login, don't use "-scaled" as the base filename
                    if (strpos($filename, '-scaled.') && !empty($results[$row->post_id]) && !strpos($results[$row->post_id]->guid, '-scaled.')) {
                        continue;
                    }

                    if (false === strpos($filename, $uploads['baseurl'])) {
                        $filename = trailingslashit($uploads['baseurl']) . $row->meta_value;
                    }

                    if (file_exists(str_replace($uploads['baseurl'], $uploads['basedir'], $filename))) {
                        $results[$row->post_id]->guid = $filename;
                    }
                }
            }
        }

        return $results;
    }
    
    public static function exceptionsAffectAttachments($eitem)
    {
        if (('post' == $eitem['for_item_source']) && ('read' == $eitem['operation']) && ('additional' != $eitem['mod_type'])) {
            if ('post' == $eitem['via_item_source']) {
                if ('attachment' == $eitem['for_item_type'])  // exception assigned directly to an attachment
                    return true;

                if (!$eitem['item_id'])  // exception assigned for "none"
                    return true;

                static $posts_with_attachment;
                if (!isset($posts_with_attachment)) {
                    global $wpdb;

                    // phpcs Note: Direct query on posts table to support periodic indexing of read-restricted posts

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $posts_with_attachment = $wpdb->get_col("SELECT DISTINCT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent > 0");
                }

                if (in_array($eitem['item_id'], $posts_with_attachment)) {
                    return true;
                }
            } elseif ('term' == $eitem['via_item_source']) {
                return true;
            }
        }

        return false;
    }
}
