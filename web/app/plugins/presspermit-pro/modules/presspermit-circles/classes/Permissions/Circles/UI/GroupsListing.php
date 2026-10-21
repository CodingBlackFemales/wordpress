<?php
namespace PublishPress\Permissions\Circles\UI;

class GroupsListing
{
    function __construct()
    {
        add_filter('presspermit_manage_groups_columns', [$this, 'fltManageGroupsColumns']);
        add_action('presspermit_manage_groups_custom_column', [$this, 'actManageGroupsCustomColumn'], 10, 3);
    }

    function fltManageGroupsColumns($cols)
    {
        $cols['circle_type'] = esc_html__('Circle Type', 'presspermit-pro');
        return $cols;
    }

    function actManageGroupsCustomColumn($column_name, $group_id, $groups_list_table)
    {
        if ('circle_type' == $column_name) {
            static $group_circles;

            if (!isset($group_circles)) {
                $group_circles = [];

                $group_type = $groups_list_table->getAgentType();

                global $wpdb;

                // phpcs Note: Direct query on plugin table, used for plugin admin queries

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DISTINCT group_id, circle_type FROM $wpdb->pp_circles WHERE group_type = %s",
                        $group_type
                    )
                );
                
                foreach ($results AS $row) {
                    $group_circles[$row->group_id][$row->circle_type] = true;
                }
            }

            if (isset($group_circles[$group_id])) {
                if (!empty($group_circles[$group_id]['read'])) {
                    esc_html_e('Visibility', 'presspermit-pro');
                }

                if (!empty($group_circles[$group_id]['edit'])) {
                    if (!empty($group_circles[$group_id]['read'])) {
                        echo ', ';
                    }

                    esc_html_e('Editorial', 'presspermit-pro');
                }
            }
        }
    }
}
