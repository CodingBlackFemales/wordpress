<?php
namespace PublishPress\Statuses\StatusControl\DB;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AttributesUpdate
{
    static function set_condition_action($assignment_id, $cond_arr)
    {
        do_action(
            'presspermit_set_item_condition', 
            $cond_arr['scope'], 
            $cond_arr['item_source'], 
            $cond_arr['item_id'], 
            $assignment_id, 
            $cond_arr
        );
    }

    // additional arguments recognized by _pp_insert_item_condition:
    // is_auto_insertion = false  (if true, skips logging the item as having a manually modified condition)
    public static function set_item_condition($attribute, $scope, $source_name, $item_id, $set_condition, $assign_for = 'item', $args = [])
    {
        global $wpdb;

        $force_flush = !empty($args['force_flush']);

        // Note: each attribute should have at most one stored condition per item.  But force retrieval of last entry in case of redundant storage.  
        // pp_insert_item_condition will delete redundancies

        // phpcs Note: Direct query of plugin tables on post update, if "subpage visibility" setting has changed

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT condition_name, assignment_id FROM $wpdb->pp_conditions WHERE scope = %s AND attribute = %s"
                . " AND item_source = %s AND item_id = %d AND assign_for = %s ORDER BY assignment_id DESC LIMIT 1",
                $scope, $attribute, $source_name, $item_id, $assign_for
            )
        )) {
            if (($row->condition_name != $set_condition) || $force_flush) {
                self::remove_conditions_by_id([$row->assignment_id]);
            } else {
                return;
            }
        }

        self::insert_item_condition($attribute, $scope, $source_name, $item_id, $set_condition, $assign_for, $args);
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
     * assign_for : 'item' or 'children'
     */ 
    public static function insert_item_condition($attribute, $scope, $item_source, $item_id, $condition, $assign_for, $args = [])
    {
        // auto_insertion arg set for restriction propagation from parent objects
        $defaults = ['inherited_from' => 0, 'is_auto_insertion' => false, 'propagate' => true];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        global $wpdb;

        $inherited_from = (int)$inherited_from;

        // before inserting the role, delete any other matching or conflicting assignments this user/group has for the same object

        // phpcs Note: Direct query of plugins table on post update, if any new "subpage visibility" values

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($ass_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT assignment_id FROM $wpdb->pp_conditions"
                . " WHERE scope = %s AND attribute = %s AND item_source = %s AND assign_for = %s AND item_id = %d", 
                
                $scope,
                $attribute,
                $item_source,
                $assign_for,
                $item_id
            )
        )) {
            self::remove_conditions_by_id($ass_ids);
        }

        $item_id = (int)$item_id;
        $assign_for = sanitize_key($assign_for);
        $inherited_from = (int)$inherited_from;

        $insert_data = compact('item_source', 'attribute', 'scope');
        $insert_data['condition_name'] = $condition;

        // insert condition for specified item

        // phpcs Note: Direct query of plugins table on post update, if any new "subpage visibility" values

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert($wpdb->pp_conditions, array_merge($insert_data, compact('item_id', 'assign_for', 'inherited_from')));     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        $condition_name = $insert_data['condition_name'];

        $assignment_id = $wpdb->insert_id;
        $cond_arr = compact('attribute', 'condition_name', 'scope', 'item_source', 'item_id', 'assign_for', 'inherited_from');
        self::set_condition_action($assignment_id, $cond_arr);

        // insert condition for all descendant items, if requested
        if (('children' == $assign_for) && $propagate) {
            if (!$inherited_from) {
                $inherited_from = (int)$wpdb->insert_id;
                $cond_arr['inherited_from'] = $inherited_from;
            }


            $descendant_ids = PPS::getDescendantIds($item_source, $item_id);

            if (!$descendant_ids = PPS::getDescendantIds($item_source, $item_id))
                return;

            // note: Propagated conditions will be converted to direct-assigned roles if the parent object/term is deleted.
            //       If the parent setting is changed, conditions inherited from old parent will be cleared before inheriting conditions from new parent. 
            $delete_ass_ids = [];

            foreach ($descendant_ids as $id) {
                // before inserting the role, delete any other propagated assignments this user/group has for the same object type

                // phpcs Note: Direct query of plugins table on post update, if any new "subpage visibility" values

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($_ass_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT assignment_id FROM $wpdb->pp_conditions"
                        . " WHERE scope = %s AND attribute = %s AND item_source = %s AND item_id = %d",

                        $scope, 
                        $attribute, 
                        $item_source,
                        $id
                    )
                )) {
                    $delete_ass_ids = array_merge($delete_ass_ids, $_ass_ids);
                }

                $cond_arr['item_id'] = $id;

                // phpcs Note: Direct query of plugins table on post update, if any new "subpage visibility" values

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert(
                    $wpdb->pp_conditions, 
                    array_merge(
                        $insert_data, 
                        ['item_id' => $id, 'assign_for' => 'item', 'inherited_from' => $inherited_from]
                    )
                );
                
                $assignment_id = $wpdb->insert_id;
                $cond_arr['assign_for'] = 'item';
                self::set_condition_action($assignment_id, $cond_arr);

                // phpcs Note: Direct query of plugins table on post update, if any new "subpage visibility" values

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert(
                    $wpdb->pp_conditions, 
                    array_merge(
                        $insert_data, 
                        ['item_id' => $id, 'assign_for' => 'children', 'inherited_from' => $inherited_from]
                    )
                );
                
                $assignment_id = $wpdb->insert_id;
                $cond_arr['assign_for'] = 'item';
                self::set_condition_action($assignment_id, $cond_arr);
            }

            if ($delete_ass_ids)
                self::remove_conditions_by_id($delete_ass_ids);
        }
    }

    public static function clear_item_condition($attribute, $scope, $source_name, $item_id, $assign_for, $args = [])
    {
        $defaults = ['inherited_only' => false, 'propagate' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        global $wpdb;

        $inherited_clause = ($inherited_only) ? "AND inherited_from > 0" : '';

        $ids = (array)$item_id;

        if ($propagate) {
            if ($descendant_ids = PPS::getDescendantIds($source_name, $item_id)) {
                $ids = array_merge($ids, $descendant_ids);
            }
        }

        $id_csv = implode("','", array_map('intval', $ids));

        // phpcs Note: Direct query of plugins table on post update, if any deleted "subpage visibility" values

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($ass_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT assignment_id FROM $wpdb->pp_conditions"
                . " WHERE attribute = %s AND scope = %s AND assign_for = %s"
                . " AND item_source = %s $inherited_clause"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . " AND item_id IN ('$id_csv')",             // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    
                $attribute, 
                $scope, 
                $assign_for, 
                $source_name
            )
        )) {
            self::remove_conditions_by_id($ass_ids);
        }
    }

    public static function remove_conditions_by_id($delete_assignments)
    {
        $delete_assignments = (array)$delete_assignments;

        if (!count($delete_assignments))
            return;

        global $wpdb;

        // Propagated roles will be deleted only if the original progenetor goes away.  
        // Removal of a "link" in the parent/child propagation chain has no effect.

        $id_csv = implode("', '", array_map('intval', $delete_assignments));

        // deleted condition data will be passed through action

        // phpcs Note: Direct query of plugins table on propagated changes to "subpage visibility" values

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $conditions = $wpdb->get_results(
            "SELECT * FROM $wpdb->pp_conditions WHERE assignment_id IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // phpcs Note: Direct query of plugins table on propagated changes to "subpage visibility" values

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE FROM $wpdb->pp_conditions WHERE assignment_id IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        foreach ($conditions as $cond) {
            // called once per removed conditions (potentially multiple per item)
            do_action(
                'presspermit_removed_item_condition', 
                $cond->scope, 
                $cond->item_source, 
                $cond->item_id, 
                $cond->assignment_id, 
                (array)$cond
            );
        }

        do_action('presspermit_removed_conditions', $delete_assignments);
    }
}
