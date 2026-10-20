<?php
namespace PublishPress\Permissions\Compat\BBPress;

class Helper
{
    public static function bbp_role_caps($caps, $role)
    {
        if (!in_array($role, ['bbp_participant', 'bbp_spectator', 'bbp_moderator', 'bbp_keymaster'], true) || did_action('bbp_deactivate'))
            return $caps;
    
        if ($customized = (array)get_option('pp_customized_roles')) {
            if (isset($customized[$role]) && !empty($customized[$role]->caps)) {
                $caps = $customized[$role]->caps;
            }
        }
    
        return $caps;
    }

    // Force BBpress to include private subforums in the count so we have a chance to filter them into the list (or not) 
    // based on supplemental role assignment.
    public static function flt_count_private_subforums($forum_count, $forum_id)
    {
        static $children;
        if (!isset($children)) {
            global $wpdb;

            // phpcs Note: Direct query to support permissions filtering of bbPress private subforums count

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($forums = $wpdb->get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'forum'")) {
                foreach ($forums as $forum) {
                    if ($forum->post_parent)
                        $children[$forum->post_parent][] = $forum->ID;
                }
            }
        }

        if (isset($children[$forum_id]))
            $forum_count = count($children[$forum_id]);

        return $forum_count;
    }

    public static function flt_include_topic($where, $query)
    {
        // Bail if no post_parent to replace
        if (!is_numeric($query->query_vars['post_parent']))
            return $where;

        // Bail if not a topic and reply query
        if (array_diff([bbp_get_topic_post_type(), bbp_get_reply_post_type()], (array) $query->query_vars['post_type'])) {
            return $where;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'posts';
        $topic_id = bbp_get_topic_id();

        $search = " AND {$table_name}.post_parent = {$topic_id}";
        $replace = " AND ({$table_name}.ID = {$topic_id} OR {$table_name}.post_parent = {$topic_id})";

        if (strpos($where, $replace)) // indicates bbPress has already applied the filtering
            return $where;

        if ($new_where = str_replace($search, $replace, $where))
            $where = $new_where;

        return $where;
    }
}
