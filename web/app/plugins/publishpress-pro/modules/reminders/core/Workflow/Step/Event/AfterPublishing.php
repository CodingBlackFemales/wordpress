<?php
/**
 * @package     PublishPress\Reminders
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Reminders\Workflow\Step\Event;

use PublishPress\Addon\Reminders\Workflow\Step\Event\Filter\AfterTimer;
use PublishPress\Notifications\Workflow\Step\Event\Base;
use WP_Post;

class AfterPublishing extends Base
{

    const META_KEY_SELECTED = '_psppno_evtafterpublishing';

    const META_VALUE_SELECTED = 'afterpublishing';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = 'afterpublishing';
        $this->label = __('After the content is published', 'publishpress-pro');

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_events_metakeys', [$this, 'filter_events_metakeys']);
        add_filter('publishpress_notif_workflow_actions', [$this, 'filter_workflow_actions']);
        add_filter('publishpress_notifications_action_params_for_log', [$this, 'filter_action_params_for_log'], 10, 2);
        add_filter('publishpress_notifications_event_label', [$this, 'filter_event_label'], 10, 2);
        add_action('publishpress_notif_notification_sending', [$this, 'registerSentFlag'], 20, 7);
        add_action('transition_post_status', [$this, 'actionTransitionPostStatus'], 999, 3);
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
        if ('after_publishing_reminder' === $action_args['event']) {
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

        $filters[] = new AfterTimer($step_name);

        return parent::get_filters($filters);
    }

    public function filter_workflow_actions($actions)
    {
        if (!is_array($actions) || empty($actions)) {
            $actions = [];
        }

        $actions[] = 'after_publishing_reminder';

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
        if ($event === 'after_publishing_reminder') {
            $label = $this->label;
        }

        return $label;
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
        if ($workflow->event_args['event'] !== 'after_publishing_reminder') {
            return;
        }

        if (isset($workflow->event_args['sent_flag'])) {
            add_post_meta($workflow->event_args['params']['post_id'], $workflow->event_args['sent_flag'], 1, true);
        }
    }

    /**
     * Action called constantly to identify posts which need to trigger a reminder
     * before or after publishing.
     *
     * @param $new_status
     * @param $old_status
     * @param WP_Post $post
     */
    public function actionTransitionPostStatus($new_status, $old_status, $post)
    {
        // Ignores auto-save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ('publish' !== $new_status || $new_status === $old_status) {
            return;
        }

        // Ignore if the post_type is an internal post_type
        if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post->post_type) {
            return;
        }

        // Go ahead and do the action to run workflows
        $params = [
            'event'   => 'after_publishing_reminder',
            'event_key' => static::META_KEY_SELECTED,
            'user_id' => get_current_user_id(),
            'params'  => [
                'post_id'    => (int)$post->ID,
                'new_status' => $new_status,
                'old_status' => $old_status,
            ],
        ];

        do_action('publishpress_notifications_trigger_workflows', $params);
    }
}
