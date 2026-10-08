<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Reminders\Workflow\Step\Event\Filter;

use PublishPress\Addon\Reminders\Factory;
use PublishPress\Addon\Reminders\Workflow\Step\Event\BeforePublishing;

class BeforeTimer extends BaseTimer
{
    const META_KEY_POST_STATUS_AMOUNT = '_psppno_pubbeforeamount';

    const META_KEY_POST_STATUS_UNIT = '_psppno_pubbeforeunit';

    /**
     * The constructor.
     *
     * @param string $step_name
     */
    public function __construct($step_name)
    {
        parent::__construct($step_name);

        // Filter to set the custom timestamp based on the workflow
        add_filter('publishpress_notifications_scheduled_time_for_notification', [$this, 'filterAsyncTimestamp'], 10, 3);
    }

    /**
     * @param $timestamp
     * @param $workflowId
     * @param $postId
     *
     * @return int|false
     */
    public function filterAsyncTimestamp($timestamp, $workflowId, $postId)
    {
        if (!$this->workflowHasTheFilterEnabled($workflowId)) {
            return $timestamp;
        }

        // Get the workflow's interval settings
        $amount = (int)get_post_meta($workflowId, static::META_KEY_POST_STATUS_AMOUNT, true);
        $unit   = get_post_meta($workflowId, static::META_KEY_POST_STATUS_UNIT, true);

        // Calculate the interval in seconds
        $interval = $this->calcTimestampInterval($amount, $unit);

        if (false === $interval) {
            return false;
        }

        $container = Factory::get_container();
        $utilTime  = $container['util_time'];

        $timestamp = $utilTime->getPostDateUnixtime($postId) - $interval;

        return $timestamp;
    }

    private function workflowHasTheFilterEnabled($workflowId)
    {
        return (bool)get_post_meta($workflowId, BeforePublishing::META_KEY_SELECTED, true);
    }
}
