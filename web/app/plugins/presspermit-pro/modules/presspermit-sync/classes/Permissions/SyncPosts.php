<?php

namespace PublishPress\Permissions;

class SyncPosts
{
    private static $instance = null;
    private static $user_sync = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new SyncPosts();
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public static function userSync()
    {
        if (is_null(self::$user_sync)) {
            require_once(PRESSPERMIT_SYNC_CLASSPATH . '/UserSync.php');
            self::$user_sync = new SyncPosts\UserSync();
        }

        return self::$user_sync;
    }

    public static function userSyncLoaded()
    {
        return ! is_null(self::$user_sync);
    }

    public static function getEnabledTypes()
    {
        if ($enabled_types = array_keys(array_filter((array)presspermit()->getOption('sync_posts_to_users_types')))) {
            $enabled_types = array_intersect($enabled_types, get_post_types([], 'names'));
        }

        return $enabled_types;
    }

    public static function getAllowedPrivatePostTypes()
    {
        return apply_filters(
            'presspermit_sync_posts_to_users_private_types', 
            [
                'awsm_team_member', 
                'tmm', 
                'team_builder', 
                'team_member', 
                'staff', 
                'totalteam', 
                'cpt_staff_lst_item'
            ]
        );
    }

    // Enable some post types to be set public within wp-admin if user enables post_to_user sync for them.
    // This is necessary for synchronized users to edit their own post.
    public static function handlePrivateTypes()
    {
        global $wp_post_types;

        if ($enabled_types = self::getEnabledTypes()) {
            $handle_types = array_intersect($enabled_types, self::getAllowedPrivatePostTypes());

            foreach ($handle_types as $post_type) {
                if (!empty($wp_post_types[$post_type])) {
                    $wp_post_types[$post_type]->public = true;
                }
            }
        }
    }
}
