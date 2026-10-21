<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

class HooksAdmin
{
    function __construct() 
    {
        add_filter('presspermit_groups_list_table', [$this, 'fltGroupsListTable'], 10, 2);
        add_action('admin_head', [$this, 'actAdminHead']);
        add_action('presspermit_group_edit_form', [$this, 'actGroupEditForm'], 10, 2);
        add_action('bp_groups_admin_comment_row_actions', [$this, 'actGroupsAdminRowActions'], 10, 2);
        add_filter('presspermit_editable_group_types', [$this, 'fltEditableGroupTypes']);

        add_filter('presspermit_append_exception_types', [$this, 'fltAppendExceptionTypes'], 20);
        add_filter('presspermit_exception_operations', [$this, 'fltAppendExceptionOperations'], 2, 4);
        add_filter('presspermit_item_select_metabox_function', [$this, 'fltExceptionItemSelectMetabox'], 10, 2);
        add_filter('presspermit_exception_via_types', [$this, 'fltExceptionViaTypes'], 10, 5);
        add_filter('presspermit_add_exception_source_types', [$this, 'fltAddExceptionSourceTypes'], 10, 2);
    }

    function fltEditableGroupTypes($editable_types)
    {
        return array_merge($editable_types, ['bp_group']);
    }

    function fltAddExceptionSourceTypes($add_src_types, $args = [])
    {
        $add_src_types['bp_group']['bp_group'] = [];
        return $add_src_types;
    }

    function actGroupsAdminRowActions($actions, $group)
    {
        $group = (array)$group;

        if (current_user_can('pp_assign_roles') && presspermit()->groups()->userCan('pp_edit_groups', $group['id'], 'bp_group')) {
            $edit_link = "?page=presspermit-edit-permissions&amp;action=edit&amp;agent_type=bp_group&amp;agent_id={$group['id']}";
            $actions['content_perms'] = '<a href="' . $edit_link . '">' . __('Permissions', 'ppbp') . '</a>';
        }

        return $actions;
    }

    function fltGroupsListTable($list_table, $group_type)
    {
        if ('bp_group' == $group_type) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/GroupsListTable.php');
            $list_table = new GroupsListTable();
        }

        return $list_table;
    }

    function actAdminHead()
    {
        echo '<link rel="stylesheet" href="' . esc_url(plugins_url('', PRESSPERMIT_COMPAT_FILE)) . '/common/css/buddypress-permission-groups.css" type="text/css" />' . "\n";

        if (!is_network_admin() || !did_action('load-toplevel_page_bp-groups') || PWP::empty_REQUEST('gid')) {
            return;
        }

        $bp_gid = PWP::REQUEST_int('gid');

        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/GroupsListTableShell.php');
        $list_table_shell = new GroupsListTableShell();

        $list_table_shell->role_info = \PublishPress\Permissions\API::countRoles('bp_group', ['query_agent_ids' => (array)$bp_gid]);
        $list_table_shell->exception_info = \PublishPress\Permissions\API::countExceptions('bp_group', ['query_agent_ids' => (array)$bp_gid]);

        $base_url = apply_filters('presspermit_groups_base_url', 'admin.php');

        $edit_link = $base_url . "?page=presspermit-edit-permissions&amp;action=edit&amp;agent_type=bp_group&amp;agent_id=$bp_gid";
        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                $('#side-sortables').append('<div id="pp-permissions" class="postbox" title="<?php _e('Supplemental roles and specific permissions assigned to this group by PublishPress Permissions', 'presspermit');?>"><div class="handlediv" title="<?php _e('Click to toggle');?>"><br></div><h3 class="hndle"><span><?php _e('Content Permissions', 'presspermit');?></span></h3><div class="inside">'
                + '<?php 
                echo '<div class="ppbp-label"><strong>' . esc_html__('Roles', 'presspermit') 
                . '</strong></div><div class="ppbp-assignments-brief">'
                . '<td>';

                $list_table_shell->single_row_role_column('roles', $bp_gid, true, $edit_link, '', ['none_title' => __('none', 'ppbp'), 'none_link' => $edit_link]);

                echo '</td></div><br />';

                echo '<div class="ppbp-label"><strong>' . esc_html__('Exceptions', 'presspermit') 
                . '</strong></div><div class="ppbp-assignments-brief">'
                . '<td>';

                $list_table_shell->single_row_role_column('exceptions', $bp_gid, true, $edit_link, '', ['none_title' => __('none', 'ppbp'), 'none_link' => $edit_link]);

                echo '</td></div>';
                ?>'
                + '</div></div>');
            });
            /* ]]> */
        </script>
        <?php
    }

    function actGroupEditForm($group_type, $group_id)
    {
        if ('bp_group' != $group_type)
            return;

        $has_groups = bp_has_groups();
        ?>
        <div>

            <?php
            while (bp_groups()):
                bp_the_group();
                $_group_id = bp_get_group_id();

                if ($_group_id != $group_id)
                    continue;

                if (bp_group_has_members('group_id=' . $group_id . '&exclude_admins_mods=0&exclude_banned=0')) {
                    ?>
                    <div class="pp-group-box pp-group_members" style="float:left;margin-right:20px;">
                        <h3><?php _e('BuddyPress Group Members', 'presspermit'); ?></h3>
                        <div>
                            <select style="height:160px;width:200px;margin-top:10px" multiple="multiple"
                                    disabled>
                                <?php
                                $require_mod = defined('PPBP_GROUP_MODERATORS_ONLY') && PPBP_GROUP_MODERATORS_ONLY;
                                $require_admin = defined('PPBP_GROUP_ADMINS_ONLY') && PPBP_GROUP_ADMINS_ONLY;

                                while (bp_group_members()) {
                                    $member = bp_group_the_member();

                                    if ($require_admin && !$member->is_admin)
                                        continue;

                                    if ($require_mod && !$member->is_admin && !$member->is_mod)
                                        continue;
                                    ?>
                                    <option><?php bp_group_member_link(); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php
                }
                ?>

            <?php endwhile; ?>
        </div>
        <?php
    }

    function fltAppendExceptionTypes($types)
    {
        $types['bp_group'] = (object)[
            'name' => 'bp_group', 
            'labels' => (object)[
                'singular_name' => __('BP Permission Group', 'presspermit-pro'), 
                'name' => __('BP Permission Groups', 'presspermit-pro')
                ]
            ];
        
        return $types;
    }

    function fltAppendExceptionOperations($ops, $for_item_source, $for_type, $args = [])
    {
        if ('bp_group' == $for_type) {
            $ops = array_unique(
                array_merge(
                    $ops, 
                    ['manage' => __('Manage', 'presspermit-pro')]
                )
            );
        }

        return $ops;
    }

    function fltExceptionViaTypes($types, $for_item_source, $for_type, $operation, $mod_type)
    {
        if ('bp_group' == $for_item_source) {
            $types['bp_group'] = __('BuddyPress Groups', 'presspermit-pro');
        }

        return $types;
    }

    function fltExceptionItemSelectMetabox($function, $type_obj) {
        if ('bp_group' == $type_obj->name) {
            $function = ['\PublishPress\Permissions\UI\ItemsMetabox', 'group_meta_box'];
        }

        return $function;
    }
}
