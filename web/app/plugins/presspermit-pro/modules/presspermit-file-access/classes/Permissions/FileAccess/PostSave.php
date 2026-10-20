<?php
namespace PublishPress\Permissions\FileAccess;

class PostSave
{
    public static function maybeFlushFileRules($object_id, $args = [])
    {
        $defaults = ['current_attachment_ids' => false, 'check_status' => true];
        foreach (array_keys($defaults) as $var) {
            $$var = (isset($args[$var])) ? $args[$var] : $defaults[$var];
        }

        $flush_rules = false;

        $object_type = get_post_field('post_type', $object_id);

        if ($check_status) {
            if (PWP::isBlockEditorActive($object_type)) {
                if ($post_status = get_post_field('post_status', $object_id)) {
                    if (!defined('PRESSPERMIT_LIMIT_HTACCESS_GUTEN_REGEN')) {
                        $status_obj = get_post_status_object($post_status);         // todo: detect status change with Gutenberg
                        if (!empty($status_obj) && (!empty($status_obj->private) || (!empty($status_obj->public) && !defined('PRESSPERMIT_LIMIT_HTACCESS_PUBLIC_REGEN')))) {
                            FileAccess::expireFileRules();
                            return;
                        }
                    }
                }
            } else {
                $new_status = PWP::POST_key('post_status'); // assume for now that XML-RPC will not modify post status
                $new_status_obj = get_post_status_object($new_status);

                if ($last_status = presspermit()->admin()->getLastPostStatus($object_id)) {
                    $last_status_obj = get_post_status_object($last_status);
                }

                if (empty($last_status_obj) || $new_status != $last_status_obj->name) {
                    // if post status has changed to or from publicly published, regenerate file filtering rules
                    if ($new_status_obj && ($new_status_obj->public != $last_status_obj->public)) {  // include $new_status_obj->public because post may be forced to a private status
                        if (!defined('PRESSPERMIT_LIMIT_HTACCESS_STATUS_REGEN')) {
                            $flush_rules = true;
                        }
                    }
                }
            }
        }

        // if exceptions were modified for this object, regenerate file filtering rules
        if (did_action('pp_inserted_exception_item') || did_action('pp_removed_exception_items')) {
            if (!defined('PRESSPERMIT_LIMIT_HTACCESS_EXCEPTION_REGEN')) {
                $flush_rules = true;
            }
        }

        global $wpdb;
        if (!is_array($current_attachment_ids)) {
            // phpcs Note: Direct query on posts table during low-level filtering operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $current_attachment_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = %d",
                    $object_id
                )
            );
        }

        // proceed to logging attachment ids and assigned terms even if flush_rules flag is already set
        $last_attachment_ids = (array)get_post_meta($object_id, '_last_attachment_ids', true);

        // if attachment ids have changed since last post update, regenerate file filtering rules
        if (!$flush_rules && (array_diff($last_attachment_ids, $current_attachment_ids) || array_diff($last_attachment_ids, $current_attachment_ids))) {
            if (!defined('PRESSPERMIT_LIMIT_HTACCESS_ATTACHMENT_REGEN')) {
                $flush_rules = true;
            }
        }

        if ($current_attachment_ids || $last_attachment_ids)
            update_post_meta($object_id, '_last_attachment_ids', $current_attachment_ids);

        // if terms assignment has changed, regenerate file filtering rules
        $last_terms = [];
        foreach (presspermit()->getEnabledTaxonomies(compact('object_type')) as $taxonomy) {
            if (!$flush_rules) {
                $last_terms = (array)get_post_meta($object_id, "_last_{$taxonomy}_ids", true);
                foreach (array_keys($last_terms) as $_key) {
                    if (is_object($last_terms[$_key]))  // storage was fixed in PPFF 2.1.2-beta
                        $last_terms[$_key] = $last_terms[$_key]->term_id;
                    else
                        break;
                }
            }

            $current_terms = wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);

            if (!$flush_rules && (array_diff($last_terms, $current_terms) || array_diff($current_terms, $last_terms))) {
                if (!defined('PRESSPERMIT_LIMIT_HTACCESS_TERM_REGEN')) {
                    $flush_rules = true;
                }
            }

            update_post_meta($object_id, "_last_{$taxonomy}_ids", $current_terms);
        }

        if ($flush_rules) {
            FileAccess::expireFileRules();
        }
    }
}
