<?php
namespace PublishPress\Permissions\Compat;

class BBPress {
    function __construct() {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Hooks.php');
        new BBPress\Hooks();

        if (PWP::isFront()) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/HooksFront.php');
            new BBPress\HooksFront();

        } elseif (is_admin()) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/HooksAdmin.php');
            new BBPress\HooksAdmin();
        }
    }

    public static function postTypeFromCaps($caps, $args = [])
    {
        $defaults = ['orig_cap' => '', 'item_type' => '', 'additional_caps' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $item_type = sanitize_key($item_type);

        static $bbp_caps;

        if (!isset($bbp_caps)) $bbp_caps = [];

        foreach (['forum', 'topic', 'reply'] as $_type) {
            if (!isset($bbp_caps[$_type])) {
                $type_obj = get_post_type_object($_type);
                $bbp_caps[$_type] = array_diff((array)$type_obj->cap, ['read', PRESSPERMIT_READ_PUBLIC_CAP]);
                if ($additional_caps) {
                    $bbp_caps[$_type] = array_merge($bbp_caps[$_type], (array)$additional_caps);
                }
            }

            if (array_intersect($bbp_caps[$_type], (array)$caps)) {
                $item_type = $_type;
                break;
            } elseif (array_intersect($bbp_caps[$_type], (array)$orig_cap)) {
                $item_type = $_type;
                break;
            }
        }

        return $item_type;
    }

    public static function getForumID($post_id, $orig_reqd_caps = [], $args = [])
    {
        if ((isset($args[0]) 
        && !self::postTypeFromCaps(
            array_map('sanitize_key', (array) $args[0]), 
            ['additional_caps' => 'participate']
        ))
        || !function_exists('bbp_get_forum_id')) {
            return $post_id;
        }

        if ($post_id) {
            if (bbp_is_reply($post_id))
                return bbp_get_reply_forum_id($post_id);

            elseif (bbp_is_topic($post_id))
                return bbp_get_topic_forum_id($post_id);

            elseif (bbp_is_forum($post_id))
                return $post_id;
        }

        if ($forum_id = bbp_get_forum_id()) {
            return $forum_id;
            
        } elseif (PWP::is_POST('action', ['bbp-new-topic', 'bbp-new-reply']) && !PWP::empty_POST('bbp_forum_id')) {
            return PWP::POST_int('bbp_forum_id');
        }

        return $post_id;
    }
}
