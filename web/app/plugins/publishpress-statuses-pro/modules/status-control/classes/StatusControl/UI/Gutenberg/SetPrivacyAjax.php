<?php

namespace PublishPress\Statuses\StatusControl\UI\Gutenberg;

/*
 * Prevent Gutenberg from imposing "Are you sure you want to unpublish?" dialog and switching to draft status 
 * when user saves to a custom privacy status while post is currently in 'publish' / 'private'
 * 
 * Under this condition, we set that post status immediately on dropdown selection via Ajax.
 * After the ajax request completes, we call wp.data.dispatch('core/editor').refreshPost(); 
 */
class SetPrivacyAjax
{
    public function __construct() 
    {
        global $wpdb, $current_user;

        if (!$post_id = \PP_Statuses_Functions::REQUEST_int('post_id')) {
            // no need for error handling because worst case on update failure is Gutenberg imposes "Are you sure?" and sets to draft
            exit;
        }

        // This Gutenberg workaround is only needed and supported for privacy statuses registered for this post type.
    	if (!in_array($set_status, ['publish', 'future', 'draft'])) {
            $set_status = \PP_Statuses_Functions::REQUEST_key('pp_ajax_set_privacy');

            set_transient("_pp_selected_privacy_{$current_user->ID}_{$post_id}", $set_status, 43200);
            set_transient("_pp_selected_privacy_{$post_id}", $set_status, 43200);

            if (!$post = get_post($post_id)) {
                exit;
            }

            // If post is already set to requested status, indicate success.
            if ($post->post_status == $set_status) {
                echo esc_attr($set_status);
                exit;
            }

	        $valid_statuses = \PP_Statuses_Functions::getPostStatuses(['private' => true, 'post_type' => $post->post_type]);
	        if (!in_array($set_status, $valid_statuses)) {
	            exit;
	        }

	        // Make sure logged user is allowed to set the requested post status
	        if (!PPS::haveStatusPermission('set_status', $post->post_type, $set_status, ['internal_call' => true])) {
	            exit;
	        }

            // Make sure logged user is allowed to edit the post
            if (!current_user_can('edit_post', $post->ID)) {
                exit;
            }

            wp_update_post(['ID' => $post->ID, 'post_status' => $set_status]);
            $post = get_post($post->ID);
            echo esc_attr($post->post_status);
    	} else {
            delete_transient("_pp_selected_privacy_{$current_user->ID}_{$post_id}", $set_status, 43200);
            delete_transient("_pp_selected_privacy_{$post_id}", $set_status, 43200);
        }

        exit;
    }
}
