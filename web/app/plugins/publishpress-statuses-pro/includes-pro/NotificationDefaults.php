<?php
namespace PublishPress_Statuses;

class NotificationDefaults {
    var $default_notification_workflows = false;

    function __construct() {
    }

    function applyPostDefaults($flush_notifications) {
        $this->queryDefaultNotificationWorkflows(compact('flush_notifications'));

        $this->createDefaultWorkflowNotification('post-declined');
        $this->createDefaultWorkflowNotification('post-status-change');

        update_option('_pp_statuses_default_revision_notifications', true);
    }

    function queryDefaultNotificationWorkflows($args = []) {
        global $wpdb;

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                           // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_statuses_is_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $query = new \WP_Query($query_args);

        $this->default_notification_workflows = [];

        foreach ($query->posts as $row) {
            if ($default_workflow_name = get_post_meta($row->ID, '_psp_default_workflow_name', true)) {

                if (!empty($args['flush_notifications'])
                && in_array($default_workflow_name, ['post-status-change', 'post-declined'])
                ) {
                    wp_delete_post($row->ID, true);
                    continue;
                }

                $this->default_notification_workflows[$default_workflow_name] = $row;
            }
        }
    }

    function createDefaultWorkflowNotification($notification_key)
    {
        if (!empty($this->default_notification_workflows[$notification_key])) {
            if ('trash' == $this->default_notification_workflows[$notification_key]->post_status) {
                wp_delete_post($this->default_notification_workflows[$notification_key]->ID, true);

                global $wpdb;
                $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->posts WHERE ID = %d", $this->default_notification_workflows[$notification_key]->ID));   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            } else {
                return;
            }
        }

        $revision_statuses = function_exists('rvy_revision_statuses') ? rvy_revision_statuses() : ['draft-revision', 'pending-revision', 'future-revision'];

        $post_statuses = \PublishPress_Statuses::instance()->getPostStatuses([], 'names');

        $post_statuses = array_diff(
            $post_statuses,
            $revision_statuses
        );

        switch ($notification_key) {

        case 'post-status-change':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Post status changed', 'publishpress-statuses-pro'),
                'post_name' => 'post-status-changed',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_statuses_is_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'draft',
                    '_psppno_poststatto' => 'pending',
                    '_psppno_contsubject' => 'Post status change: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'The unpublished post &quot;[psppno_post title]&quot; has been moved to a different status.'
                    . '<div>&nbsp;</div><div>New status: [psppno_post new_status]</div>'
                    . '<div>&nbsp;</div><div>Previous status: [psppno_post old_status]</div>'
                    . '<div>&nbsp;</div><div><a href="[psppno_post edit_link]">Edit</a></div>',

                    '_psppno_tositeadmin' => 1,
                    '_psppno_posttype' => 'page',
                    '_psppno_evtcontposttype' => 1,
                    '_psppno_torole' => 1,
                    '_psppno_torolelist' => 'administrator',
                    '_psppno_toauthor' => 1,
                    '_psppno_tofollower' => 1,
                ],
            ];

            $post_id = wp_insert_post($workflow);

            foreach (array_keys(\PublishPress_Statuses::instance()->get_supported_post_types()) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            foreach ($post_statuses as $status) {
                add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                add_post_meta($post_id, '_psppno_poststatto', $status, false);
            }

            break;

        case 'post-declined':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Post deferred or rejected', 'publishpress-statuses-pro'),
                'post_name' => 'post-deferred-or-rejected',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_statuses_is_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'draft',
                    '_psppno_poststatto' => 'rejected',
                    '_psppno_contsubject' => 'Post workflow feedback: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'The post &quot;[psppno_post title]&quot; was deferred or rejected.'
                    . '<div>&nbsp;</div><div>The new status is: &quot;[psppno_post new_status]&quot;</div>'
                    . '<div>&nbsp;</div><div><a href="[psppno_post edit_link]">Edit</a></div>',

                    '_psppno_tositeadmin' => 1,
                    '_psppno_posttype' => 'page',
                    '_psppno_evtcontposttype' => 1,
                    '_psppno_torole' => 1,
                    '_psppno_torolelist' => 'administrator',
                    '_psppno_toauthor' => 1,
                    '_psppno_tofollower' => 1,
                ],
            ];

            $post_id = wp_insert_post($workflow);

            foreach (array_keys(\PublishPress_Statuses::instance()->get_supported_post_types()) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            foreach ($post_statuses as $status) {
                if ('draft' != $status) {
                    add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                }

                add_post_meta($post_id, '_psppno_poststatto', 'deferred', false);
                add_post_meta($post_id, '_psppno_poststatto', 'needs-work', false);
            }

            break;
        }
    }

    /*
     * For currently stored Statuses Notification Workflows, replace postmeta "_psppno_is_default_workflow" with "_psppno_statuses_is_default_workflow"
     *
     * Sharing the _psppno_is_default_workflow meta key with Planner prevents it from creating its own default Notification Workflows.
     * 
     */
    function fixDefaultNotificationsMetaKey() {
        global $wpdb;

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_is_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $query = new \WP_Query($query_args);

        foreach ($query->posts as $row) {
            if ($default_workflow_name = get_post_meta($row->ID, '_psp_default_workflow_name', true)) {
                // Also convert Reviions Pro's default Notification Workflows
                if (in_array(
                    $default_workflow_name, 
                    [
                        'revision-publication',
                        'revision-applied',
                        'revision-scheduled-publication',
                        'revision-scheduled',
                        'revision-declined',
                        'revision-status-change',
                        'revision-submission',
                        'new-revision'
                    ]
                )) {
                    delete_post_meta($row->ID, '_psppno_is_default_workflow');
                    update_post_meta($row->ID, '_psppno_rvy_is_default_workflow', true);
                    clean_post_cache($row->ID);

                // Also convert Statuses Pro's default Notification Workflows
                } elseif (in_array(
                    $default_workflow_name, 
                    [
                        'post-status-change',
                        'post-declined',
                    ]
                )) {
                    delete_post_meta($row->ID, '_psppno_is_default_workflow');
                    update_post_meta($row->ID, '_psppno_statuses_is_default_workflow', true);
                    clean_post_cache($row->ID);
                }
            }
        }

        delete_option('revisionary_pro_fix_default_notifications_meta_key');
    }

    // Restore default Planner Notifications after Planner skipped their creation due to the existence of Statuses Notification Workflows
    function restoreDefaultNotificationWorkflows($args = []) {
        global $publishpress;

        if (empty($publishpress->improved_notifications) 
        || !method_exists($publishpress->improved_notifications, 'create_default_workflow_post_update')
        || !is_callable([$publishpress->improved_notifications, 'create_default_workflow_post_update'])
        ) {
            // Either Improved Notifications is inactive or this version of Planner has these methods protected. 
            // Return without a query, but also leave option uncleared so this will execute successfully after the needed Planner config or update is applied.
            return;
        }

        $restore_defaults = [
            'create_default_workflow_post_update',
            'create_default_workflow_new_draft_created',
            'create_default_workflow_post_published',
            'create_default_workflow_editorial_comment',
        ];

        foreach ($restore_defaults as $method_name) {
            if (method_exists($publishpress->improved_notifications, $method_name)
                && is_callable([$publishpress->improved_notifications, $method_name])
            ) {
                $publishpress->improved_notifications->$method_name();
            }
        }

        delete_option('statuses_pro_restore_notifications');
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function create_default_workflow_revision_status_change()
    {
        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [
                [
                    'key' => '_psp_default_workflow_name',
                    'value' => 'revision-submission',
                ],
            ],
        ];

        $query = new WP_Query($query_args);

        if ($query->have_posts()) {
            return;
        }

        //$view = $this->get_service('view');

        $statuses = rvy_revision_statuses();

        // Post Save
        $workflow = [
            'post_status' => 'publish',
            'post_title' => __('Revision is submitted', 'publishpress-statuses-pro'),
            'post_name' => 'revision-is-submitted',
            'post_type' => 'psppnotif_workflow',
            'meta_input' => [
                '_psppno_statuses_is_default_workflow' => '1',
                '_psp_default_workflow_name' => 'revision-submission',
                '_psppno_evtpostsave' => '1',
                '_psppno_poststatfrom' => 'draft-revision',
                '_psppno_poststatto' => 'pending-revision',
                '_psppno_contsubject' => 'Revision submitted: &quot;[psppno_post title]&quot;',

                '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; was submitted.'
                . '<div></div><div>The new status is: &quot;[psppno_post new_status]&quot;</div>'
                . '<div>Preview / Approval: [psp_revision_preview]</div>'
                . '<div>Revision Queue: [psp_revision_in_queue]</div>'
                . '<div>Edit: [psppno_post edit_link]</div>',

                '_psppno_tositeadmin' => 1,
                '_psppno_posttype' => 'post',
                '_psppno_evtcontposttype' => 1,
                '_psppno_torole' => 1,
                '_psppno_torolelist' => 'administrator',
                '_psppno_toauthor' => 1,
                '_psppno_tofollower' => 1,
            ],
        ];

        $post_id = wp_insert_post($workflow);

        add_post_meta($post_id, '_psppno_posttype', 'page', false);
        add_post_meta($post_id, '_psppno_torolelist', 'editor', false);
    }
    */
}
