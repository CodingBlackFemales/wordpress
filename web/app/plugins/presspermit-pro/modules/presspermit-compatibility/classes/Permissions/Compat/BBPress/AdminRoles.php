<?php
namespace PublishPress\Permissions\Compat\BBPress;

class AdminRoles
{
    public static function fltCanSetExceptions($can, $operation, $for_item_type, $args = [])
    {
        if (('forum' != $for_item_type) || !in_array($operation, ['publish_topics', 'publish_replies'], true))
            return $can;

        $defaults = ['item_id' => 0, 'is_administrator' => false];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $item_id = (int) $item_id;

        if ($is_administrator)
            return true;

        if ($type_obj = get_post_type_object('forum')) {
            $user = presspermit()->getUser();

            // require edit_others_posts (unless this is a post-assigned exception for user's own post)
            if (!$can_edit = !empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                if (!$item_id)
                    $item_id = PWP::getPostID();

                $_post = ($item_id) ? get_post($item_id) : false;
                $can_edit = $_post && ($_post->post_author == $user->ID);
            }

            return $can_edit && current_user_can("pp_set_{$operation}_exceptions");
        }

        return $can;
    }
}
