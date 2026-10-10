<?php
namespace PublishPress\Revisions;

class NotificationDefaults {
    var $default_notification_workflows = false;

    function __construct() {
    }

    function applyRevisionDefaults($flush_notifications) {
        if (!is_array($this->default_notification_workflows)) {
            $this->queryDefaultNotificationWorkflows(compact('flush_notifications'));
        }

        $this->createDefaultWorkflowNotification('revision-publication');
        $this->createDefaultWorkflowNotification('revision-applied');
        $this->createDefaultWorkflowNotification('revision-scheduled-publication');
        $this->createDefaultWorkflowNotification('revision-scheduled');
        $this->createDefaultWorkflowNotification('revision-declined');
        $this->createDefaultWorkflowNotification('revision-status-change');

        $this->createDefaultWorkflowNotification('revision-submission');


        $this->createDefaultWorkflowNotification('new-revision');

        update_option('_pp_statuses_planner_default_revision_notifications', true);
    }

    function queryDefaultNotificationWorkflows($args = []) {
        global $wpdb;

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_is_rvy_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $query = new \WP_Query($query_args);

        $this->default_notification_workflows = [];

        foreach ($query->posts as $row) {
            if ($default_workflow_name = get_post_meta($row->ID, '_psp_default_workflow_name', true)) {

                if (!empty($args['flush_notifications'])
                && in_array(
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
                    wp_delete_post($row->ID, true);
                    continue;
                }

                $this->default_notification_workflows[$default_workflow_name] = $row;
            }
        }
    }

    // Use psppno_revision shortcode instead of psppno_post for some notification content
    function fixNotificationShortcodes() {
        global $wpdb;

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_is_rvy_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $rvy_defaults_query = new \WP_Query($query_args);

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_is_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $planner_defaults_query = new \WP_Query($query_args);

        $planner_defaults_query = new \WP_Query($query_args);
        foreach (array_merge($rvy_defaults_query->posts, $planner_defaults_query->posts)  as $row) {
            $notif_content = get_post_meta($row->ID, '_psppno_contbody', true);
            $_notif_content = $notif_content;

            if ((false !== strpos($_notif_content, 'revision')) && (false !== strpos($_notif_content, 'created by [psppno_post author_display_name]'))) {
                $_notif_content = str_replace('created by [psppno_post author_display_name]', 'created by [psppno_revision author_display_name]', $_notif_content);
            }

            if ((false !== strpos($_notif_content, 'revision')) && (false === strpos($row->post_name, 'published')) && (false === strpos($row->post_name, 'applied')) && (false !== strpos($_notif_content, '[psppno_post new_status]'))) {
                $_notif_content = str_replace('[psppno_post new_status]', '[psppno_revision new_status]', $_notif_content);
            }

            if ((false !== strpos($_notif_content, 'revision')) && (false !== strpos($_notif_content, '[psppno_post old_status]'))) {
                $_notif_content = str_replace('[psppno_post old_status]', '[psppno_revision old_status]', $_notif_content);
            }

            if ((false !== strpos($_notif_content, 'revision')) && (false !== strpos($_notif_content, '[psppno_post edit_link]'))) {
                $_notif_content = str_replace('[psppno_post edit_link]', '[psppno_revision edit_link]', $_notif_content);
            }

            if ((false !== strpos($_notif_content, 'revision')) && (false !== strpos($_notif_content, 'Scheduled publication time: [psppno_post date] [psppno_post time]'))) {
                $_notif_content = str_replace('Scheduled publication time: [psppno_post date] [psppno_post time]', 'Scheduled publication time: [psppno_revision date] [psppno_revision time]', $_notif_content);
            }

            if ($_notif_content != $notif_content) {
                update_post_meta($row->ID, '_psppno_contbody', $_notif_content);
            }
        }

        delete_option('revisionary_pro_fix_default_notification_shortcodes');
    }

    /*
     * For currently stored Revisions Notification Workflows, replace postmeta "_psppno_is_default_workflow" with "_psppno_is_rvy_default_workflow"
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
                    update_post_meta($row->ID, '_psppno_is_rvy_default_workflow', true);
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

    function fixRevisionScheduledNotification() {
        global $wpdb;

        $query_args = [
            'post_type' => 'psppnotif_workflow',
            'meta_query' => [                                       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => '_psppno_is_rvy_default_workflow',
                    'value' => '1',
                ],
            ],
        ];

        $query = new \WP_Query($query_args);

        foreach ($query->posts as $row) {
            if ('revision-scheduled' == get_post_meta($row->ID, '_psp_default_workflow_name', true)) {
                add_post_meta($row->ID, '_psppno_poststatfrom', 'new', false);
                add_post_meta($row->ID, '_psppno_poststatfrom', 'auto-draft', false);
                clean_post_cache($row->ID);

                break;
            }
        }

        delete_option('revisionary_pro_fix_revision_scheduled_notification');
    }

    // Restore default Planner Notifications after Planner skipped their creation due to the existence of Revisions Notification Workflows
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
                if (defined('PUBLISHPRESS_BASE_PATH') && !class_exists('PublishPress\Notifications\Workflow\Step\Event\Filter\Post_Status')) {
                    // Planner create_default methods rely on this class for a constant definition.
                    if (file_exists(PUBLISHPRESS_BASE_PATH . '/lib/Notifications/Workflow/Step/Event/Filter/Post_Status.php')) {
                        require_once(PUBLISHPRESS_BASE_PATH . '/lib/Notifications/Workflow/Step/Event/Filter/Post_Status.php');
                    } else {
                        continue;
                    }
                }

                $publishpress->improved_notifications->$method_name();
            }
        }

        delete_option('revisionary_pro_restore_notifications');
    }

    function createDefaultWorkflowNotification($notification_key)
    {
        global $revisionary;

        if (!empty($this->default_notification_workflows[$notification_key])) {
            if ('trash' == $this->default_notification_workflows[$notification_key]->post_status) {
                wp_delete_post($this->default_notification_workflows[$notification_key]->ID, true);

                global $wpdb;
                $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->posts WHERE ID = %d", $this->default_notification_workflows[$notification_key]->ID));   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            } else {
                return;
            }
        }

        $revision_statuses = rvy_revision_statuses();

        $post_statuses = (class_exists('PublishPress_Statuses')) ? \PublishPress_Statuses::instance()->getPostStatuses([], 'names') : get_post_stati(['internal' => false], 'names');

        $post_statuses = array_diff(
            $post_statuses,
            $revision_statuses
        );

        $enabled_post_types = (!empty($revisionary->enabled_post_types)) ? $revisionary->enabled_post_types : get_option('rvy_enabled_post_types', false);

        if (!$enabled_post_types) {
            $enabled_post_types = array_fill_keys(['post', 'page'], true);
        } else {
            $enabled_post_types = (array) $enabled_post_types;
        }

        switch ($notification_key) {

        case 'new-revision':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('New Revision created', 'revisionary-pro'),
                'post_name' => 'new-revision-created',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_is_rvy_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'new',
                    '_psppno_poststatto' => 'draft-revision',
                    '_psppno_contsubject' => 'New Revision of &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'A new revision of &quot;[psppno_post title]&quot; has been created by [psppno_post author_display_name].'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_preview]">Preview / Approve</a></div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_in_queue]">New Revisions</a></div>'
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

            foreach (array_keys($enabled_post_types) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            add_post_meta($post_id, '_psppno_poststatfrom', 'auto-draft', false);
            add_post_meta($post_id, '_psppno_poststatfrom', 'new', false);

            add_post_meta($post_id, '_psppno_poststatto', 'draft', false);

            break;

        case 'revision-submission':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Revision submitted', 'revisionary-pro'),
                'post_name' => 'revision-is-submitted',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_is_rvy_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'draft-revision',
                    '_psppno_poststatto' => 'pending-revision',
                    '_psppno_contsubject' => 'Revision submitted: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; was submitted.'
                    . '<div>&nbsp;</div><div>New status: &quot;[psppno_post new_status]&quot;</div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_preview]">Preview / Approval</a></div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_in_queue]">New Revisions</a></div>'
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

            add_post_meta($post_id, '_psppno_poststatfrom', 'auto-draft', false);
            add_post_meta($post_id, '_psppno_poststatfrom', 'new', false);
            add_post_meta($post_id, '_psppno_poststatto', 'pending', false);

            foreach (array_keys($enabled_post_types) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);
        
            foreach ($revision_statuses as $status) {
                if ('future-revision' != $status) {
                    add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                }
            }

            break;

        case 'revision-declined':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Revision deferred or rejected', 'revisionary-pro'),
                'post_name' => 'revision-deferred-or-rejected',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_is_rvy_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'draft-revision',
                    '_psppno_poststatto' => 'revision-rejected',
                    '_psppno_contsubject' => 'Revision feedback: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; was deferred or rejected.'
                    . '<div>&nbsp;</div><div>New status: &quot;[psppno_post new_status]&quot;</div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_in_queue]">New Revisions</a></div>'
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

            foreach (array_keys($enabled_post_types) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            foreach ($revision_statuses as $status) {
                if ('draft-revision' != $status) {
                    add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                }
            }

            add_post_meta($post_id, '_psppno_poststatto', 'draft-revision', false);

            foreach (['revision-deferred', 'revision-needs-work'] as $status) {
                add_post_meta($post_id, '_psppno_poststatto', $status, false);
            }

            if ($status = apply_filters('revisionary_revision_decline_status', 'draft-revision', 0)) {
                if (!in_array($status, ['draft-revision', 'revision-rejected', 'revision-needs-work', 'revision-deferred', 'revision-declined'])) {
                    add_post_meta($post_id, '_psppno_poststatto', $status, false);
                }
            }

            break;

        case 'revision-scheduled':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Revision scheduled', 'revisionary-pro'),
                'post_name' => 'revision-is-scheduled',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_is_rvy_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatto' => 'future-revision',
                    '_psppno_poststatfrom' => 'draft-revision',
                    '_psppno_contsubject' => 'Revision scheduled: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; was scheduled for publication.'
                    . '<div>&nbsp;</div><div>Scheduled publication time: [psppno_post date] [psppno_post time]</div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_preview]">Preview</a></div>'
                    . '<div>&nbsp;</div><div><a href="[psp_revision_in_queue]">New Revisions</a></div>'
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

            foreach (array_keys($enabled_post_types) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            foreach ($revision_statuses as $status) {
                if (!in_array($status, ['draft-revision', 'future-revision'])) {
                    add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                }
            }

            add_post_meta($post_id, '_psppno_poststatfrom', 'new', false);
            add_post_meta($post_id, '_psppno_poststatfrom', 'auto-draft', false);

            break;

        case 'revision-publication':
                $workflow = [
                    'post_status' => 'publish',
                    'post_title' => __('Revision published', 'revisionary-pro'),
                    'post_name' => 'revision-is-published',
                    'post_type' => 'psppnotif_workflow',
                    'meta_input' => [
                        '_psppno_is_rvy_default_workflow' => '1',
                        '_psp_default_workflow_name' => $notification_key,
                        '_psppno_evtpostsave' => '1',
                        '_psppno_poststatfrom' => 'pending-revision',
                        '_psppno_poststatto' => 'publish',
                        '_psppno_contsubject' => 'Revision published: &quot;[psppno_post title]&quot;',
    
                        '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; has been published.'
                        . '<div>&nbsp;</div><div>Post status: [psppno_post new_status]</div>'
                        . '<div>&nbsp;</div><div><a href="[psppno_post permalink]">View</a></div>',
    
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
    
                foreach (array_keys($enabled_post_types) as $post_type) {
                    if ('page' != $post_type) {
                        add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                    }
                }
    
                add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

                $private_statuses = (class_exists('PublishPress_Statuses')) ? \PublishPress_Statuses::instance()->getPostStatuses(['private' => true], 'names') : get_post_stati(['private' => true], 'names');

                foreach ($private_statuses as $status) {
                    add_post_meta($post_id, '_psppno_poststatto', $status, false);
                }

                foreach ($revision_statuses as $status) {
                    if (!in_array($status, ['pending-revision', 'future-revision'])) {
                        add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                    }
                }
    
                break;

        case 'revision-applied':
                $workflow = [
                    'post_status' => 'publish',
                    'post_title' => __('Revision applied', 'revisionary-pro'),
                    'post_name' => 'revision-is-applied',
                    'post_type' => 'psppnotif_workflow',
                    'meta_input' => [
                        '_psppno_is_rvy_default_workflow' => '1',
                        '_psp_default_workflow_name' => $notification_key,
                        '_psppno_evtpostsave' => '1',
                        '_psppno_poststatfrom' => 'pending-revision',
                        '_psppno_poststatto' => 'draft',
                        '_psppno_contsubject' => 'Revision applied: &quot;[psppno_post title]&quot;',
    
                        '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; has been applied to an unpublished post.'
                        . '<div>&nbsp;</div><div>Post status: [psppno_post new_status]</div>'
                        . '<div>&nbsp;</div><div><a href="[psppno_post permalink]">View</a></div>',
    
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
    
                foreach (array_keys($enabled_post_types) as $post_type) {
                    if ('page' != $post_type) {
                        add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                    }
                }
    
                add_post_meta($post_id, '_psppno_torolelist', 'editor', false);
    
                $moderation_statuses = (class_exists('PublishPress_Statuses')) ? \PublishPress_Statuses::instance()->getPostStatuses(['moderation' => true], 'names') : ['draft', 'pending'];

                foreach ($moderation_statuses as $status) {
                    add_post_meta($post_id, '_psppno_poststatto', $status, false);
                }

                foreach ($revision_statuses as $status) {
                    if (!in_array($status, ['pending-revision', 'future-revision'])) {
                        add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                    }
                }
    
                break;

        case 'revision-scheduled-publication':
            $workflow = [
                'post_status' => 'publish',
                'post_title' => __('Scheduled Revision published', 'revisionary-pro'),
                'post_name' => 'scheduled-revision-is-published',
                'post_type' => 'psppnotif_workflow',
                'meta_input' => [
                    '_psppno_is_rvy_default_workflow' => '1',
                    '_psp_default_workflow_name' => $notification_key,
                    '_psppno_evtpostsave' => '1',
                    '_psppno_poststatfrom' => 'future-revision',
                    '_psppno_poststatto' => 'publish',
                    '_psppno_contsubject' => 'Scheduled Revision published: &quot;[psppno_post title]&quot;',

                    '_psppno_contbody' => 'A scheduled revision of &quot;[psppno_post title]&quot; has been published.'
                    . '<div>&nbsp;</div><div>Post status: [psppno_post new_status]</div>'
                    . '<div>&nbsp;</div><div><a href="[psppno_post permalink]">View</a></div>',

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

            foreach (array_keys($enabled_post_types) as $post_type) {
                if ('page' != $post_type) {
                    add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                }
            }

            add_post_meta($post_id, '_psppno_torolelist', 'editor', false);

            foreach ($post_statuses as $status) {
                if ('publish' != $status) {
                    add_post_meta($post_id, '_psppno_poststatto', $status, false);
                }
            }

            break;

        case 'revision-status-change':
                $workflow = [
                    'post_status' => 'publish',
                    'post_title' => __('Revision status changed', 'revisionary-pro'),
                    'post_name' => 'revision-status-changed',
                    'post_type' => 'psppnotif_workflow',
                    'meta_input' => [
                        '_psppno_is_rvy_default_workflow' => '1',
                        '_psp_default_workflow_name' => $notification_key,
                        '_psppno_evtpostsave' => '1',
                        '_psppno_poststatfrom' => 'draft-revision',
                        '_psppno_poststatto' => 'pending-revision',
                        '_psppno_contsubject' => 'Revision status change: &quot;[psppno_post title]&quot;',
    
                        '_psppno_contbody' => 'A revision of &quot;[psppno_post title]&quot; has been moved to a different status.'
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
    
                foreach (array_keys($enabled_post_types) as $post_type) {
                    if ('page' != $post_type) {
                        add_post_meta($post_id, '_psppno_posttype', $post_type, false);
                    }
                }

                add_post_meta($post_id, '_psppno_torolelist', 'editor', false);
    
                foreach ($revision_statuses as $status) {
                    add_post_meta($post_id, '_psppno_poststatfrom', $status, false);
                    add_post_meta($post_id, '_psppno_poststatto', $status, false);
                }
    
                break;

        default:
        }
    }
}
