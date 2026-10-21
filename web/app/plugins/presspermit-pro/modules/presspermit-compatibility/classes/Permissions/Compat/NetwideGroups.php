<?php
namespace PublishPress\Permissions\Compat;

class NetwideGroups
{
    function __construct()
    {
        add_filter('presspermit_use_groups_table', [$this, 'fltUseGroupsTable'], 10, 2);
        add_filter('presspermit_use_group_members_table', [$this, 'fltUseGroupMembersTable'], 10, 2);
        add_filter('presspermit_get_group', [$this, 'fltGetGroup'], 10, 3);
        add_filter('presspermit_get_groups', [$this, 'fltGetGroups'], 10, 3);
        add_filter('presspermit_get_group_members', [$this, 'fltGetGroupMembers'], 10, 5);
        add_filter('presspermit_get_groups_for_user', [$this, 'fltGetGroupsForUser'], 10, 4);
        add_filter('presspermit_add_exception_source_types', [$this, 'fltAddExceptionSourceTypes'], 10, 2);

        global $wpdb;

        $labels = (is_admin()) 
        ? ['name' => esc_html__('Network Groups', 'presspermit-pro'), 'singular_name' => esc_html__('Network Group', 'presspermit-pro')] 
        : [];

        $schema = [
            'members' => [
                'members_table' => $wpdb->pp_group_members_netwide, 
                'col_member_user' => 'user_id', 
                'col_member_group' => 'group_id'
            ],
            'groups' => [
                'groups_table' => $wpdb->pp_groups_netwide, 
                'col_group_id' => 'ID', 
                'col_group_name' => 'name'
            ],
        ];

        presspermit()->groups()->registerGroupType('pp_net_group', compact('labels', 'schema'));
    }

    function fltUseGroupsTable($table, $agent_type = 'pp_net_group')
    {
        if ('pp_net_group' == $agent_type) {
            global $wpdb;
            $table = $wpdb->pp_groups_netwide;
        }

        return $table;
    }

    function fltUseGroupMembersTable($table, $agent_type)
    {
        if ('pp_net_group' == $agent_type) {
            global $wpdb;
            $table = $wpdb->pp_group_members_netwide;
        }

        return $table;
    }

    function fltGetGroupMembers($members, $group_id, $agent_type, $cols, $args)
    {
        if ('pp_net_group' == $agent_type) {
            require_once(PRESSPERMIT_CLASSPATH . '/DB/Groups.php');

            $args['agent_type'] = $agent_type;
            $members = \PublishPress\Permissions\DB\Groups::getGroupMembers($group_id, $cols, $args);
        }

        return $members;
    }

    function fltGetGroupsForUser($groups, $user_id, $agent_type, $args)
    {
        if ('pp_net_group' == $agent_type) {
            require_once(PRESSPERMIT_CLASSPATH . '/DB/Groups.php');

            $args['agent_type'] = $agent_type;
            $groups = \PublishPress\Permissions\DB\Groups::getGroupsForUser($user_id, $args);
        }

        return $groups;
    }

    function fltGetGroups($groups, $agent_type, $args)
    {
        if ('pp_net_group' == $agent_type) {
            require_once(PRESSPERMIT_CLASSPATH . '/DB/Groups.php');

            $args['agent_type'] = $agent_type;
            $args['skip_meta_types'] = 'wp_role';
            $groups = \PublishPress\Permissions\DB\Groups::getGroups($args);
        }
        return $groups;
    }

    function fltGetGroup($group, $agent_id, $agent_type)
    {
        if ('pp_net_group' == $agent_type) {
            global $wpdb;

            // phpcs Note: Direct query on plugin tables for plugin admin operation

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($group = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT ID, group_name AS name, group_description, metagroup_type, metagroup_id"
                    . " FROM $wpdb->pp_groups_netwide WHERE ID = %d",

                    $agent_id
                )
            )) {
                $group->name = stripslashes($group->name);
                $group->group_description = stripslashes($group->group_description);
                $group->group_name = $group->name;
            }
        }

        return $group;
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function anyGroupManager($any, $user_id)
    {
        $user = presspermit()->getUser();

        if (!isset($user->except["manage_pp_net_group"]))
            $user->retrieveExceptions(['manage'], ['pp_net_group']);

        return !empty($user->except["manage_pp_net_group"]['pp_group']['']['additional']['pp_group']['']);
    }
    */

    function fltAddExceptionSourceTypes($add_src_types, $args = [])
    {
        $add_src_types['pp_net_group']['pp_net_group'] = [];
        return $add_src_types;
    }
}
