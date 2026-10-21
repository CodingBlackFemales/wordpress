<?php
namespace PublishPress\Permissions\Circles\UI\Handlers;

class GroupUpdate
{
    public static function updateGroup($group_type, $group_id)
    {
        if (!PWP::empty_REQUEST('pp_ajax_agent_permissions')) {
            return;
		}

		if (!PWP::empty_REQUEST("_pp_nonce_roles")) {
            check_admin_referer('pp-update-roles_' . $group_id, '_pp_nonce_roles');
        } elseif (!PWP::empty_REQUEST("_pp_nonce_exceptions")) {
            check_admin_referer('pp-update-exceptions_' . $group_id, '_pp_nonce_exceptions');
        } elseif (!PWP::empty_REQUEST("_wpnonce_pp-create-group")) {
            check_admin_referer('pp-create-group', '_wpnonce_pp-create-group');
        } else {
            check_admin_referer('pp-update-group_' . $group_id);
        }

        if (empty($_REQUEST['reviewed_circle_types'])) {
            return;
        }

        $stored_circles = Circles::getGroupCircles($group_type, $group_id);
        
        $delete_circles = [];
        $add_circles = ['read' => [], 'edit' => []];
        foreach (['read', 'edit'] as $circle_type) {
            if (PWP::empty_REQUEST("is_{$circle_type}_circle")) {
                // circle deactivated
                if (!empty($stored_circles[$group_id][$circle_type])) {
                    $delete_circles = array_merge($delete_circles, $stored_circles[$group_id][$circle_type]);
                }
            } else {
                $posted_types = (isset($_REQUEST["{$circle_type}_circle_post_types"])) 
                ? array_map('sanitize_key', $_REQUEST["{$circle_type}_circle_post_types"]) 
                : [];

                // circle activated
                if (isset($stored_circles[$group_id][$circle_type])) {
                    // already stored (at least for some post types)
                    if ($old = array_diff_key(
                        $stored_circles[$group_id][$circle_type], 
                        array_flip($posted_types))
                    ) {
                        if (!empty($_REQUEST['reviewed_circle_types'])) {
                            $delete_circles = array_intersect_key(
                                array_merge($delete_circles, $old), 
                                array_flip(
                                    array_map('sanitize_key', explode(",", sanitize_text_field($_REQUEST['reviewed_circle_types'])))
                                )
                            );  // don't remove circle activation for types which are not currently registered
                        }
                    }

                    if (!empty($posted_types)) {
                        if ($new = array_diff(
                            $posted_types, 
                            array_flip($stored_circles[$group_id][$circle_type]))
                        ) {
                            $add_circles[$circle_type] = array_merge($add_circles[$circle_type], $new);
                        }
                    }
                } else {
                    // not stored at all yet
                    $add_circles[$circle_type] = array_merge($add_circles[$circle_type], $posted_types);
                }
            }
        }

        global $wpdb;
        if ($delete_circles) {
            $id_csv = implode("','", array_map('intval', $delete_circles));

            // phpcs Note: Direct query on plugin table, used for plugin admin queries

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                "DELETE FROM $wpdb->pp_circles WHERE ID IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        }

        if ($add_circles) {
            foreach (array_keys($add_circles) as $circle_type) {
                foreach ($add_circles[$circle_type] as $post_type) {
                    // phpcs Note: Direct query on plugin table, used for plugin admin queries

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO $wpdb->pp_circles"
                            . " (group_type,group_id,circle_type,post_type) VALUES (%s,%d,%s,%s)", 
                            
                            $group_type, $group_id, $circle_type, $post_type
                        )
                    );
                }
            }
        }
    }
}
