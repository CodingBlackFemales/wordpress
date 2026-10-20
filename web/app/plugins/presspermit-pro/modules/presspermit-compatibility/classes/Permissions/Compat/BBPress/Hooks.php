<?php
namespace PublishPress\Permissions\Compat\BBPress;

class Hooks
{
    function __construct() {
        add_filter('presspermit_enabled_post_types', [$this, 'enabled_types']);
        add_filter('presspermit_unfiltered_post_types', [$this, 'enable_bbp_types'], 20);
        add_filter('presspermit_parent_types', [$this, 'flt_parent_types'], 10, 2);
        add_action('bbp_init', [$this, 'set_status_args'], 5);

        add_action('presspermit_pre_init', [$this, 'register_caps']);
        add_filter('bbp_get_user_role_map', [$this, 'role_map']);
        add_action('plugins_loaded', [$this, 'force_roles_supplemental']);

        add_filter('presspermit_get_post_id', ['\PublishPress\Permissions\Compat\BBPress', 'getForumID'], 10, 3);

        add_filter('presspermit_item_update_process_roles_args', [$this, 'item_update_process_roles_args'], 10, 4);

        add_action('presspermit_user_init', [$this, 'userInit']);
    }

    public function userInit() {
        if (!presspermit()->isContentAdministrator()) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/PostFilters.php');
            new PostFilters();
        }
    }

    public static function enabled_types($types)
    {
        if (in_array('forum', $types, true))
            $types = array_unique(array_merge($types, ['reply' => 'reply', 'topic' => 'topic', 'forum' => 'forum']));

        return $types;
    }

    function enable_bbp_types($types)
    {
        return array_diff($types, ['forum', 'topic', 'reply']);
    }

    public static function flt_parent_types($parent_types, $post_type)
    {
        switch ($post_type) {
            case 'reply':
                $parent_types [] = ['topic'];
                $parent_types = array_unique($parent_types);
                break;
            case 'topic':
                $parent_types [] = ['forum'];
                $parent_types = array_unique($parent_types);
                break;
        }

        return $parent_types;
    }

    function set_status_args()
    {
        global $wp_post_statuses;
        foreach (['closed', 'spam', 'orphan', 'hidden'] as $status) {
            if (isset($wp_post_statuses[$status]) && empty($wp_post_statuses[$status]->post_type)) {
                $wp_post_statuses[$status]->post_type = ['forum', 'topic', 'reply'];
            }
        }
    }

    function register_caps()
    {
        $pp = presspermit();
        $pp->capDefs()->all_type_caps['moderate'] = true;
        $pp->capDefs()->all_type_caps['throttle'] = true;
    }

    // bbPress >= 2.2
    function role_map($map)
    {
        if (version_compare(bbp_get_version(), '2.2', '>=')) {
            if (!$bbp_role_map = presspermit()->getOption('bbp_suppress_role_map'))
                $bbp_role_map = [];

            return array_merge($map, array_fill_keys(array_keys($bbp_role_map), ''));
        }
        return $map;
    }

    // bbPress >= 2.2
    function force_roles_supplemental()
    {
        if (function_exists('bbp_get_version') && version_compare(bbp_get_version(), '2.2', '>=')) {
            add_filter('presspermit_default_options', function($options)
            {
                $options = array_merge(
                    $options, 
                    ['pp_supplemental_role_defs' => 
                        ['bbp_participant', 
                        'bbp_spectator', 
                        'bbp_blocked', 
                        'bbp_keymaster', 
                        'bbp_moderator'
                        ]
                    ]
                );
                
                return $options;
            });
        }
    }

    function item_update_process_roles_args($args, $via_item_source, $for_item_source, $item_id)
    {
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // [ 'is_new', 'set_parent', 'last_parent', 'disallow_manual_entry' ]

        if ('post' == $via_item_source) {
            if (!$item_id) {
                return $args;
            }

            if (!$_post = get_post($item_id))
                return $args;

            $action = PWP::REQUEST_key('action');

            switch ($action) {
                case 'bbp-new-topic':
                    if ('topic' == $_post->post_type) {
                        $args['set_parent'] = PWP::REQUEST_int('bbp_forum_id');
                    }

                    $args['for_item_type'] = 'topic';

                    break;
                case 'bbp-new-reply':
                    if ('reply' == $_post->post_type) {
                        $args['set_parent'] = PWP::REQUEST_int('bbp_topic_id');
                    }

                    $args['for_item_type'] = 'reply';

                    break;
            }
        }

        return $args;
    }
}
