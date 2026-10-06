<?php
namespace PublishPress\Statuses\StatusControl\Revisionary;

class CapabilityFilters 
{
    function __construct() {
        add_filter('rvy_replace_post_edit_caps', [$this, 'flt_replace_post_edit_caps'], 10, 3);
    }

    function flt_replace_post_edit_caps($caps, $post_type, $post_id)
    {
        $attributes = PPS::attributes();

        if (empty($attributes->all_custom_condition_caps[$post_type]))
            return $caps;

        if ($type_obj = get_post_type_object($post_type)) {
            if (isset($attributes->all_moderation_caps[$post_type][$type_obj->cap->edit_posts]))
                $caps = array_merge($caps, $attributes->all_moderation_caps[$post_type][$type_obj->cap->edit_posts]);
        }

        return array_unique($caps);
    }
}
