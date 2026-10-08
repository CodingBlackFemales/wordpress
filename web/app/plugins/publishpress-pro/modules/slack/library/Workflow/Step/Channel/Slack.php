<?php
/**
 * @package     PublishPress\Slack
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Slack\Workflow\Step\Channel;

use Exception;
use PublishPress\Notifications\Workflow\Step\Channel\Base;
use PublishPress\Notifications\Workflow\Step\Channel\Channel_Interface;

class Slack extends Base implements Channel_Interface
{

    const META_KEY_SELECTED = '_psppno_chnslack';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = 'slack';
        $this->label = __('Slack', 'publishpress-pro');
        $this->icon  = PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/img/icon-slack.png';

        parent::__construct();

        // Add filter to return the user's channel's options
        add_filter('psppno_filter_workflow_channel_options', [$this, 'filter_workflow_channel_options'], 10, 3);
        add_filter('publishpress_notifications_channel_icon_class', [$this, 'filterChannelIconClass']);
        add_filter('publishpress_notifications_log_receiver_text', [$this, 'filterLogReceiverText'], 10, 3);
    }

    /**
     * Check if this channel is selected and triggers the notification.
     *
     * @param Workflow $workflow
     * @param array $receiverData
     * @param array $content
     * @param string $channel
     * @param bool $async
     *
     * @throws Exception
     */
    public function action_send_notification($workflow, $receiverData, $content, $channel, $async)
    {
        if (empty($receiverData['receiver'])) {
            return;
        }

        // Make sure we unserialize the content when it comes from async notifications.
        if (is_string($content)) {
            $content = maybe_unserialize($content);
        }

        // Get the user's slack channel
        $slack_channel = $this->get_slack_channel_for_receiver($workflow->workflow_post->ID, $receiverData['receiver']);

        // Check if the notification was already sent
        $signature  = $this->get_notification_signature(
            $content,
            $channel . ':' . ':' . $slack_channel . serialize($receiverData['receiver'])
        );
        $controller = $this->get_service('workflows_controller');

        // Check if the notification was already sent on this request.
        if ($controller->is_notification_signature_registered($signature)) {
            return;
        }

        $subject = html_entity_decode($content['subject']);

        $body = wpautop($content['body']);
        $body = apply_filters('the_content', $body);
        $body = str_replace(']]>', ']]&gt;', $body);

        $action_args = [
            'event_args'   => $workflow->event_args,
            'channel' => $slack_channel,
            'body'    => $body,
            'subject' => $subject,
        ];

        $result = $this->get_service('publishpress')->slack->send_notification($action_args);

        $deliveryResult = true;

        if (isset($result['response']) && is_wp_error($result['response'])) {
            $deliveryResult = $result['response']->get_error_message();
        }

        /**
         * @param WP_Post $workflow_post
         * @param array $action_args
         * @param string $channel
         * @param string $subject
         * @param string $body
         * @param array $deliveryResult
         */
        do_action(
            'publishpress_notif_notification_sending',
            $workflow,
            $channel,
            $receiverData,
            $subject,
            $body,
            $deliveryResult,
            $async
        );

        $controller->register_notification_signature($signature);
    }

    /**
     * Returns the list of Slack channel for each receiver. Detect if we have
     * an user or channel directly set, instead of the user ID.
     *
     * @param int $workflow_id
     * @param array $receiver
     *
     * @return array
     */
    public function get_slack_channel_for_receiver($workflow_id, $receiver)
    {
        $channel = null;

        // Is a user id?
        if (is_numeric($receiver)) {
            $channel = get_user_meta($receiver, 'psppno_workflow_' . $workflow_id . '_slack_channel', true);
        }

        // If empty, we use the global channel
        if (empty($channel)) {
            $channel = \PP_Slack::get_default_slack_channel();
        }

        // Is probably a Slack user or channel?
        return $channel;
    }

    /**
     * Action hooked when the user profile is saved
     *
     * @param int $user_id
     */
    public function action_save_user_profile($user_id)
    {
        if (isset($_POST['psppno_workflow_slack_channel'])) {
            if (!empty($_POST['psppno_workflow_slack_channel'])) {
                foreach ($_POST['psppno_workflow_slack_channel'] as $workflow_id => $channel) {
                    update_user_meta($user_id, 'psppno_workflow_' . (int)$workflow_id . '_slack_channel', $channel);
                }
            }
        }
    }

    /**
     * Returns a list of option fields to display in the user profile.
     *
     * 'options': [
     *     [
     *         'name'
     *         'html'
     *     ]
     *  ]
     *
     * @return array
     *
     * @throws Exception
     */
    protected function get_user_profile_option_fields()
    {
        $options = [];

        $defaultChannel = \PP_Slack::get_default_slack_channel();

        $html = $this->get_service('view')->render(
            'workflow_channel_text_field',
            [
                'name'        => 'psppno_workflow_slack_channel[%workflow_id%]',
                'id'          => 'psppno_channel_slack_channel_%workflow_id%',
                'value'       => '',
                'placeholder' => $defaultChannel,
                'label'       => esc_html__('Slack channel:', 'publishpress-pro'),
                'description' => esc_html__('Slack channel in which notification will be sent to. For example #general, @username, or user@email.com', 'publishpress-pro'),
            ]
        );

        $options[] = (object)[
            'name' => 'channel',
            'html' => $html,
        ];

        return $options;
    }

    /**
     * Filters the list of options for this channel, found in the user's meta
     *
     * @param array $options
     * @param int $user_id
     * @param int $workflow_id
     *
     * @return array
     */
    public function filter_workflow_channel_options($options, $user_id, $workflow_id)
    {
        $channel = get_user_meta($user_id, 'psppno_workflow_' . $workflow_id . '_slack_channel', true);

        $options = [
            'slack' => [
                'channel' => $channel,
            ],
        ];

        return $options;
    }

    public function filterChannelIconClass($channel)
    {
        if ($channel === $this->name) {
            return 'ppp-slack-icon-slack';
        }

        return $channel;
    }

    public function filterLogReceiverText($receiverText, $receiverData, $workflowId)
    {
        if (!isset($receiverData['channel']) || $receiverData['channel'] !== $this->name || !isset($receiverData['receiver'])) {
            return $receiverText;
        }

        if (is_numeric($receiverData['receiver'])) {
            $user = get_user_by('ID', $receiverData['receiver']);

            if (!is_object($user)) {
                return $receiverText;
            }

            $channel = $this->get_slack_channel_for_receiver($workflowId, $receiverData['receiver']);

            $receiverText = $user->user_nicename;
            $receiverText .= sprintf(
                '<span class="user-details muted">(user_id:%d, channel:%s)</span>',
                $user->ID,
                $channel
            );
        }

        return $receiverText;
    }
}
