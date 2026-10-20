<?php
namespace PublishPress\Permissions;

class SyncHooks
{
    function __construct() 
    {
        if ( is_admin() ) {
            require_once(PRESSPERMIT_SYNC_CLASSPATH . '/UI/Dashboard.php');
            new SyncPosts\UI\Dashboard();
        }

        add_filter('presspermit_default_options', [$this, 'fltDefaultOptions']);

        if (is_multisite()) {
            add_action('add_user_to_blog', [$this, 'actNewUser'], 10, 3);
        } else {
            add_action('user_register', [$this, 'actNewUser']);
        }
    }

    function fltDefaultOptions($def)
    {
        $new = [
            'sync_posts_to_users' => 0,
            'sync_posts_to_users_apply_permissions' => 1,
            'reveal_author_col' => 0,
            'sync_posts_to_users_types' => [], // Legacy option - kept for backward compatibility
            'sync_posts_to_users_new' => [], // New option for new user sync
            'sync_posts_to_users_existing' => [], // New option for existing user sync
            'sync_posts_to_users_post_field' => [],
            'sync_posts_to_users_user_field' => [],
            'sync_posts_to_users_role' => [],
            'sync_posts_to_users_post_parent' => [],
            'sync_posts_to_users_quantity' => [],
            'sync_posts_to_users_status' => [],
        ];

        return array_merge($def, $new);
    }

    function actNewUser($user_id, $role_name = '', $blog_id = '')
    {
        if (presspermit()->getOption('sync_posts_to_users')) {
            $sync_posts = \PublishPress\Permissions\SyncPosts::instance();
            $sync_posts->handlePrivateTypes();
            $sync_posts->userSync()->newUser($user_id, $role_name, $blog_id);
        }
    }
}
