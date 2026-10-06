<?php
namespace PublishPress\Statuses\StatusControl;

class ItemSave
{
    public static function get_parent_conditions($attribute, $scope, $source_name, $parent_id)
    {
        global $wpdb;

        // Since this is a new object, propagate item conditions from parent (if any are marked for propagation)
        
        // phpcs Note: Direct query of plugin table during admin operation

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT condition_name, attribute, assignment_id, scope, item_source, item_id, assign_for, mode, inherited_from"
                . " FROM $wpdb->pp_conditions WHERE attribute = %s AND scope = %s AND assign_for = 'children' AND item_source = %s"
                . " AND item_id = %d ORDER BY condition_name", 
                
                $attribute, 
                $scope, 
                $source_name, 
                $parent_id
            ),
            OBJECT_K
        );
        
        return $results;
    }

    public static function inherit_parent_conditions(
        $attribute, $item_id, $scope, $source_name, $parent_id, $object_type = '', $default_value = false
    ) {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/AttributesUpdate.php');

        if (!$parent_id && $default_value) {
            $parent_conditions = [(object)['attribute' => $attribute, 'condition_name' => $default_value, 'item_id' => 0]];
        } else {
            $parent_conditions = self::get_parent_conditions($attribute, $scope, $source_name, $parent_id);
        }

        if ($parent_conditions) {
            foreach ($parent_conditions as $row) {
                $inherited_from = ($row->item_id) ? $row->assignment_id : 0;

                $args = ['is_auto_insertion' => true, 'inherited_from' => $inherited_from];

                DB\AttributesUpdate::insert_item_condition(
                    $row->attribute, 
                    $scope, 
                    $source_name, 
                    $item_id, 
                    $row->condition_name, 
                    'item', 
                    $args
                );
                
                DB\AttributesUpdate::insert_item_condition(
                    $row->attribute, 
                    $scope, 
                    $source_name, 
                    $item_id, 
                    $row->condition_name, 
                    'children', 
                    $args
                );
            }
        }
    }

    public static function propagate_post_visibility($post_id, $visibility, $args = [])
    {
        if ($visibility) {
            PPS::setItemCondition(
                'force_visibility', 
                'object', 
                'post', 
                $post_id, 
                $visibility, 
                'children', 
                ['propagate' => true]
            );

            // if child visibility is set, apply it for all published subposts
            $post_status = get_post_stati(['public' => true, 'private' => true], 'names', 'or');

            if ($published_subposts = PPS::getDescendantIds(
                'post', 
                $post_id, 
                ['post_status' => $post_status, 'append_clause' => "AND post_password = ''"]
            )) {
                global $wpdb;

                $subposts_id_csv = implode("','", array_map('intval', $published_subposts));

                // phpcs Note: Direct query of posts table during admin operation, to query posts without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $wpdb->posts SET post_status = %s WHERE ID IN ('$subposts_id_csv')",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $visibility
                    )
                );
            }
        } else {
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item');
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item', ['propagate' => true, 'inherited_only' => true]);
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'children', ['propagate' => true]);
        }
    }

    public static function post_update_force_visibility($object, $args = [])
    {
        $post_id = $object->ID;

        if (isset($_REQUEST["pp_ajax_set_privacy"])) {
            check_ajax_referer('pp-ajax');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif (!isset($_REQUEST['_wpnonce']) || (($post_id && !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "update-post_{$post_id}")) && (!wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "add-{$object->post_type}")))
        ) {
            return;
        }

        // setting for post being edited
        foreach (['item' => 'pp_force_visibility', 'children' => 'pp_ch_force_visibility'] as $assign_for => $var) {
            // make sure the UI for this condition was actually reviewed
            if (!empty($args[$assign_for])) {
                if ($args[$assign_for])
                    PPS::setItemCondition('force_visibility', 'object', 'post', $post_id, \PP_Statuses_Functions::sanitizeEntry($args[$assign_for]), $assign_for);
                else
                    PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, $assign_for);

            } elseif (isset($_POST[$var])) {
                if (sanitize_text_field(wp_unslash($_POST[$var]))) {
                    PPS::setItemCondition('force_visibility', 'object', 'post', $post_id, \PP_Statuses_Functions::sanitizeEntry(sanitize_text_field(wp_unslash($_POST[$var]))), $assign_for);
                } else {
                    PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, $assign_for);
                }
            }
        } // end foreach (item/children)

        // parent setting affects auto-assignment of force_visibility
        $set_parent = $object->post_parent;
        $last_parent = ($post_id > 0) ? get_post_meta($post_id, '_pp_last_parent', true) : 0;

        if (intval($set_parent) !== intval($last_parent)) {
            update_post_meta($object->ID, '_pp_last_parent', (int)$set_parent);

            // Inherit parent condition, but only for new post or if parent has changed 
            // (force_visibility is always propagated and cannot be overrident by manual setting)
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item', ['inherited_only' => true]);
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'children', ['inherited_only' => true]);

            // apply propagating conditions from specific parent
            self::inherit_parent_conditions('force_visibility', $post_id, 'object', 'post', $set_parent, $object->post_type);
        }
    }
}
