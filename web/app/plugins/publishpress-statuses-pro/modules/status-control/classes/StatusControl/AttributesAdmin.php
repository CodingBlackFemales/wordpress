<?php
namespace PublishPress\Statuses\StatusControl;

class AttributesAdmin
{
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    // returns array [item_id][condition] = true or (if return_array=true) [ 'inherited_from' => $row->inherited_from ]
    // source_name = item source name (i.e. 'post') 
    //
    public static function getItemCondition($source_name, $attribute, $args = [])
    {
        // Note: propogating conditions are always directly assigned to the child item(s).
        // Use assign_for = 'children' to retrieve condition values that are set for propagation to child items,
        $defaults = ['id' => null, 'object_type' => '', 'assign_for' => 'item', 'default_only' => false, 'inherited_only' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $id = (int) $id;
        $object_type = sanitize_key($object_type);

        if ($default_only)
            return null;

        $query_ids = (array)$id;

        if (function_exists('presspermit')) {
            $pp = presspermit();

            if (!empty($pp->listed_ids)) {
                foreach (array_keys($pp->listed_ids) as $_type) {
                    $query_ids = array_merge($query_ids, array_keys($pp->listed_ids[$_type]));
                }
            }
        }

        global $wpdb;

        $items = [];

        if ($query_ids) { // don't return all objects
            $query_id_csv = implode("','", array_map('intval', array_unique($query_ids)));
            $id_clause = "AND item_id IN ('$query_id_csv')";
        } else {
            $id_clause = "AND item_id != 0";
        }

        $inherited_clause = (!empty($args['inherited_only'])) ? "AND inherited_from > 0" : '';

        static $all_attrib_conditions;
        if (!isset($all_attrib_conditions))
            $all_attrib_conditions = [];

        // phpcs Note: Direct query of plugin table during admin operation

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $qry = $wpdb->prepare(
            "SELECT attribute, condition_name, item_id, inherited_from FROM $wpdb->pp_conditions"               // phpcs Note: Query clauses constructed and sanitized above
            . " WHERE scope = 'object' AND assign_for = %s AND item_source = %s $inherited_clause $id_clause",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            
            $assign_for, 
            $source_name
        );
        
        $qkey = "{$assign_for}:{$source_name}:{$inherited_clause}";

        if (!isset($all_attrib_conditions[$qkey])) {
            if (!isset($all_attrib_conditions[$qkey]) || !isset($all_attrib_conditions[$qkey][$id])) {
                $all_attrib_conditions[$qkey] = [];

                // phpcs Note: Direct query of plugin table during admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT attribute, condition_name, item_id, inherited_from FROM $wpdb->pp_conditions"               // phpcs Note: Query clauses constructed and sanitized above
                        . " WHERE scope = 'object' AND assign_for = %s AND item_source = %s $inherited_clause $id_clause",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        
                        $assign_for, 
                        $source_name
                    )
                );

                foreach ($results as $row) {
                    $all_attrib_conditions[$qkey][$row->item_id][$row->attribute][$row->condition_name] = $row;
                }
            }
        }

        if (isset($all_attrib_conditions[$qkey][$id][$attribute])) {
            foreach ($all_attrib_conditions[$qkey][$id][$attribute] as $condition => $row) {
                $items[$id][$condition] = true;
            }
        }

        return (!is_null($id) && isset($items[$id])) ? key($items[$id]) : null;
    }
}
