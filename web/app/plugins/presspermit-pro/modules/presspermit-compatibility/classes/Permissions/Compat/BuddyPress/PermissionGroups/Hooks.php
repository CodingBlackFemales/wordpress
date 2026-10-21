<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

class Hooks
{
    function __construct() 
    {
        // otherwise these don't execute until plugins_loaded action.  We need BP table names set earlier for retrieval of groups for current user
        if (defined('BP_VERSION') && version_compare(BP_VERSION, '1.5-dev', '<')) {
            global $bp;
            $bp->table_prefix = bp_core_get_table_prefix();
            groups_setup_globals();
            $this->actLoad();
        } else {
            add_action('bp_core_setup_globals', [$this, 'actLoad']);
            add_action('presspermit_cap_filters', [$this, 'actApplyPageFilter']);
        }

        add_action('presspermit_roles_defined', [$this, 'actAnonCaps']);
        add_filter('presspermit_get_group', [$this, 'fltGetGroup'], 10, 3);
        add_filter('presspermit_get_groups', [$this, 'fltGetGroups'], 10, 4);
        add_filter('presspermit_get_group_members', [$this, 'fltGetMembers'], 10, 4);
        add_filter('presspermit_get_groups_for_user', [$this, 'fltGetGroupsForUser'], 10, 3);
        add_filter('presspermit_circle_members', [$this, 'fltCircleMembers'], 10, 3);
        add_action('groups_delete_group', [$this, 'actDeletedBuddypressGroup']);

        if (defined('BPGE_VERSION')) {
            add_action('pre_get_posts', [$this, 'actGetExtrasPages']);
        }

        add_filter('bp_user_can_create_groups', [$this, 'fltCanCreateGroups'], 10, 2);
    }

    function actLoad()
    {
        if ($is_admin = is_admin()) {
            $labels = ['name' => esc_html__('BuddyPress Groups', 'presspermmit-pro'), 'singular_name' => esc_html__('BuddyPress Group', 'presspermit-pro')];
            $labels['plural_name'] = $labels['name'];
        } else {
            $labels = [];
        }

        global $wpdb;
        $prefix = (is_multisite()) ? $wpdb->base_prefix : $wpdb->prefix;
        $schema = [
            'members' => [
                'members_table' => $prefix . 'bp_groups_members', 
                'col_member_user' => 'user_id', 
                'col_member_group' => 'group_id'
            ],

            'groups' => [
                'groups_table' => $prefix . 
                'bp_groups', 
                'col_group_id' => 'id', 
                'col_group_name' => 'name'
            ],
        ];

        // phpcs Note: Direct query for failsafe confirmation BP Groups table exists (@todo: alternate method?)

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if (!$wpdb->get_results(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                "{$prefix}bp_groups"
            )
        )) {
            return;
        }

        presspermit()->groups()->registerGroupType('bp_group', compact('labels', 'schema'));

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        //$labels = ($is_admin) ? ['name' => esc_html__('BP Moderators', 'ppbg'), 'singular_name' => esc_html__('BP Moderator', 'ppbg')] : [];
        //presspermit()->groups()->registerGroupType( 'bp_moderator', compact($labels) );

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        //$labels = ($is_admin) ? ['name' => esc_html__('BP Admins', 'ppbg'), 'singular_name' => esc_html__('BP Admin', 'ppbg')] : [];
        //presspermit()->groups()->registerGroupType( 'bp_administrator', compact($labels) );
    }

    function actAnonCaps()
    {
        global $current_user;
        if (empty($current_user->ID)) {
            $current_user->allcaps[PRESSPERMIT_READ_PUBLIC_CAP] = true;
            presspermit()->getUser()->allcaps[PRESSPERMIT_READ_PUBLIC_CAP] = true;
        }
    }

    function fltCanCreateGroups($can_create, $restricted) {
        return ($restricted) ? current_user_can('bp_create_groups') : $can_create;
    }

    function actGetExtrasPages(&$query_obj)
    {
        // phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
        if ($query_obj->is_main_query() && isset($query_obj->query_vars['post_type']) && ('gpages' == $query_obj->query_vars['post_type'])) {
            $query_obj->query_vars['suppress_filters'] = false;
        }
    }

    function actApplyPageFilter()
    {
        if (!presspermit()->isContentAdministrator()) {
            global $bp;

            if ($bp->current_component) {
                $page_ids = bp_core_get_directory_page_ids();

                if (isset($page_ids[$bp->current_component])) {
                    if (!current_user_can('read_post', $page_ids[$bp->current_component])) {
                        $bp->current_component = '';
                        bp_do_404();
                    }
                }
            }
        }
    }

    function fltCircleMembers($circle_members, $circle_type, $user_id)
    {
        static $group_members = [];

        $pp = presspermit();
        $all_post_types = $pp->getEnabledPostTypes();

        if ($bp_groups = $pp->groups()->getGroupsForUser($user_id, 'bp_group')) {
            $group_circles = apply_filters('presspermit_group_circles', [], 'bp_group', array_keys($bp_groups), $circle_type);

            foreach ($group_circles as $group_id => $circles) {
                if (!isset($group_members[$group_id]))
                    $group_members[$group_id] = $pp->groups()->getGroupMembers($group_id, 'bp_group', 'ids');

                $all_types = isset($circles[$circle_type]['']);
                foreach ($all_post_types as $post_type) {
                    if ($all_types || isset($circles[$circle_type][$post_type])) {
                        if (isset($circle_members[$post_type]))
                            $circle_members[$post_type] = array_merge($circle_members[$post_type], $group_members[$group_id]);
                        else
                            $circle_members[$post_type] = $group_members[$group_id];
                    }
                }
            }
        }

        return $circle_members;
    }

    function fltGetGroup($group_obj, $group_id, $group_type)
    {
        if ('bp_group' != $group_type)
            return $group_obj;

        if ($groups = $this->fltGetGroups([$group_obj], $group_type, compact('group_id')))
            return current($groups);
    }

    function fltGetGroups($groups, $group_type, $args = [])
    {
        if ('bp_group' != $group_type)
            return $groups;

        $defaults = ['group_id' => [], 'cols' => 'all'];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $group_id = (int) $group_id;

        if ($group_id)
            $group_id = (array)$group_id;

        if (version_compare(BP_VERSION, '1.5-dev', '<')) {
            if (!method_exists('BP_Groups_Group', 'get_all'))
                return $groups;

            $bp_groups = \BP_Groups_Group::get_all(null, null, false);  // third arg: only_public
        } else {
            $bp_args = ['type' => null, 'per_page' => null, 'show_hidden' => true, 'populate_extras' => false];
            if ($group_id)
                $bp_args['include'] = $group_id;

            if (!empty($args['search'])) $bp_args['search_terms'] = $args['search'];

            if (!function_exists('groups_get_groups'))
                $bp_groups = [];
            elseif ($bp_groups = groups_get_groups($bp_args))
                $bp_groups = $bp_groups['groups'];
        }

        $groups = [];
        if ($bp_groups) {
            if ('ids' == $cols) {
                foreach ($bp_groups as $row) {
                    if ($group_id && !in_array($row->id, $group_id))  // this is only necessary for BP < 1.5
                        continue;

                    $groups[] = $row->id;
                }
            } else {
                foreach ($bp_groups as $row) {
                    if ($group_id && !in_array($row->id, $group_id))
                        continue;

                    $groups[$row->id] = (object)[
                        'ID' => $row->id, 
                        'name' => $row->name, 
                        'slug' => $row->slug, 
                        'group_description' => $row->description, 
                        'metagroup_type' => '', 
                        'metagroup_id' => 0
                    ];
                }
            }
        }

        return $groups;
    }

    function fltGetMembers($members, $group_id, $group_type, $cols)
    {
        if ('ids' != $cols)
            return $members;

        switch ($group_type) {
            case 'bp_group':
                if (method_exists('BP_Groups_Member', 'get_group_member_ids'))
                    return \BP_Groups_Member::get_group_member_ids($group_id);
                break;
            case 'bp_mod':
                if (method_exists('BP_Groups_Member', 'get_group_moderator_ids'))
                    return \BP_Groups_Member::get_group_moderator_ids($group_id);
                break;
            case 'bp_admin':
                if (method_exists('BP_Groups_Member', 'get_group_administrator_ids'))
                    return \BP_Groups_Member::get_group_administrator_ids($group_id);
                break;
        }

        return $members;
    }

    function fltGetGroupsForUser($group_ids, $user_id, $group_type)
    {
        if (!$user_id)
            return $group_ids;

        if (!in_array($group_type, ['bp_group', 'bp_mod', 'bp_admin'], true))
            return $group_ids;

        if (version_compare(BP_VERSION, '1.5-dev', '<')) {
            if (!method_exists('BP_Groups_Group', 'get_active'))
                return $group_ids;

            $results = BP_Groups_Group::get_active(null, null, $user_id);
        } else {
            $args = ['user_id' => $user_id, 'type' => null, 'per_page' => null, 'show_hidden' => true, 'populate_extras' => false];

            $results = (function_exists('groups_get_groups')) ? groups_get_groups($args) : [];
        }

        $group_ids = [];

        if (isset($results['groups'])) {
            switch ($group_type) {
                case 'bp_group':
                    $require_mod = defined('PPBP_GROUP_MODERATORS_ONLY') && PPBP_GROUP_MODERATORS_ONLY;
                    $require_admin = defined('PPBP_GROUP_ADMINS_ONLY') && PPBP_GROUP_ADMINS_ONLY;

                    foreach ($results['groups'] as $row) {
                        if ($require_admin && !$row->is_admin)
                            continue;

                        if ($require_mod && !$row->is_admin && !$row->is_mod)
                            continue;

                        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                        //if ( ! $row->is_mod && ! $row->is_admin ) {
                            
                        $group_ids [$row->id] = (isset($row->date_modified)) ? $row->date_modified : $row->date_created;

                        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                        //}
                    }
                    break;

                default:
                    $var = ('bp_admin' == $group_type) ? 'is_admin' : 'is_mod';

                    foreach ($results as $row) {
                        if ($row->$var) {
                            $group_ids [$row->id] = (isset($row->date_modified)) ? $row->date_modified : $row->date_created;
                        }
                    }
            } // end switch
        }

        return $group_ids;
    }

    function actDeletedBuddypressGroup($group_id)
    {
        $pp = presspermit();

        $pp->deleteExceptions($group_id, 'bp_group');

        if (is_multisite()) {
            \PublishPress\Permissions\NetworkGroupCleanupBatch::start(
                'buddypress_group',
                $group_id,
                get_current_blog_id()
            );
        }
    }
}
