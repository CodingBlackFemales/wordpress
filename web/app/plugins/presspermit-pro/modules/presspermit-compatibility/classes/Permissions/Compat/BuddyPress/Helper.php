<?php
namespace PublishPress\Permissions\Compat\BuddyPress;

class Helper
{
    public static function roleCaps($caps, $role)
    {
        if ($customized = (array)get_option('pp_customized_roles')) {
            if (isset($customized[$role]) && !empty($customized[$role]->caps)) {
                $caps = $customized[$role]->caps;
            }
        }

        return $caps;
    }

    public static function fltHasActivities($bp_activities, $bp_activities_template)
    {
        $pp = presspermit();

        $wp_keys = [];
        foreach (array_keys($bp_activities_template->activities) as $key) {
            if ('new_blog_post' == $bp_activities_template->activities[$key]->type) {
                // log listed ids first to buffer current_user_can query results
                $wp_id = $bp_activities_template->activities[$key]->secondary_item_id;

                if ($post_type = get_post_field('post_type', $wp_id)) {
                    $pp->listed_ids[$post_type][$wp_id] = true;
                    $wp_keys[$key] = $wp_id;
                }
            }
        }

        $hidden_count = 0;
        foreach ($wp_keys as $key => $wp_id) {
            if (!current_user_can('read_post', $wp_id)) {
                unset($bp_activities_template->activities[$key]);
                $hidden_count++;
            }
        }

        if ($hidden_count) {
            $bp_activities_template->activities = array_values($bp_activities_template->activities);  // reset keys
            $bp_activities_template->activity_count -= $hidden_count;
        }

        return $bp_activities;
    }

    public static function fltContent($content)
    {
        if ($bp_page = bp_current_component()) {
            $pages = bp_core_get_directory_pages();

            if (isset($pages->$bp_page) && !current_user_can('read_post', $pages->$bp_page->id)) {
                // If access is denied, return regular DB-stored post_content (which can be edited for a custom error message)
                return get_post_field('post_content', $pages->$bp_page->id);
            }
        }

        return $content;
    }
}
