<?php
namespace PublishPress\Permissions\Compat;

class Mirror
{
    public static function mirrorExceptionItems($via_item_source, $source_id, $target_ids, $postmeta_key = false)
    {
        global $wpdb;

        $target_ids = (array)$target_ids;

        // phpcs Note: Direct query on plugin tables for plugin admin operation

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if (!$source_item_exceptions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.* FROM $wpdb->ppc_exception_items AS i"
                . " INNER JOIN $wpdb->ppc_exceptions AS e ON i.exception_id = e.exception_id"
                . " WHERE e.via_item_source = %s AND i.item_id = %d",

                $via_item_source,
                $source_id
            )
        )) {
            return;
        }

        $source_hashes = [];
        $delete_eitem_ids = [];

        if (('post' == $via_item_source) && $postmeta_key) {
            if (!$mirrored_items = get_post_meta($source_id, $postmeta_key, true))
                $mirrored_items = [];
            else
                $mirrored_items = (array)$mirrored_items;
        }

        $id_csv = implode("','", $target_ids);

        // phpcs Note: Direct query on plugin tables for plugin admin operation

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $target_item_exceptions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.* FROM $wpdb->ppc_exception_items AS i"
                . " INNER JOIN $wpdb->ppc_exceptions AS e ON i.exception_id = e.exception_id"
                . " WHERE e.via_item_source = %s AND item_id IN ('$id_csv')",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                $via_item_source
            )
        );

        foreach ($target_ids as $target_id) {
            $target_hashes = [];
            foreach ($target_item_exceptions as $row) {
                $row->assign_for = trim($row->assign_for);  // work around Project Nami issue (enum column values get padded with trailing spaces)

                if ($target_id == $row->item_id) {
                    $hash = "$row->exception_id|$row->assign_for";
                    $target_hashes[$hash] = $row;
                }
            }

            $comma = '';
            $insert_rows = '';

            foreach ($source_item_exceptions AS $row) {
                $row->assign_for = trim($row->assign_for);  // work around Project Nami issue (enum column values get padded with trailing spaces)

                $inherited_from = ($row->inherited_from) ? $row->inherited_from : $row->eitem_id;

                $hash = "$row->exception_id|$row->assign_for";

                if (!isset($target_hashes[$hash])) {
                    $insert_rows .= "$comma ('$row->exception_id','$target_id','$row->assign_for','$row->eitem_id','$row->assigner_id')";
                    $comma = ',';
                }

                if (empty($source_exceptions_logged)) {
                    $source_hashes[$hash] = $row;
                }
            }
            $source_exceptions_logged = true;;

            if ($insert_rows) {
                // phpcs Note: Direct query on plugin tables for plugin admin operation. Insert clause constructed and sanitized above.

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query("INSERT INTO $wpdb->ppc_exception_items (exception_id, item_id, assign_for, inherited_from, assigner_id) VALUES $insert_rows");
            }

            // delete target exception items which no longer have a corresponding source post exception item (based on source_hashes)
            foreach ($target_hashes AS $hash => $row) {
                if (!isset($source_hashes[$hash])) {
                    $delete_eitem_ids [] = $row->eitem_id;
                }
            }

            if ($delete_eitem_ids) {
                $eitem_csv = implode("','", array_map('intval', $delete_eitem_ids));

                // phpcs Note: Direct query on plugin tables for plugin admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query(
                    "DELETE FROM $wpdb->ppc_exception_items WHERE eitem_id IN ('$eitem_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                );
            }
        }

        if ('post' == $via_item_source) {
            $mirrored_items = array_unique(array_merge($mirrored_items, $target_ids));
            update_post_meta($source_id, $postmeta_key, $mirrored_items);
        }
    }
}
