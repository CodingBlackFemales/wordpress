<?php
namespace PublishPress\Permissions\Compat\BBPress\UI\Dashboard;

class Helper
{
    public static function dropdown_include_private_topics($query)
    {
        static $busy;
        if (!empty($busy)) {
            return $query;
        } else {
            $busy = true;
        }

        $type_obj = get_post_type_object('topic');
        if (current_user_can($type_obj->cap->read_private_posts)) {
            global $wpdb;
            $query = str_replace(
                "post_type = 'topic' AND ($wpdb->posts.post_status = 'publish')", 
                "post_type = 'topic' AND ($wpdb->posts.post_status IN ('publish','private'))", 
                $query
            );
        }

        $busy = false;
        return $query;
    }
}
