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
use PublishPress\Notifications\Workflow\Step\Event\Filter\Base;
use PublishPress\Notifications\Workflow\Step\Event\Filter\Filter_Interface;
use PublishPress\Notifications\Workflow\Workflow;

abstract class BaseTimer extends Base implements Filter_Interface
{
    const META_KEY_POST_STATUS_AMOUNT = '_psppno_pubamount';

    const META_KEY_POST_STATUS_UNIT = '_psppno_pubunit';

    const META_KEY_POST_STATUS = '_psppno_pubstatus';

    /**
     * Function to render and return the HTML markup for the
     * Field in the form.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function render()
    {
        $container = Factory::get_container();
        $view      = $container['view'];

        global $publishpress;

        $post_statuses = [];

        if ('event_beforepublishing' == $this->step_name && version_compare(PUBLISHPRESS_VERSION, '1.20.7', '>=')) {
            if (class_exists('PublishPress_Statuses')) {
                //$statuses = $publishpress->custom_status->get_custom_statuses([], true);
                $statuses = $publishpress->getPostStatuses([]);

                foreach ($statuses as $status_obj) {
                    $post_statuses[$status_obj->name] = $status_obj->label;
                }

                $post_statuses = array_diff_key($post_statuses,
                    get_post_stati(['public' => true, 'private' => true], 'object', 'OR'));

                if (empty($post_statuses['future'])) {
                    $post_statuses['future'] = __('Scheduled');
                }
            } else {
                $post_statuses = ['draft' => __('Draft'), 'pending' => __('Pending'), 'future' => __('Scheduled')];
            }

            $post_statuses = apply_filters('publishpress_reminder_statuses', $post_statuses);

            if (!$post_status = $this->get_metadata(static::META_KEY_POST_STATUS, true)) {
                $post_status = 'future';
            }
        } else {
            $post_status = 'future';
        }

        echo $view->render(
            'workflow_filter_timer',
            [
                'name'          => "publishpress_notif[{$this->step_name}_filters][timer]",
                'id'            => "publishpress_notif_{$this->step_name}_filters_timer",
                'labels'        => [
                    'amount'      => esc_html__('Amount', 'publishpress-pro'),
                    'unit'        => esc_html__('Unit of time', 'publishpress-pro'),
                    'hour'        => esc_html__('Hours', 'publishpress-pro'),
                    'day'         => esc_html__('Days', 'publishpress-pro'),
                    'week'        => esc_html__('Weeks', 'publishpress-pro'),
                    'month'       => esc_html__('Months', 'publishpress-pro'),
                    'post_status' => $post_statuses ? esc_html__('For content in this status', 'publishpress-pro') : '',
                ],
                'values'        => [
                    'amount'      => $this->get_metadata(static::META_KEY_POST_STATUS_AMOUNT, true),
                    'unit'        => $this->get_metadata(static::META_KEY_POST_STATUS_UNIT, true),
                    'post_status' => $post_status,
                ],
                'post_statuses' => $post_statuses,
            ],
            PUBLISHPRESS_PRO_DIR_PATH . '/modules/reminders/views'
        );
    }

    /**
     * Function to save the metadata from the metabox
     *
     * @param int $id
     * @param WP_Post $post
     */
    public function save_metabox_data($id, $post)
    {
        // Amount
        if (!isset($_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['amount'])) {
            $amount = '1';
        } else {
            $amount = $_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['amount'];
        }
        $amount = [$amount];

        $this->update_metadata_array($id, static::META_KEY_POST_STATUS_AMOUNT, $amount);

        // Unit
        if (!isset($_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['unit'])) {
            $unit = 'hour';
        } else {
            $unit = $_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['unit'];
        }
        $unit = [$unit];

        $this->update_metadata_array($id, static::META_KEY_POST_STATUS_UNIT, $unit);

        if ('event_beforepublishing' == $this->step_name) {
            // Post Status
            if (!isset($_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['post_status'])) {
                $post_status = 'future';
            } else {
                $post_status = $_POST['publishpress_notif']["{$this->step_name}_filters"]['timer']['post_status'];
            }
            $post_status = [$post_status];

            $this->update_metadata_array($id, static::META_KEY_POST_STATUS, $post_status);
        }
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
    public function get_run_workflow_query_args($query_args, $action_args)
    {
        return parent::get_run_workflow_query_args($query_args, $action_args);
    }

    /**
     * @param $amount
     * @param $unit
     *
     * @return bool|float|int
     */
    protected function calcTimestampInterval($amount, $unit)
    {
        $multipliers = [
            'hour' => HOUR_IN_SECONDS,
            'day'  => DAY_IN_SECONDS,
            'week' => WEEK_IN_SECONDS,
            'month' => MONTH_IN_SECONDS,
        ];

        // If we have issues in the settings, we abort the notification
        if (empty($amount) || !array_key_exists($unit, $multipliers)) {
            return false;
        }

        return $amount * $multipliers[$unit];
    }
}
