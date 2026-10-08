<?php
namespace PublishPress\Statuses\StatusControl;

/**
 * Triggers class
 *
 * Deals with content, user or site changes which may require a 
 * corresponding permissions data update or other action. 
 * 
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2026, PublishPress
 *
 */
class Triggers
{
    function __construct() {
        // This script normally executes on plugin load, 
        // but can be bypassed for front end URLs if defined('PP_NO_FRONTEND_ADMIN')
        //
        add_action('save_post', [$this, 'actSavePost'], 10, 2);
        add_action('delete_post', [$this, 'actDeletePost'], 10, 3);

        add_filter('wp_insert_post_data', [$this, 'fltPostData'], 50, 2);
    }

    function actSavePost($post_id, $object)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ('revision' == $object->post_type) return;

        if (function_exists('presspermit') && !empty(presspermit()->flags['ignore_save_post'])) {
            return;
        }

        if (!is_object($object)) {
            if (!$object = get_post($post_id)) {
                return;
            }
        }

        if (isset($_REQUEST["pp_ajax_set_privacy"])) {
            check_ajax_referer('pp-ajax');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ((!isset($_REQUEST['_wpnonce']) || 
            (
            ($post_id && !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "update-post_{$post_id}"))               // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            && (!empty($object) && !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "add-{$object->post_type}"))   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            ))  
            && (!isset($_REQUEST['_inline_edit']) ||
            (!wp_verify_nonce(wp_unslash($_REQUEST['_inline_edit']), 'inlineeditnonce'))                              // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            )
        ) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || (\PP_Statuses_Functions::is_REQUEST('action', 'untrash'))
            || ('revision' == $object->post_type)  // operations in this function do not apply to revision save
        ) {
            return;
        }

        if (defined('REVISIONARY_VERSION')) {
            global $revisionary;
            if (!empty($revisionary->admin->revision_save_in_progress)) {
                $revisionary->admin->revision_save_in_progress = false;
                return;
            }
        }

        if (is_post_type_hierarchical($object->post_type)) {
            $set_subpost_visibility = false;
            
            if (\PublishPress_Statuses::instance()->doing_rest) {
                $rest = \PublishPress_Statuses\REST::instance();
                $set_subpost_visibility = isset($rest->params['pp_subpost_visibility']) ? $rest->params['pp_subpost_visibility'] : false;

            } elseif (\PP_Statuses_Functions::empty_POST() || \PP_Statuses_Functions::isBlockEditorActive($object->post_type)) {
                return;
            } else {
                $set_subpost_visibility = isset($_POST['ch_visibility']) ? sanitize_key($_POST['ch_visibility']) : false;
            }

            if (false !== $set_subpost_visibility) {
                $set_subpost_visibility = sanitize_key($set_subpost_visibility);

                require_once(PP_STATUS_CONTROL_CLASSPATH . '/ItemSave.php');
                ItemSave::post_update_force_visibility($object, ['children' => $set_subpost_visibility]);
            }
        }
    }

    function actDeletePost($object_id)
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/ItemDelete.php');
        ItemDelete::actDeletePost($object_id);
    }

    function fltPostData($post_data, $post_arr) {
        // If 'pre_post_status' filtered the status to a custom privacy status, 
        // re-filter on 'wp_insert_post_data' to counteract status being subsequently forced back to 'draft'
        if (('draft' == $post_data['post_status'])
        && !empty($post_arr['ID'])
        && !empty(\PublishPress_Statuses::instance()->filtered_post_status[$post_arr['ID']])
        && (\PublishPress_Statuses::instance()->filtered_post_status[$post_arr['ID']] != $post_data['post_status'])
        ) {
            if ($status_obj = get_post_status_object(\PublishPress_Statuses::instance()->filtered_post_status[$post_arr['ID']])) {
                if (('private' != $status_obj->name) && !empty($status_obj->private)) {
                    $post_data['post_status'] = \PublishPress_Statuses::instance()->fltAppyDefaultVisibility($post_data['post_status'], ['filter_draft_status' => true]);
                }
            }
        }

        return $post_data;
    }
}
