<?php
namespace PublishPress\Permissions\Compat;

/**
 * PP_Analyst class
 *
 * @author Kevin Behrens
 * @copyright Copyright (c) 2024, PublishPress
 *
 */

class Analyst
{
    public static function identify_restricted_posts($args = [])
    {
        global $wpdb;

        $pp = presspermit();

        $defaults = ['identify_private_posts' => true];
        foreach ($defaults as $key => $val) {
            $$key = (isset($args[$key])) ? $args[$key] : $val;
        }

        $pp_enabled_types = $pp->getEnabledPostTypes();
        $pp_enabled_taxonomies = $pp->getEnabledTaxonomies();

        if (!$pp_enabled_types) return [];

        // phpcs Note: Direct queries on plugin, posts, terms tables for periodic indexing of read-restricted posts

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        $types_csv = implode("','", array_map('sanitize_key', $pp_enabled_types));

        // per-post 'exclude' exceptions
        $exc_posts = $wpdb->get_col(
            "SELECT i.item_id FROM $wpdb->ppc_exception_items AS i"
            . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id AND e.via_item_source = 'post'"
            . " AND e.operation = 'read' AND e.mod_type = 'exclude' AND e.for_item_type IN ('$types_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // per-post 'include' exceptions (To simplify rule generation queries, handle include exceptions 
        // by treating all posts of related type as protected.  userCanReadFile() will clear up any false positives.)
        $exc_types = $wpdb->get_col(
            "SELECT DISTINCT e.for_item_type FROM $wpdb->ppc_exceptions AS e"
            . " INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id"
            . " AND e.via_item_source = 'post' AND e.operation = 'read'"
            . " AND e.mod_type = 'include' AND e.for_item_type IN ('$types_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // per-term 'include' exceptions
        if ($pp_enabled_taxonomies) {
            $taxonomies_csv = implode("','", array_map('sanitize_key', $pp_enabled_taxonomies));

            $exc_taxonomies = $wpdb->get_results(
                "SELECT DISTINCT e.via_item_type, e.for_item_type FROM $wpdb->ppc_exceptions AS e"
                . " INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id"
                . " AND e.via_item_source = 'term' AND e.operation = 'read'"
                . " AND e.mod_type = 'include' AND e.via_item_type IN ('$taxonomies_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
                        $exc_types = array_merge(
                            $exc_types, 
                            array_intersect((array)$tx_obj->object_type, $pp_enabled_types)
                        );
                    }

                    unset($exc_taxonomies_by_type['']);
                }

                $exc_types = array_merge(
                    $exc_types, 
                    array_intersect(array_keys($exc_taxonomies_by_type), $pp_enabled_types)
                );
            }

            // per-term 'exclude' exceptions
            $exc_terms = $wpdb->get_results(
                "SELECT i.item_id, e.for_item_type FROM $wpdb->ppc_exception_items AS i"
                . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id"
                . " AND e.via_item_source = 'term' AND e.operation = 'read'"
                . " AND e.mod_type = 'exclude' AND e.via_item_type IN ('$taxonomies_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );

            // convert per-term excludes to per-post
            if ($exc_terms) {
                $term_assoc_where = [];

                $exc_terms_by_type = [];
                foreach ($exc_terms as $row) {
                    $exc_terms_by_type[$row->for_item_type][$row->item_id] = true;
                }

                if (isset($exc_terms_by_type[''])) {
                    $ttid_csv = implode("','", array_map('intval', array_keys($exc_terms_by_type[''])));

                    $exc_posts = array_merge(
                        $exc_posts, $wpdb->get_col(
                            "SELECT ID FROM $wpdb->posts AS p"
                            . " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id"
                            . " WHERE 1=1 AND tr.term_taxonomy_id IN ('$ttid_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        )
                    );

                    // if a term has an exclude exception for all post types, no need to also query for specific types
                    $universal_terms = $exc_terms_by_type[''];
                    unset($exc_terms_by_type['']);
                } else
                    $universal_terms = [];

                foreach (array_keys($exc_terms_by_type) as $for_type) {
                    if ($exc_terms_by_type[$for_type] = array_diff_key($exc_terms_by_type[$for_type], $universal_terms)) {
                        $ttid_csv = implode("','", array_map('intval', array_keys($exc_terms_by_type[$for_type])));

                        $exc_posts = array_merge(
                            $exc_posts, $wpdb->get_col(
                                $wpdb->prepare(
                                    "SELECT ID FROM $wpdb->posts AS p"
                                    . " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id"
                                    . " WHERE 1=1 AND p.post_type = %s AND tr.term_taxonomy_id IN ('$ttid_csv')",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                                    $for_type
                                )
                            )
                        );
                    }
                }
            }
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

        $att_where = '';
        $attachment_clause = '';

        $status_csv = ($identify_private_posts) 
        ? implode("','", array_map('sanitize_key', get_post_stati(['private' => true])))
        : "''";

        if (defined('PRESSPERMIT_FILE_ACCESS_VERSION ')) {
            // Special Case: per-file 'additional' exceptions for wp_all metagroup.
            //
            // These will override implicit privacy (based on parent post) as long as no corresponding 
            // per-post exclude exceptions are directly set for the file.
            $all_group = $pp->groups()->getMetagroup('wp_role', 'wp_all');

            $types_csv = implode("','", array_map('sanitize_key', $pp_enabled_types));

            $public_attachments = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT i.item_id FROM $wpdb->ppc_exception_items AS i"
                    . " INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id"
                    . " AND e.agent_type = 'pp_group' AND e.agent_id = %d AND e.via_item_source = 'post'"
                    . " AND e.operation = 'read' AND e.mod_type = 'additional'"
                    . " AND ( e.for_item_type = 'attachment' ) AND e.for_item_type IN ('$types_csv')",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                    $all_group->ID
                )
            );
            
            if ($public_attachments = array_diff($public_attachments, $exc_posts)) {
                $public_id_csv = implode("','", array_map('intval', $public_attachments));
            } else {
                $public_id_csv = '';
            }

            // ==== account for parent post permissions imposing restrictions on attachments ====
            $exc_posts = array_merge(
                $exc_posts, 
                $wpdb->get_col(
                    "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment'"
                    . " AND ID NOT IN ('$public_id_csv') AND ( post_parent IN"              // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    . " ( SELECT $wpdb->posts.ID FROM $wpdb->posts"
                    . " WHERE $wpdb->posts.post_status IN ('$status_csv')"                  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    . " OR post_type IN ('$exc_types_csv') OR ID IN ('$exc_posts_csv') )"   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    . " ) ORDER BY ID DESC", 
                    OBJECT_K
                )
            );

            if (defined('PPFF_EXCLUDE_MIME_TYPES')) {
                $mime_types_csv = implode("','", array_map('sanitize_key', explode(",", PPFF_EXCLUDE_MIME_TYPES)));
                $att_where .= " AND post_mime_type NOT IN ('" . $mime_types_csv . "')";
            }
    
            if (defined('PPFF_INCLUDE_MIME_TYPES')) {
                $mime_types_csv = implode("','", array_map('sanitize_key', explode(",", PPFF_INCLUDE_MIME_TYPES)));
                $att_where .= " AND post_mime_type IN ('" . $mime_types_csv . "')";
            }

            // ==== account for direct file exceptions ====
            if ($pp->getOption('unattached_files_private') && $pp->getOption('attached_files_private'))
                $attachment_clause = ' OR 1=1';
            elseif ($pp->getOption('unattached_files_private'))
                $attachment_clause = ' OR post_parent < 1';
            elseif ($pp->getOption('attached_files_private'))
                $attachment_clause = ' OR post_parent > 0';
        }

        // phpcs Note: Query clauses constructed and sanitized above

        $exc_posts = array_merge(
            $exc_posts, 
            $wpdb->get_col(
                "SELECT ID FROM $wpdb->posts WHERE 1=1 $att_where AND ("             // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " ID IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts"
                . " WHERE $wpdb->posts.post_status IN ('$status_csv')"               // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " OR post_type IN ('$exc_types_csv') OR ID IN ('$exc_posts_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " ) $attachment_clause ) ORDER BY ID DESC"                         // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                , OBJECT_K
            )
        );

        return $exc_posts;
    }
}
