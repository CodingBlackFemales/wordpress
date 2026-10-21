<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

require_once(PRESSPERMIT_CLASSPATH . '/UI/GroupsListTableBase.php');

require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/GroupsQuery.php');

class GroupsListTable extends \PublishPress\Permissions\UI\GroupsListTableBase
{

    var $site_id;
    var $listed_ids;

    function __construct()
    {
        global $_wp_column_headers;

        $screen = get_current_screen();

        // clear out empty entry from initial admin_header.php execution
        if (isset($_wp_column_headers[$screen->id]))
            unset($_wp_column_headers[$screen->id]);

        parent::__construct([
            'singular' => 'bp_group',
            'plural' => 'bp_groups'
        ]);
    }

    function ajax_user_can()
    {
        return current_user_can('manage_pp_groups');
    }

    function prepare_items()
    {
        global $groupsearch;

        // phpcs Note: Nonce verification unnecessary for search parameter of Groups listing

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        $groupsearch = !empty($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        $groups_per_page = $this->get_items_per_page('groups_per_page');

        $paged = $this->get_pagenum();

        $args = [
            'number' => $groups_per_page,
            'offset' => ($paged - 1) * $groups_per_page,
            'search' => $groupsearch,
        ];

        $args['search'] = '*' . $args['search'] . '*';

        if ($orderby = PWP::REQUEST_key('orderby')) {
            $args['orderby'] = $orderby;
        }

        if ($order = PWP::REQUEST_key('order')) {
            $args['order'] = $order;
        }

        // Query the user IDs for this page
        $group_search = new GroupsQuery($args);

        $this->items = $group_search->get_results();

        foreach ($this->items as $group) {
            $this->listed_ids[] = $group->id;
        }

        if (isset($this->listed_ids)) {
            $this->role_info = \PublishPress\Permissions\API::countRoles('bp_group', ['query_agent_ids' => $this->listed_ids]);
            $this->exception_info = \PublishPress\Permissions\API::countExceptions('bp_group', ['query_agent_ids' => $this->listed_ids]);
        }

        $this->set_pagination_args([
            'total_items' => $group_search->get_total(),
            'per_page' => $groups_per_page,
        ]);
    }

    function no_items()
    {
        esc_html_e('No matching groups were found.', 'presspermit');
    }

    function get_views()
    {
        return [];
    }

    function get_bulk_actions()
    {
        return [];
    }

    function get_columns()
    {
        $c = [
            'cb' => '',
            'ID' => esc_html__('ID'),
            'name' => esc_html__('Name'),
            'num_users' => _x('Users', 'count', 'presspermit'),
            'site_roles' => _x('Sitewide Roles', 'count', 'presspermit'),
            'item_roles' => _x('Content Roles', 'count', 'presspermit'),
            'description' => esc_html__('Description', 'presspermit'),
        ];

        return $c;
    }

    function get_sortable_columns()
    {
        return [];
    }

    function display_rows()
    {
        $style = '';

        bp_has_groups();

        foreach ($this->items as $group_object) {
            $style = ('alternate' == $style) ? '' : 'alternate';
            $this->single_row($group_object, $style);
        }
    }

    /**
     * Generate HTML for a single row on the PP Role Groups admin panel.
     *
     * @param object $user_object
     * @param string $style Optional. Attributes added to the TR element.  Must be sanitized.
     * @param int $num_users Optional. User count to display for this group.
     * @return string
     */
    function single_row($group_object, $style = '')
    {
        global $groups_template;
        $groups_template->group = $group_object;

        static $base_url;
        static $members_cap;
        static $is_administrator;

        $pp = presspermit();
        $pp_groups = $pp->groups();

        $group_id = $group_object->id;

        if (!isset($base_url)) {
            $base_url = apply_filters('presspermit_groups_base_url', 'admin.php'); // TODO: filter based on menu usage

            $is_administrator = presspermit()->isUserAdministrator();

            if (!$is_administrator)
                $members_cap = apply_filters('presspermit_edit_groups_reqd_caps', ['manage_pp_groups'], 'edit-members');
        }

        // Set up the hover actions for this user
        $actions = [];

        $can_manage_group = $is_administrator || $pp_groups->userCan('pp_edit_groups', $group_id, 'bp_group');

        $actions = apply_filters('ppbg_group_row_actions', $actions, $group_object);

        echo "<tr id='group-" . esc_attr($group_id) . "' style='" . esc_attr($style) . "'>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = "$column_name column-$column_name";

            $style = (in_array($column_name, $hidden, true)) ? 'display:none;' : '';

            switch ($column_name) {
                case 'id':
                case 'ID':
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>" . (int) $group_object->id . "</td>";
                    break;

                case 'name':
                case 'group_name': // todo: clean this up
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>";
                    
                    bp_group_avatar("id={$group_id}&type=thumb&width=50&height=50&alt=");
                    echo ' ';
                    
                    // Check if the group for this row is editable
                    if ($can_manage_group) {
                        $edit_link = $base_url . "?page=presspermit-edit-permissions&amp;action=edit&amp;agent_type=bp_group&amp;agent_id={$group_id}";
                        $actions['edit'] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Permissions') . '</a>';
                        echo "<strong><a href='" . esc_url($edit_link) . "'>" . esc_html($group_object->name) . "</a></strong><br />";
                    } else {
                        echo '<strong>' . esc_html($group_object->name) . '</strong>';
                    }

                    $this->row_actions($actions);

                    echo "</td>";
                    break;

                case 'num_users':
                    $num_users = count(\BP_Groups_Member::get_group_member_ids($group_id));

                    echo "<td class='posts column-num_users num' style='" . esc_attr($style) . "'>";
                    
                    // Check if the group for this row is editable
                    if ($can_manage_group) {
                        $edit_url = bp_get_group_manage_url($group_object);
                        $title = esc_html__('BuddyPress Group Admin', 'presspermit');
                        echo "<strong><a href='" . esc_url($edit_url) . "' title='" . esc_attr($title) . "'>" . (int) $num_users . "</a></strong><br />";
                    } else {
                        echo (int) $num_users;
                    }

                    echo "</td>";
                break;

                case 'circle_type':
                    static $group_circles;
                    static $circle_captions;

                    if (!isset($group_circles)) {
                        $group_circles = [];

                        global $wpdb;

                        // phpcs Note: Direct query on plugin table for admin query

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $results = $wpdb->get_results("SELECT DISTINCT group_id, circle_type FROM $wpdb->pp_circles WHERE group_type = 'bp_group'");
                        foreach ($results as $row) {
                            $group_circles[$row->group_id][$row->circle_type] = true;
                        }

                        $circle_captions = [];
                        $circle_captions['read'] = esc_html__('Visibility', 'presspermit');
                        $circle_captions['edit'] = esc_html__('Editorial', 'presspermit');
                    }

                    if (isset($group_circles[$group_id])) {
                        $capt_arr = array_intersect_key($circle_captions, $group_circles[$group_id]);
                        $_caption = implode(", ", $capt_arr);
                    } else {
                        $_caption = '';
                    }
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>" . esc_html($_caption) . "</td>";
                    break;

                case 'roles':
                case 'exceptions':
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>";
                    $this->single_row_role_column($column_name, $group_id, $can_manage_group, $edit_link, $attributes);
                    echo '</td>';
                    break;

                case 'description':
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>" . esc_html($group_object->description) . "</td>";
                    break;

                default:
                    echo "<td class='" . esc_attr($class) . "' style='" . esc_attr($style) . "'>";
                    echo "</td>";
            }
        }
        
        echo '</tr>';
    }

    function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return '';
		}

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$class = ('excerpt' === $mode) ? 'row-actions visible' : 'row-actions';

		echo '<div class="' . esc_attr($class) . '">';

		$i = 0;

		foreach ( $actions as $action => $link ) {
            ++$i;
            
            echo "<span class='" . esc_attr($action) . "'>" . $link;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            if (( $i < $action_count )) echo ' | ';
            echo "</span>";
		}

		echo '</div>';

		echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__('Show more details', 'presspermit-pro') . '</span></button>';
	}
}
