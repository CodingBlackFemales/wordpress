<?php
namespace PublishPress\Permissions\Circles\DB;

/**
 * Groups class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 *
 */

class Groups
{
    public static function getGroupCircles($group_type, $group_id, $circle_type = false)
    {
        global $wpdb;

        if (!$circle_type)
            $circle_type = ['read', 'edit'];
        else
            $circle_type = (array)$circle_type;

        $group_id = (array)$group_id;

        $id_csv = implode("','", array_map('intval', $group_id));
        $ctype_csv = implode("','", array_map('sanitize_key', $circle_type));

        // phpcs Note: Direct query on plugin table used for plugin admin queries

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $circles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT group_id, circle_type, post_type, ID FROM $wpdb->pp_circles"
                . " WHERE group_type = %s AND circle_type IN ('$ctype_csv') AND group_id IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " ORDER BY group_id",

                $group_type
            )
        );

        foreach($circles as $k => $row) {
            if (empty($row->ID) && !empty($row->id)) {
                $circles[$k]->ID = $circles[$k]->id;
            }
        }

        $circle_groups = [];
        foreach ($circles as $circle) {
            $circle_groups[$circle->group_id][$circle->circle_type][$circle->post_type] = $circle->ID;
        }
        return $circle_groups;
    }

    // (note: this limits the user's scope of assigned editing role(s), does not bestow editing access)
    public static function getCircleMembers($circle_type, $user = false, $force_refresh = false)
    {
        if (!in_array($circle_type, \PublishPress\Permissions\Circles::getCircleTypes(), true))
            return [];

        if (!$user) {
            $user = presspermit()->getUser();
        }

        static $cfg;

        $pp = presspermit();
        $pp_groups = $pp->groups();

        if (!isset($cfg))
            $cfg = [];

        if (!isset($cfg[$user->ID]) || $force_refresh)
            $cfg[$user->ID] = [];

        if (!isset($cfg[$user->ID][$circle_type]))
            $cfg[$user->ID][$circle_type] = [];
        else {
            return $cfg[$user->ID][$circle_type];
        }

        $all_post_types = array_merge($pp->getEnabledPostTypes(), ['user']);
        $cfg[$user->ID][$circle_type] = array_fill_keys($all_post_types, []);

        $user_groups = (isset($user->groups)) ? $user->groups : [];

        if (!$user_groups) {
            $user_groups = [];

            foreach (array_keys($pp->groups()->getGroupTypes()) as $agent_type) {
                $user_groups[$agent_type] = $pp_groups->getGroupsForUser(
                    $user->ID, 
                    $agent_type, 
                    ['cols' => 'id', 'force_refresh' => true]
                );
            }
        }

        global $wpdb;
        if ($user->ID != presspermit()->getUser()->ID) {
            static $circles;
        }

        if (!isset($circles))
            $circles = [];

        if (!isset($circles[$circle_type])) {
            $circles[$circle_type] = [];

            if (($user->ID != presspermit()->getUser()->ID) || !$user_groups) {
                // phpcs Note: Direct query on plugin table, used for plugin admin queries

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT group_type, group_id, post_type FROM $wpdb->pp_circles WHERE circle_type = %s", 
                        $circle_type
                    )
                );
            } else {
                $results = [];

                foreach (array_keys($user_groups) as $group_type) {
                    $group_id_csv = implode("','", array_map('intval', array_keys($user_groups[$group_type])));

                    $results = array_merge($results, 
                        // phpcs Note: Direct query on plugin table, used for plugin admin queries

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT group_type, group_id, post_type FROM $wpdb->pp_circles"
                                . " WHERE circle_type = %s AND group_type = %s"
                                . " AND group_id IN ('$group_id_csv')",   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                
                                $circle_type,
                                $group_type
                            )
                        )
                    );
                }
            }

            foreach ($results as $row) {
                $circles[$circle_type][$row->group_type][$row->group_id][$row->post_type] = true;
            }
        }

        foreach (array_keys($user_groups) as $group_type) {
            if (!empty($circles[$circle_type][$group_type])) {
                foreach (array_intersect_key($circles[$circle_type][$group_type], $user_groups[$group_type]) 
                    as $group_id => $circle_post_types
                ) {
                    $get_arg = ('bp_group' == $group_type) ? 'ids' : 'id'; // todo: clean this up in Compat module
                    $members = $pp_groups->getGroupMembers($group_id, $group_type, $get_arg);

                    foreach ($all_post_types as $post_type) {
                        if (!isset($cfg[$user->ID][$circle_type][$post_type]))
                            $cfg[$user->ID][$circle_type][$post_type] = [];

                        if ($members && !empty($circles[$circle_type][$group_type][$group_id][$post_type])) {
                            $cfg[$user->ID][$circle_type][$post_type] = array_merge(
                                $cfg[$user->ID][$circle_type][$post_type], 
                                $members
                            );
                        }
                    }
                }
            }
        }

        $cfg[$user->ID][$circle_type] = apply_filters(
            'presspermit_circle_members', 
            $cfg[$user->ID][$circle_type], 
            $circle_type, 
            $user->ID
        );

        foreach (array_keys($cfg[$user->ID][$circle_type]) as $post_type) {
            if (empty($cfg[$user->ID][$circle_type][$post_type])) {
                unset($cfg[$user->ID][$circle_type][$post_type]);
            } else {
                $cfg[$user->ID][$circle_type][$post_type] = array_map('intval', array_unique($cfg[$user->ID][$circle_type][$post_type]));
            }
        }

        return $cfg[$user->ID][$circle_type];
    }
}
