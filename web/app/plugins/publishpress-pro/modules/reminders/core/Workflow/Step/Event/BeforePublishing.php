<?php
/**
 * @package     PublishPress\Reminders
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Reminders\Workflow\Step\Event;

use PublishPress\Addon\Reminders\Workflow\Step\Event\Filter\BeforeTimer;
use PublishPress\AsyncNotifications\WPCronAdapter;
use PublishPress\Notifications\Workflow\Step\Event\Base;
use PublishPress\Notifications\Workflow\Step\Event_Content\Filter\Category;
use PublishPress\Notifications\Workflow\Step\Event_Content\Filter\Post_Type as Post_Type_Filter;

class BeforePublishing extends Base
{
    const META_KEY_SELECTED = '_psppno_evtbeforepublishing';

    const META_VALUE_SELECTED = 'beforepublishing';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = 'beforepublishing';
        $this->label = __('Before the selected publish date', 'publishpress-pro');

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_events_metakeys', [$this, 'filter_events_metakeys']);
        add_filter('publishpress_notif_workflow_actions', [$this, 'filter_workflow_actions']);
        add_action('publishpress_before_publishing_notifications', [$this, 'processBeforePublishingJobs']);
        add_filter('publishpress_notifications_action_params_for_log', [$this, 'filter_action_params_for_log'], 10, 2);
        add_filter('publishpress_notifications_event_label', [$this, 'filter_event_label'], 10, 2);
        add_action('publishpress_notif_notification_sending', [$this, 'registerSentFlag'], 20, 7);
    }

    /**
     * Filters and returns the arguments for the query which locates
     * workflows that should be executed.
     *
     * @param array $query_args
     * @param array $action_args
     *
     * @return array
     */
    public function filter_running_workflow_query_args($query_args, $action_args)
    {
        if ($this->should_ignore_event_on_query($action_args)) {
            return $query_args;
        }

        if ('before_publishing_reminder' === $action_args['event']) {
            $query_args['meta_query'][] = [
                'key'     => static::META_KEY_SELECTED,
                'value'   => 1,
                'type'    => 'BOOL',
                'compare' => '=',
            ];

            // Check the filters
            $filters = $this->get_filters();

            foreach ($filters as $filter) {
                $query_args = $filter->get_run_workflow_query_args($query_args, $action_args);
            }
            // unset needs_event_filter since atleast this event already filter the query
            if (isset($query_args['needs_event_filter'])) {
                unset($query_args['needs_event_filter']);
            }
        }

        return $query_args;
    }

    /**
     * Method to return a list of fields to display in the filter area
     *
     * @param array
     *
     * @return array
     */
    protected function get_filters($filters = [])
    {
        if (!empty($this->cache_filters)) {
            return $this->cache_filters;
        }

        $step_name = $this->attr_prefix . '_' . $this->name;

        $filters[] = new BeforeTimer($step_name);

        return parent::get_filters($filters);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getFilteredWorkflows()
    {
        $workflowController = \publishpress::instance()->get_service('workflows_controller');

        $args = [
            'event' => 'before_publishing_reminder',
        ];

        return $workflowController->get_filtered_workflows($args);
    }

    /**
     * Check if we have any active workflow and posts which should schedule a
     * notification before publishing date.
     */
    public function processBeforePublishingJobs()
    {
        $workflows = $this->getFilteredWorkflows();

        if (!empty($workflows)) {
            // Workflows that expect to find posts waiting for being published.
            foreach ($workflows as $workflow) {
                $workflowPostStatusFilter = get_post_meta($workflow->workflow_post->ID, '_psppno_pubstatus', true);

                if (empty($workflowPostStatusFilter)) {
                    $workflowPostStatusFilter = 'future';
                }

                // Get posts accepted by this workflow, based on post type and scheduled publishing date.
                $posts = $this->getPostsRelatedToTheWorkflow(
                    $workflow->workflow_post->ID,
                    ['post_status' => $workflowPostStatusFilter]
                );

                // If we have posts, get each post and run the workflow, to schedule a notification
                if (!empty($posts)) {
                    foreach ($posts as $post) {
                        // If reminder pertains to a draft "due date," only send it if post date is still in the future
                        if ('future' != $post->post_status) {
                            if (strtotime($post->post_date_gmt) < strtotime(gmdate("Y-m-d H:i:s"))) {
                                continue;
                            }
                        }

                        if ($this->isNotificationScheduled($workflow->workflow_post->ID, $post->ID)) {
                            continue;
                        }

                        $notificationSentFlagMetaKey = $this->getNotificationSentFlagMetaKey(
                            $workflow->workflow_post->ID,
                            $post->post_status
                        );

                        // Go ahead and do the action to run workflows
                        $params = [
                            'event'     => 'before_publishing_reminder',
                            'event_key' => static::META_KEY_SELECTED,
                            'user_id'   => get_current_user_id(),
                            'params'    => [
                                'post_id'    => (int)$post->ID,
                                'new_status' => $post->post_status,
                                'old_status' => $post->post_status,
                            ],
                            'sent_flag' => $notificationSentFlagMetaKey,
                        ];

                        $workflow->run($params);
                    }
                }
            }
        }
    }

    protected function isNotificationScheduled($workflowId, $postId)
    {
        $cronArray = _get_cron_array();

        $expectedHooks = [WPCronAdapter::SEND_NOTIFICATION_HOOK,];

        if (!empty($cronArray)) {
            foreach ($cronArray as $time => $cronTasks) {
                foreach ($cronTasks as $hook => $dings) {
                    if (!in_array($hook, $expectedHooks)) {
                        continue;
                    }

                    foreach ($dings as $ding) {
                        if (((int)$ding['args'][0]['workflow_id'] === (int)$workflowId)
                            && ((int)$ding['args'][0]['event_args']['params']['post_id'] === (int)$postId)
                            && ($ding['args'][0]['event_args']['event'] === 'before_publishing_reminder')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function filter_workflow_actions($actions)
    {
        if (!is_array($actions) || empty($actions)) {
            $actions = [];
        }

        $actions[] = 'before_publishing_reminder';

        return $actions;
    }

    public function filter_action_params_for_log($paramsString, $log)
    {
        if ($log->event === self::META_VALUE_SELECTED) {
        }

        return $paramsString;
    }

    /**
     * @param string $label
     * @param string $event
     * @return string|void
     */
    public function filter_event_label($label, $event)
    {
        if ($event === 'before_publishing_reminder') {
            $label = $this->label;
        }

        return $label;
    }

    protected function getNotificationSentFlagMetaKey($workflowId, $postStatus)
    {
        $fragments = [
            'pp_notification_sent',
            static::META_VALUE_SELECTED,
            $workflowId,
            $postStatus,
        ];

        return implode('_', $fragments);
    }

    public function registerSentFlag(
        $workflow,
        $channel,
        $receiverData,
        $subject,
        $body,
        $deliveryResult,
        $async
    ) {
        if ($workflow->event_args['event'] !== 'before_publishing_reminder') {
            return;
        }

        if (isset($workflow->event_args['sent_flag'])) {
            add_post_meta($workflow->event_args['params']['post_id'], $workflow->event_args['sent_flag'], 1, true);
        }
    }

    /**
     * Get posts related to this workflow, applying the filters, in a reverse way, not founding a workflow related
     * to the post.
     *
     * @param $workflowPostId
     * @param array $args
     *
     * @return array
     */
    protected function getPostsRelatedToTheWorkflow($workflowPostId, $args = [])
    {
        $postStatus = (!empty($args['post_status'])) ? $args['post_status'] : 'future';

        $notificationSentFlagMetaKey = $this->getNotificationSentFlagMetaKey($workflowPostId, $postStatus);

        $posts = [];

        // Build the query
        $queryArgs = [
            'nopaging'      => true,
            'post_status'   => $postStatus,
            'no_found_rows' => true,
            'cache_results' => true,
            'meta_query'    => [
                [
                    'key'     => $notificationSentFlagMetaKey,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        // Check if the workflow filters by post type
        $workflowPostTypes = get_post_meta(
            $workflowPostId,
            Post_Type_Filter::META_KEY_POST_TYPE
        );

        if (!empty($workflowPostTypes)) {
            $queryArgs['post_type'] = $workflowPostTypes;
        }

        // Check if the workflow filters by category
        $workflowCategories = get_post_meta(
            $workflowPostId,
            Category::META_KEY_CATEGORY
        );

        if (!empty($workflowCategories)) {
            $queryArgs['category__in'] = $workflowCategories;
        }

        $query = new \WP_Query($queryArgs);

        if (!empty($query->posts)) {
            foreach ($query->posts as $post) {
                $posts[] = $post;
            }
        }

        return $posts;
    }
}
