<?php
namespace PublishPress\Permissions\Compat\BBPress;

class PostFilters
{
    function __construct() {
        add_filter('presspermit_adjust_posts_where_clause', [$this, 'adjust_posts_where_clause'], 10, 4);
        add_filter('bbp_current_user_can_publish_topics', [$this, 'can_publish_topics']);
        
        add_filter('bbp_get_caps_for_role', [$this, 'role_caps'], 5, 2);
        add_action('presspermit_pre_init', [$this, 'define_bbp_read_cap']);

        add_filter('presspermit_user_has_cap_params', [$this, 'user_has_cap_params'], 10, 4);

        add_filter('presspermit_user_has_caps', [$this, 'user_has_caps'], 10, 3);
        add_filter('presspermit_has_post_cap_vars', [$this, 'has_post_cap_vars'], 10, 4);

        add_filter('presspermit_credit_cap_exception', [$this, 'credit_cap_exception'], 10, 2);

        add_filter('presspermit_cap_operation', [$this, 'cap_operation'], 20, 3);
        add_filter('user_has_cap', [$this, 'moderate_cap'], 200, 3);
    }

    function adjust_posts_where_clause($where, $where_orig, $post_type, $args)
    {
        if (!is_search()) {
            return $where;
        }

        if ('reply' == $post_type) {
            static $busy;

            if (!empty($busy)) {
                return $where;
            }

            $busy = true;

            $query_args = ['required_operation' => $args['required_operation'], 'post_types' => 'forum', 'skip_teaser' => true];

            global $wpdb;

            $query_args['has_cap_check'] = !empty($args['has_cap_check']);
            $request = \PublishPress\Permissions\PostFilters::constructPostsRequest(['fields' => "$wpdb->posts.ID"], $query_args);

            $busy = false;

            // phpcs Note: Direct query to support permissions filtering of bbPress posts (without additional filter applications)

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if (!$forum_ids = $wpdb->get_col(
                $request  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )) {
                return '1=2';
            }

            $forum_id_csv = implode("','", array_map('intval', $forum_ids));

            if (defined('PRESSPERMIT_BBP_NO_SUBQUERIES') && PRESSPERMIT_BBP_NO_SUBQUERIES) {
                // phpcs Note: Direct query to support permissions filtering of bbPress posts (without additional filter applications)

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if (!$topic_ids = $wpdb->get_col(
                    "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE post_parent IN ('$forum_id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                )) {
                    return '1=2';
                }

                $topic_id_csv = implode("','", array_map('intval', $topic_ids));
                $where = "1=1 AND ( post_type = 'reply' AND post_parent IN ('$topic_id_csv') )";
            } else {
                // phpcs Note: Direct subquery to support permissions filtering of The Events Calendar posts (without additional filter applications)

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $where = "1=1 AND ( post_type = 'reply' AND post_parent IN (
                    SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE post_parent IN ('$forum_id_csv')
                ) )";
            }
        }

        return $where;
    }

    function can_publish_topics($val)
    {
        if (!is_user_logged_in())
            return current_user_can('publish_topics');
        else
            return $val;
    }

    // Enforce usage of db-stored bbPress role customizations, if any. 
    // This will reinstate the last-stored CME rolecap customization even if the stored WP role is reset 
    // (which bbPress <= 2.1 does on activation) or circumvented (which bbPress > 2.2.1 could do in the future).
    function role_caps($caps, $role)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Helper.php');
        return Helper::bbp_role_caps($caps, $role);
    }

    // bbPress >= 2.2
    function define_bbp_read_cap()
    {
        // bbp grants implicit spectate cap to anon users; does not map any dynamic role for anon user
        if (!is_user_logged_in())
            return;

        if (function_exists('bbp_get_version') && version_compare(bbp_get_version(), '2.2', '>=')) {
            global $wp_post_types;
            if (isset($wp_post_types['forum'])) {
                $wp_post_types['forum']->cap->read = 'spectate';
                $wp_post_types['topic']->cap->read = 'spectate';
                $wp_post_types['reply']->cap->read = 'spectate';
            }
        }
    }

    public static function cap_operation($set_operation, $base_cap, $item_type)
    {
        if (!in_array($item_type, ['forum', 'reply', 'topic'], true))
            return $set_operation;

        if (in_array($base_cap, ['moderate', 'throttle'], true))
            return 'edit';

        if (('delete' == $base_cap) && in_array($item_type, ['topic', 'reply'], true) 
        && defined('PP_BBP_DELETE_VIA_MODERATION_EXCEPTION')
        ) {
            return 'edit';
        }

        $type_obj = get_post_type_object('topic');
        if ($base_cap == $type_obj->cap->publish_posts)
            return 'publish_topics';

        $type_obj = get_post_type_object('reply');
        if ($base_cap == $type_obj->cap->publish_posts)
            return 'publish_replies';

        return $set_operation;
    }

    public static function user_has_cap_params($params, $orig_reqd_caps, $args)
    {
        global $wp_query;
        if (empty($wp_query->queried_object)) {
            return $params;
        }

        $post_type = (!empty($args['item_type'])) ? $args['item_type'] : false;

        $return = [];

        if (!in_array($post_type, ['forum', 'topic', 'reply'], true)) {
            $post_type = \PublishPress\Permissions\Compat\BBPress::postTypeFromCaps($orig_reqd_caps, $args);
        }

        if (in_array($post_type, ['forum', 'topic', 'reply'], true)) {
            $return['item_id'] = \PublishPress\Permissions\Compat\BBPress::getForumID($args['item_id']);
            $return['item_type'] = 'forum';

            foreach (['topic', 'reply'] as $_post_type) {
                $type_obj = get_post_type_object($_post_type);

                if ($type_obj->cap->publish_posts == reset($orig_reqd_caps) 
                || $type_obj->cap->publish_posts == $args['orig_cap']
                ) {
                    $return['required_operation'] = $type_obj->cap->publish_posts;
                    $return['bbp_cap_handling'] = true;
                    $return['is_post_cap'] = true;

                    if ('participate' == reset($orig_reqd_caps)) {
                        $return['type_caps'] = [$type_obj->cap->publish_posts];
                    }

                    break;
                }
            }
        }

        return ($return) ? array_merge((array)$params, $return) : $params;
    }

    public static function has_post_cap_vars($force_vars, $wp_sitecaps, $pp_reqd_caps, $vars)
    {
        $defaults = ['post_type' => [], 'required_operation' => ''];
        $vars = array_merge($defaults, $vars);
        foreach (array_keys($defaults) as $var) {
            $$var = $vars[$var];
        }

        $return = [];

        if (in_array($post_type, ['forum', 'topic', 'reply'], true)) {
            $topic_obj = get_post_type_object('topic');

            if ($topic_obj->cap->publish_posts == reset($pp_reqd_caps)) {
                $return['required_operation'] = 'publish_topics';  // topics are created as children of forums
            } else {
                $reply_obj = get_post_type_object('reply');  // replies are created as children of topics
                if ($reply_obj->cap->publish_posts == reset($pp_reqd_caps))
                    $return['required_operation'] = 'publish_replies';
            }
        }

        return ($return) ? array_merge((array)$force_vars, $return) : $force_vars;
    }

    public static function user_has_caps($wp_sitecaps, $orig_reqd_caps, $params)
    {
        if (!empty($params['bbp_cap_handling'])) {
            $defaults = ['item_id' => 0, 'required_operation' => ''];
            $params = array_merge($defaults, $params);
            foreach (array_keys($defaults) as $var) {
                $$var = $params[$var];
            }

            if ($item_id && $required_operation) {
                $user = presspermit()->getUser();

                $fail = false;

                $_ids = $user->getExceptionPosts($required_operation, 'additional', 'forum');
                if (!in_array($item_id, $_ids)) {
                    // note: item_type is taxonomy here
                    if ($_ids = $user->getExceptionPosts($required_operation, 'include', 'forum')) {
                        if (!in_array($item_id, $_ids))
                            $fail = true;

                    } elseif ($_ids = $user->getExceptionPosts($required_operation, 'exclude', 'forum')) {
                        if (in_array($item_id, $_ids))
                            $fail = true;
                    }
                }

                if ($fail)
                    $wp_sitecaps = array_diff_key($wp_sitecaps, array_fill_keys($orig_reqd_caps, true));
            }
        }

        return $wp_sitecaps;
    }

    public static function credit_cap_exception($pass, $params)
    {
        if (isset($params['item_type']) && in_array($params['item_type'], ['forum', 'topic', 'reply'], true)) {
            $defaults = ['item_id' => 0, 'item_type' => '', 'type_caps' => [], 'required_operation' => ''];
            $params = array_merge($defaults, $params);
            foreach (array_keys($defaults) as $var) {
                $$var = $params[$var];
            }

            if (count($type_caps) == 1) {
                if ($required_operation) {
                    // note: item_type is taxonomy here
                    if ($_ids = presspermit()->getUser()->getExceptionPosts($required_operation, 'additional', 'forum')) {
                        if (!$item_id || in_array($item_id, $_ids)) {
                            $pass = true;
                        }
                    }
                }
            }
        }

        return $pass;
    }

    function moderate_cap($wp_sitecaps, $orig_reqd_caps, $args)
    {
        global $wp_query;
        if (empty($wp_query->queried_object)) {
            return $wp_sitecaps;
        }

        if (presspermit()->isContentAdministrator() || defined('PP_DISABLE_MODERATE_FILTER'))
            return $wp_sitecaps;

        $args = (array)$args;
        $orig_cap = reset($args);

        if (('moderate' != $orig_cap))
            return $wp_sitecaps;

        if (PWP::empty_POST() && !did_action('bbp_template_redirect'))
            return $wp_sitecaps;

        if (!$forum_id = bbp_get_forum_id()) {
            if ($topic_id = PWP::REQUEST_int('topic_id')) {
                $forum_id = bbp_get_topic_forum_id($topic_id);
                
            } elseif ($reply_id = PWP::REQUEST_int('reply_id')) {
                $forum_id = bbp_get_reply_forum_id($reply_id);
            }
        }

        if (!$forum_id)
            return $wp_sitecaps;

        $user = presspermit()->getUser();

        if ($args[1] != $user->ID) {
            return $wp_sitecaps;
        }

        if (@isset($user->except['edit_post']['post']['']['additional']['forum'][''])) {
            if (in_array($forum_id, $user->except['edit_post']['post']['']['additional']['forum'][''])) {
                $wp_sitecaps['moderate'] = true;
                return $wp_sitecaps;
            }
        }

        if (@isset($user->except['edit_post']['post']['']['exclude']['forum'][''])) {
            if (in_array($forum_id, $user->except['edit_post']['post']['']['exclude']['forum'][''])) {
                unset($wp_sitecaps['moderate']);
            }
        }

        if (@isset($user->except['edit_post']['post']['']['include']['forum'][''])) {
            if (!in_array($forum_id, $user->except['edit_post']['post']['']['include']['forum'][''])) {
                unset($wp_sitecaps['moderate']);
            }
        }

        return $wp_sitecaps;
    }
}
