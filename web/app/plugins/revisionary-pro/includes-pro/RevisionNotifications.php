<?php
namespace PublishPress\Revisions;

class RevisionNotifications {
    private $notification_workflow_post = false;
    private $notification_event_args = [];

    function __construct() {
        global $pagenow;

        add_filter(
            'publishpress_notif_shortcode_post_data', 
            function($custom_val, $field, $post, $attrs) {
                if ('revision_status' == $field) {
                    $status = $publishpress->getPostStatusBy(
                        'slug',
                        $post->post_mime_type
                    );

                    if (!empty($status) && ('WP_Error' !== get_class($status))) {
                        $custom_val = $status->label;
                    }
                }

                return $custom_val;
            },
            10, 4
        );

        add_action(
            'publishpress_notifications_running_for_post',
            [$this, 'actSendingNotification'],
            5
        );

        add_action(
            'publishpress_notifications_send_notifications_action',
            [$this, 'actSendingNotification'],
            5
        );

        add_action('revisionary_new_revision', [$this, 'act_new_revision'], 10, 2);
        add_action('publishpress_notifications_do_transition_post_status', [$this, 'action_do_notifications_transition_post_status'], 10, 2);
        add_action('post_updated', [$this, 'action_post_updated'], 10, 3);                          // trigger revision status change notification
        add_action('revisionary_revision_published', [$this, 'action_revision_published'], 10, 2);  // trigger revision approved / published notifications
        add_action('revisionary_submitted', [$this, 'action_revision_submitted'], 10, 3);
        add_action('revisionary_declined', [$this, 'action_revision_declined'], 10, 3);
        add_action('revisionary_scheduled', [$this, 'action_revision_scheduled'], 10, 3);

        add_filter(
            'publishpress_notification_statuses',
            function ($statuses, $group) {
                $revision_statuses = [];

                if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                    foreach (['draft-revision', 'pending-revision', 'future-revision'] as $status) {
                        $revision_statuses[$status] = (object) ['name' => $status, 'slug' => $status, 'label' => pp_revisions_status_label($status, 'name')];
                    }
                }

                foreach ($statuses as $k => $status) {
                    if (!empty($status->taxonomy) && ('pp_revision_status' == $status->taxonomy)) {
                        $revision_statuses[$k] = $status;
                        unset($statuses[$k]);
                    }
                }

                $statuses = array_merge($statuses, $revision_statuses);

                return $statuses;
            }, 
            10, 2
        );

        add_filter(
            'publishpress_post_notification_get_workflows',
            function ($workflows, $post = false) {
                return $this->fltNotificationWorkflows($workflows, $post, 'get');
            },
            10, 2
        );

        add_filter(
            'publishpress_post_notification_trigger_workflows',
            function ($workflows, $post = false) {
                return $this->fltNotificationWorkflows($workflows, $post, 'trigger');
            },
            10, 2
        );

        add_filter('publishpress_notif_run_workflow_receivers', [$this, 'fltPlannerReceivers'], 10, 3);

        add_shortcode('psppno_revision', [$this, 'handle_psppno_revision']);
        add_shortcode('psp_revision_preview', [$this, 'handle_psp_revision_preview']);
        add_shortcode('psp_revision_in_queue', [$this, 'handle_psp_revision_in_queue']);

        if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '4.7.3-beta', '<') 
        && is_admin() && !empty($pagenow) && ('post.php' == $pagenow)
        ) {
            add_action('admin_print_scripts', [$this, 'actAppendNotificationHelp'], 99);
        }
    }

    function actSendingNotification($workflow) {
        $this->notification_workflow_post = $workflow->workflow_post;
        $this->notification_event_args = $workflow->event_args;
    }

    function actAppendNotificationHelp() {
        global $pagenow, $post;

        if (!empty($post) && ('psppnotif_workflow' == $post->post_type) && 
            (
                get_post_meta($post->ID, '_psppno_is_rvy_default_workflow', true) 
                || false !== strpos(strval(get_post_meta($post->ID, '_psppno_contbody', true)), 'revision')
            )
        ) :
        ?>
            <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready( function($) {
                $('#publishpress_notif_workflow_help_div div.inside pre').each(function() {
                    var text = $(this).text();
                    text = text.replace('[psppno_post]', '[psppno_post], [psppno_revision]');
                    $(this).text(text);
                });

                $('#publishpress_notif_workflow_help_div div.inside h4:contains("User making changes or comments")').before(
                    '<p><pre>[psp_revision_preview]' + String.fromCharCode(13) + String.fromCharCode(13) + '[psp_revision_in_queue]</pre></p>'
                );
            });
            /* ]]> */
            </script>
        <?php endif;
    }

    function fltNotificationWorkflows ($notifications, $content_post = false, $filter_type = 'trigger') {
        global $post, $revisionary_revision_id;

        if (empty($content_post)) {
            if (!empty($revisionary_revision_id)) {
                $post_id = $revisionary_revision_id;
            } else {
                $post_id = (!empty($post)) ? $post->ID : rvy_detect_post_id();
            }
        } else {
            $post_id = is_object($content_post) ? $content_post->ID : $content_post;
        }

        $is_revision = ($post_id && rvy_in_revision_workflow($post_id)) 
            || (!empty($_REQUEST['action']) && (in_array($_REQUEST['action'], ['revise', 'submit_revision', 'decline_revision', 'approve_revision', 'publish_revision', 'unschedule_revision'])))   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || (!empty($_REQUEST['page']) && ('rvy-revisions' == $_REQUEST['page']))    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || !empty($revisionary_revision_id);

        foreach ($notifications as $key => $notif) {
            $workflow_post = (!empty($notif->workflow_post)) ? $notif->workflow_post : $notif;
            
            $is_default_planner_workflow = get_post_meta($workflow_post->ID, '_psppno_is_default_workflow', true);
            $is_default_revisions_workflow = get_post_meta($workflow_post->ID, '_psppno_is_rvy_default_workflow', true);
            $is_default_statuses_workflow = get_post_meta($workflow_post->ID, '_psppno_statuses_is_default_workflow', true);
            
            $default_workflow_name = get_post_meta($workflow_post->ID, '_psp_default_workflow_name', true);

            if ((!$is_revision && $is_default_revisions_workflow)
                || ($is_revision && 
                    (($is_default_planner_workflow && !$is_default_revisions_workflow)
                    || $is_default_statuses_workflow 
                    || ('notify-when-content-is-published' == $workflow_post->post_name) 
                    || (('trigger' != $filter_type) && ('new-revision' == $default_workflow_name))
                    )
                )
                || (!$is_revision && ('notify-when-content-is-published' == $workflow_post->post_name) && !empty($_REQUEST['action']) && ('revise' == $_REQUEST['action']))     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ) {
                unset($notifications[$key]);
            }

            // If Statuses Pro is not active, one-time default-disabling of "Revision Status Change" to avoid redundancy with "Revision Submitted" and "Revision Declined"
            if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                if ($is_default_revisions_workflow && (('revision-status-change' == $default_workflow_name) || ('revision-status-changed' == $workflow_post->post_name))) {
                    if (!get_option('revisionary_status_change_notif_default_done')) {
                        unset($notifications[$key]);
                        wp_update_post(['ID' => $workflow_post->ID, 'post_status' => 'draft']);
                        update_option('revisionary_status_change_notif_default_done', true);
                    }
                }
            }
        }

        return $notifications;
    }

    public function fltPlannerReceivers($receivers, $post, $event_args) {
        global $revisionary;
        
        if (!isset($event_args['event']) || empty($revisionary)) {
            return $receivers;
        }

        if (defined('PRESSPERMIT_VERSION') && defined('RVY_CONTENT_ROLES') && !defined('SCOPER_DEFAULT_MONITOR_GROUPS') && !defined('REVISIONARY_LIMIT_ADMIN_NOTIFICATIONS') ) {
            $request_notification_events = ['revision-submission'];

            if (defined('PRESSPERMIT_CHANGE_NOTIF_GROUP_STATUS_CHANGE')) {
                $request_notification_events []= 'revision-status-change';
            }

            $scheduled_notification_events = ['revision-scheduled-publication'];

            if (defined('PRESSPERMIT_SCHEDULED_CHANGE_NOTIF_GROUP_SCHEDULED')) {
                $scheduled_notification_events []= 'revision-scheduled';
            }

            if (defined('PRESSPERMIT_SCHEDULED_CHANGE_NOTIF_GROUP_REGULAR_APPROVAL')) {
                $scheduled_notification_events []= 'revision-applied';
            }

            if (defined('PRESSPERMIT_SCHEDULED_CHANGE_NOTIF_GROUP_REGULAR_PUBLICATION')) {
                $scheduled_notification_events []= 'revision-published';
            }

            if (defined('PRESSPERMIT_SCHEDULED_CHANGE_NOTIF_GROUP_DECLINE')) {
                $scheduled_notification_events []= 'revision-declined';
            }
            
            if (in_array($event_args['event'], array_merge($request_notification_events, $scheduled_notification_events))) {
                $monitor_groups_enabled = true;
                $revisionary->content_roles->ensure_init();
                
                if (in_array($event_args['event'], $request_notification_events)) {
                    $recipient_ids = $revisionary->content_roles->get_metagroup_members('Pending Revision Monitors');
                
                } elseif (in_array($event_args['event'], $scheduled_notification_events)) {
                    $recipient_ids = $revisionary->content_roles->get_metagroup_members('Scheduled Revision Monitors');
                } else {
                    $recipient_ids = [];
                }

                // These group memberships cause notifications only if the user can edit the published post
                if ($recipient_ids) {
                    $post_publisher_ids = $revisionary->content_roles->users_who_can('edit_post', $post->ID, ['cols' => 'id', 'user_ids' => $recipient_ids]);
                    $recipient_ids = array_intersect($recipient_ids, $post_publisher_ids);
                }

                foreach ($recipient_ids as $user_id) {
                    $receivers[] = [
                        'receiver' => $user_id,
                        'group' => 'pp_group'
                    ];
                }

                // Also apply access check for recipients selected via Planner Notifications UI ?
                if (rvy_get_option('planner_notifications_access_limited')) {
                    $all_receiver_ids = [];
                    
                    foreach ($receivers as $k => $arr) {
                        if (isset($arr['group']) && ('pp_group' != $arr['group'])) {
                            if (isset($arr['channel']) && isset($arr['receiver']) && ('email' != $arr['channel']) && is_numeric($arr['receiver'])) {
                                $all_receiver_ids []= $arr['receiver'];
                            }
                        }
                    }

                    if ($all_receiver_ids) {
                        $post_publisher_ids = $revisionary->content_roles->users_who_can('edit_post', $post->ID, ['cols' => 'id', 'user_ids' => $all_receiver_ids]);

                        foreach ($receivers as $k => $arr) {
                            if (isset($arr['group']) && ('pp_group' != $arr['group'])) {
                                if (isset($arr['channel']) && isset($arr['receiver']) && ('email' != $arr['channel']) && is_numeric($arr['receiver'])) {
                                    if (!in_array($arr['receiver'], $post_publisher_ids)) {
                                        unset($receivers[$k]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $receivers;
    }

    public function action_do_notifications_transition_post_status($do_notification, $post) {
        if (rvy_in_revision_workflow($post)) {
            // Trigger notification for Revision status change from post_updated action instead, to pass custom parameters.
            $do_notification = false;
        }

        return $do_notification;
    }

    public function act_new_revision($revision_id, $revision_status) {
        if ($revision = get_post($revision_id)) {
            $this->trigger_update_notifications($revision, $revision_status, 'new');
        }
    }

    public function action_post_updated($post_id, $post_after, $post_before) {
        global $publishpress;

        if (empty($publishpress)) {
            return;
        }

        if (($post_after->post_mime_type != $post_before->post_mime_type)
        && (rvy_in_revision_workflow($post_id))
        ) {
            $this->trigger_update_notifications($post_after, $post_after->post_mime_type, $post_before->post_mime_type);
        }
    }

    private function trigger_update_notifications($revision, $revision_status_after, $revision_status_before) {
        global $publishpress;

        $revision = (is_object($revision)) ? $revision : get_post($revision);

        if (empty($revision) || empty($publishpress) || ($revision_status_after == $revision_status_before)) {
            return;
        }

        if (has_action('publishpress_notifications_trigger_workflows')) {
            $params = [
                'event' => 'transition_post_status',
                'event_key' => '_psppno_evtpostsave',
                'user_id' => get_current_user_id(),
                'params' => [
                    'postId' => rvy_post_id($revision->ID),
                    'post_id' => rvy_post_id($revision->ID),
                    'revision_id' => $revision->ID,
                    'new_status' => $revision_status_after,
                    'old_status' => $revision_status_before,
                ],
            ];

            do_action('publishpress_notifications_trigger_workflows', $params);

        } elseif (!empty($publishpress->notifications) && has_action('pp_send_notification_status_update')) {
            $publishpress->notifications->notification_status_change(
                $revision_status_after, 
                $revision_status_before, 
                $revision
            );
        }
    }

    public function action_revision_submitted($post_id, $revision_after, $revision_before) {
        global $publishpress;

        if (empty($publishpress) || empty($publishpress->notifications)) {
            return;
        }

        $post = get_post($post_id);
        if (empty($post) || empty($post->post_status)) {
            return;
        }

        if (has_action('publishpress_notifications_trigger_workflows')) {
            $params = [
                'event' => 'transition_post_status',
                'event_key' => '_psppno_evtpostsave',
                'user_id' => get_current_user_id(),
                'params' => [
                    'postId' => $post_id,
                    'post_id' => $post_id,
                    'revision_id' => $revision_after->ID,
                    'new_status' => $revision_after->post_mime_type,
                    'old_status' => $revision_before->post_mime_type,
                ],
            ];

            do_action('publishpress_notifications_trigger_workflows', $params);

        } elseif (!empty($publishpress->notifications) && has_action('pp_send_notification_status_update')) {
            $publishpress->notifications->notification_status_change(
                $revision_after->post_mime_type, 
                $revision_before->post_mime_type, 
                $revision_after
            );
        }
    }

    public function action_revision_declined($post_id, $revision_after, $revision_before) {
        global $publishpress;

        if (empty($publishpress) || empty($publishpress->notifications)) {
            return;
        }

        // @todo: post validation for this early-executing action handler?

        if (has_action('publishpress_notifications_trigger_workflows')) {
            $params = [
                'event' => 'transition_post_status',
                'event_key' => '_psppno_evtpostsave',
                'user_id' => get_current_user_id(),
                'params' => [
                    'postId' => $post_id,
                    'post_id' => $post_id,
                    'revision_id' => $revision_after->ID,
                    'new_status' => $revision_after->post_mime_type,
                    'old_status' => $revision_before->post_mime_type,
                ],
            ];

            do_action('publishpress_notifications_trigger_workflows', $params);

        } elseif (!empty($publishpress->notifications) && has_action('pp_send_notification_status_update')) {
            $publishpress->notifications->notification_status_change(
                $revision_after->post_mime_type, 
                $revision_before->post_mime_type, 
                $revision_after
            );
        }
    }

    public function action_revision_scheduled($post_id, $revision_after, $revision_before) {
        global $publishpress;

        if (empty($publishpress) || empty($publishpress->notifications)) {
            return;
        }

        // @todo: post validation for this early-executing action handler?

        $status_before = $revision_before->post_mime_type;

        if (!rvy_is_revision_status($status_before)) {
            $status_before = 'auto-draft';
        }

        if (has_action('publishpress_notifications_trigger_workflows')) {
            $params = [
                'event' => 'transition_post_status',
                'event_key' => '_psppno_evtpostsave',
                'user_id' => get_current_user_id(),
                'params' => [
                    'postId' => $post_id,
                    'post_id' => $post_id,
                    'revision_id' => $revision_after->ID,
                    'new_status' => 'future-revision',
                    'old_status' => $status_before,
                ],
            ];

            do_action('publishpress_notifications_trigger_workflows', $params);

        } elseif (!empty($publishpress->notifications) && has_action('pp_send_notification_status_update')) {
            $publishpress->notifications->notification_status_change(
                'future-revision', 
                $status_before, 
                $revision_after
            );
        }
    }

    public function action_revision_published($post, $revision) {
        global $publishpress;

        if (empty($publishpress) || empty($publishpress->notifications)) {
            return;
        }

        if (has_action('publishpress_notifications_trigger_workflows')) {
            $params = [
                'event' => 'transition_post_status',
                'event_key' => '_psppno_evtpostsave',
                'user_id' => get_current_user_id(),
                'params' => [
                    'postId' => $post->ID,
                    'post_id' => $post->ID,
                    'revision_id' => $revision->ID,
                    'new_status' => $post->post_status,
                    'old_status' => $revision->post_mime_type,
                ],
            ];

            do_action('publishpress_notifications_trigger_workflows', $params);

        } elseif (!empty($publishpress->notifications) && has_action('pp_send_notification_status_update')) {
            $publishpress->notifications->notification_status_change(
                $post->post_status, 
                $revision->post_mime_type, 
                $post
            );
        }
    }

    /**
     * Returns the info from the published / main post related to the notification.
     * You can specify which post's property should be printed:
     *
     * [psppno_post title]
     *
     * If no attribute is provided, we use title as default.
     *
     * Accepted attributes:
     *   - id
     *   - title
     *   - url
     *
     * @param array $attrs
     *
     * @return string
     */
    public function handle_psppno_revision($attrs)
    {
        if ($revision = $this->get_revision()) {
            return $this->get_post_data($revision, $attrs);
        }
    }

    /**
     * Returns the revision ID related to the notification.
     *
     * @return int
     */
    protected function get_revision()
    {
        return (!empty($this->notification_event_args['params']['revision_id'])) ? get_post($this->notification_event_args['params']['revision_id']) : 0;
    }

    public function handle_psp_revision_preview($attrs)
    {
        if (empty($this->notification_event_args) 
        || empty($this->notification_event_args['params']) 
        || empty($this->notification_event_args['params']['revision_id'])
        ) {
            return;
        }

        $revision_id = $this->notification_event_args['params']['revision_id'];

        if (!function_exists('rvy_in_revision_workflow') || !rvy_in_revision_workflow($revision_id)) {
            return;
        }

        return rvy_preview_url($revision_id);
    }

    public function handle_psp_revision_in_queue($attrs)
    {
        $post_id = $this->notification_event_args['params']['post_id'];
        $revision_id = $this->notification_event_args['params']['revision_id'];

        if (empty($this->notification_event_args) 
        || empty($this->notification_event_args['params']) 
        || empty($this->notification_event_args['params']['revision_id'])
        ) {
            return;
        }

        $post_id = $this->notification_event_args['params']['post_id'];
        $revision_id = $this->notification_event_args['params']['revision_id'];

        if (!function_exists('rvy_in_revision_workflow') || !rvy_in_revision_workflow($revision_id)) {
            return;
        }

        return rvy_admin_url("admin.php?page=revisionary-q&published_post={$post_id}&all=1");
    }


    // @todo: shared implementation with Planner?

    /**
     * Returns the post's info. You can specify which post's property should be
     * printed passing that on the $attrs.
     *
     * If more than one attribute is given, we returns all the data
     * separated by comma (default) or specified separator, in the order it was
     * received.
     *
     * If no attribute is provided, we use title as default.
     *
     * Accepted attributes:
     *   - id
     *   - title
     *   - permalink
     *   - date
     *   - time
     *   - old_status
     *   - new_status
     *   - separator
     *   - edit_link
     *
     * @param WP_Post $post
     * @param array $attrs
     *
     * @return string
     * @throws Exception
     *
     */
    protected function get_post_data($post, $attrs)
    {
        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs = ['title'];
        }

        // Set the separator
        if (!isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the post's info
        $info = [];

        foreach ($attrs as $field) {
            $data = $this->get_post_field($post, $field, $attrs);

            if (false !== $data) {
                $info[] = $data;
            }
        }

        return implode($attrs['separator'], $info);
    }

    private function get_post_field($post, $field, $attrs)
    {
        $result = false;

        if (is_null($field)) {
            $field = 'title';
        }

        switch ($field) {
            case 'id':
                $result = $post->ID;
                break;

            case 'title':
                $result = $post->post_title;
                break;

            case 'post_type':
                $postType = get_post_type_object($post->post_type);

                if (!empty($postType) && !is_wp_error($postType)) {
                    $result = $postType->labels->singular_name;
                }
                break;

            case 'permalink':
                $result = get_permalink($post->ID);
                break;

            case 'date':
                $result = get_the_date('', $post);
                break;

            case 'time':
                $result = get_the_time('', $post);
                break;

            case 'old_status':
            case 'new_status':
                $status_name = apply_filters('publishpress_notifications_status', $this->notification_event_args['params'][$field], $post);

                $status = get_post_status_object($status_name);

                if (empty($status) || 'WP_Error' === get_class($status)) {
                    break;
                }

                $result = $status->label;
                break;

            case 'content':
                $result = $post->post_content;
                break;

            case 'excerpt':
                $result = $post->post_excerpt;
                break;

            case 'edit_link':
                $admin_path = 'post.php?post=' . $post->ID . '&action=edit';
                $result     = htmlspecialchars_decode(admin_url($admin_path));
                break;

            case 'author_display_name':
            case 'author_email':
            case 'author_login':
                $author_data = get_userdata($post->post_author);

                $field_map = [
                    'author_display_name' => 'display_name',
                    'author_email'        => 'user_email',
                    'author_login'        => 'user_login',
                ];

                $user_field = $field_map[$field];
                $data       = $author_data->{$user_field};

                $result = apply_filters('pp_get_author_data', $data, $field, $post);
                break;

            default:
                // Meta data attribute
                if (0 === strpos($field, 'meta')) {
                    $arr = explode(':', $field);
                    if (!empty($arr[1])) {
                        if (substr_count($arr[1], '.')) {
                            $meta_fragments = explode('.', $arr[1]);

                            $meta_name      = $meta_fragments[0];
                            $meta_sub_field = $meta_fragments[1];
                        } else {
                            $meta_name      = $arr[1];
                            $meta_sub_field = null;
                        }

                        $meta = get_post_meta($post->ID, $meta_name, true);
                        if ($meta && is_scalar($meta)) {
                            if ('meta-date' == $arr[0]) {
                                $result = date_i18n(get_option('date_format'), $meta);
                            } elseif ('meta-relationship' == $arr[0] || 'meta-post' == $arr[0]) {
                                $rel_post = get_post((int)$meta);

                                if (!empty($rel_post) && !is_wp_error($rel_post)) {
                                    $result = $this->get_post_field($rel_post, $meta_sub_field, $attrs);
                                }
                            } elseif ('meta-user' == $arr[0] || strpos($meta_name, '_pp_editorial_meta_user') === 0) {
                                $rel_user = get_user_by('ID', (int)$meta);

                                if (!empty($rel_user) && !is_wp_error($rel_user)) {
                                    $result = $this->get_user_field($rel_user, $meta_sub_field, $attrs);
                                }
                            } else {
                                $result = $meta;
                            }
                        } elseif (is_array($meta)) {
                            if (!empty($meta)) {
                                switch ($arr[0]) {
                                    case 'meta-post':
                                    case 'meta-relationship':
                                        if (is_null($meta_sub_field)) {
                                            $meta_sub_field = 'title';
                                        }

                                        $rel_result = [];

                                        foreach ($meta as $rel_post_ID) {
                                            $rel_post = get_post($rel_post_ID);

                                            if (!empty($rel_post) && !is_wp_error($rel_post)) {
                                                $rel_result[] = $this->get_post_field($rel_post, $meta_sub_field, $attrs);
                                            }
                                        }

                                        if (!empty($rel_result)) {
                                            $result = implode($attrs['separator'], $rel_result);
                                        }
                                        break;

                                    case 'meta-link':
                                        $result = sprintf(
                                            '<a href="%s" target="%s">%s</a>',
                                            $meta['url'],
                                            $meta['target'],
                                            $meta['title']
                                        );
                                        break;

                                    case 'meta-term':
                                        if (is_null($meta_sub_field)) {
                                            $meta_sub_field = 'name';
                                        }

                                        $rel_result = [];

                                        foreach ($meta as $rel_term_ID) {
                                            $rel_term = get_term($rel_term_ID);

                                            if (!empty($rel_term) && !is_wp_error($rel_term)) {
                                                $rel_result[] = $this->get_term_field($rel_term, $meta_sub_field, $attrs);
                                            }
                                        }

                                        if (!empty($rel_result)) {
                                            $result = implode($attrs['separator'], $rel_result);
                                        }
                                        break;

                                    case 'meta-user':
                                        if (is_null($meta_sub_field)) {
                                            $meta_sub_field = 'name';
                                        }

                                        $rel_result = [];

                                        foreach ($meta as $rel_user_ID) {
                                            $rel_user = get_user_by('ID', $rel_user_ID);

                                            if (!empty($rel_user) && !is_wp_error($rel_user)) {
                                                $rel_result[] = $this->get_user_field($rel_user, $meta_sub_field, $attrs);
                                            }
                                        }

                                        if (!empty($rel_result)) {
                                            $result = implode($attrs['separator'], $rel_result);
                                        }
                                        break;
                                }
                            }
                        }
                    }
                } else {
                    if ($custom = apply_filters(
                        'publishpress_notif_shortcode_post_data',
                        false,
                        $field,
                        $post,
                        $attrs
                    )) {
                        $result = $custom;
                    }
                }

                break;
        }

        return $result;
    }

    private function get_term_field($term, $field, $attrs)
    {
        $result = false;

        if (is_null($field)) {
            $field = 'name';
        }

        switch ($field) {
            case 'id':
                $result = $term->term_id;
                break;

            case 'name':
                $result = $term->name;
                break;

            case 'slug':
                $result = $term->slug;
                break;

            default:
                if ($custom = apply_filters(
                    'publishpress_notif_shortcode_term_data',
                    false,
                    $field,
                    $term,
                    $attrs
                )) {
                    $result = $custom;
                }
        }

        return $result;
    }

    /**
     * Returns the user's info. You can specify which user's property should be
     * printed passing that on the $attrs.
     *
     * If more than one attribute is given, we returns all the data
     * separated by comma (default) or specified separator, in the order it was
     * received.
     *
     * If no attribute is provided, we use display_name as default.
     *
     * Accepted attributes:
     *   - id
     *   - login
     *   - url
     *   - display_name
     *   - first_name
     *   - last_name
     *   - email
     *   - separator
     *
     * @param WP_User $user
     * @param array $attrs
     *
     * @return string
     */
    protected function get_user_data($user, $attrs)
    {
        if (!is_array($attrs)) {
            if (!empty($attrs)) {
                $attrs[] = $attrs;
            } else {
                $attrs = [];
            }
        }

        // No attributes? Set the default one.
        if (empty($attrs)) {
            $attrs[] = 'display_name';
        }

        // Set the separator
        if (!isset($attrs['separator'])) {
            $attrs['separator'] = ', ';
        }

        // Get the user's info
        $info = [];

        foreach ($attrs as $index => $field) {
            $data = $this->get_user_field($user, $field, $attrs);

            if (false !== $data) {
                $info[] = $data;
            }
        }

        return implode($attrs['separator'], $info);
    }

    private function get_user_field($user, $field, $attrs)
    {
        $result = false;

        if (empty($field)) {
            $field = 'name';
        }

        switch ($field) {
            case 'id':
                $result = $user->ID;
                break;

            case 'login':
                $result = $user->user_login;
                break;

            case 'url':
                $result = $user->user_url;
                break;

            case 'name':
            case 'display_name':
                $result = $user->display_name;
                break;

            case 'first_name':
                $result = $user->first_name;
                break;

            case 'last_name':
                $result = $user->last_name;
                break;

            case 'email':
                $result = $user->user_email;
                break;

            default:
                if ($custom = apply_filters(
                    'publishpress_notif_shortcode_user_data',
                    false,
                    $field,
                    $user,
                    $attrs
                )) {
                    $result = $custom;
                }
                break;
        }

        return $result;
    }
}
