<?php

namespace PublishPressPro;

use function defined;
use function add_action;
use function sprintf;
use function is_admin;
use function current_user_can;
use function esc_html__;
use function esc_html;
use function did_action;

function freePluginIsRunningAsStandAlone()
{
    return defined('PUBLISHPRESS_HOOKS_REGISTERED') && ! did_action(PUBLISHPRESS_PRO_ACTION_LOAD_BASE_PLUGIN);
}

function proPluginIsRunning()
{
    return defined('PUBLISHPRESS_PRO_LOADED');
}

function deprecatedSlackAddonIsRunning()
{
    return defined('PP_SLACK_LOADED');
}

function deprecatedRemindersAddonIsRunning()
{
    return defined('PP_REMINDERS_LOADED');
}

function showAdminNoticeAndLogError($message)
{
    if (is_admin() && current_user_can('activate_plugins')) {
        add_action(
            'admin_notices',
            function () use ($message) {
                $msg = sprintf(
                    '<strong>%s:</strong> %s',
                    esc_html__('Warning', 'publishpress-pro'),
                    esc_html($message)
                );

                echo "<div class='notice notice-error is-dismissible' style='color:black'><p>" . $msg . '</p></div>';
            },
            5
        );
    }
}

add_action('publishpress_planner_pro_loaded', function () {
    if (deprecatedSlackAddonIsRunning()) {
        showAdminNoticeAndLogError(
            __('Please, deactivate and remove PublishPress Slack before using PublishPress Pro.', 'publishpress-pro')
        );

        do_action(PUBLISHPRESS_PRO_ACTION_HALT);
    }

    if (deprecatedRemindersAddonIsRunning()) {
        showAdminNoticeAndLogError(
            __(
                'Please, deactivate and remove PublishPress Reminders before using PublishPress Pro.',
                'publishpress-pro'
            )
        );

        do_action(PUBLISHPRESS_PRO_ACTION_HALT);
    }
});


if (proPluginIsRunning()) {
    showAdminNoticeAndLogError(
        __(
            'It seems like you have multiple PublishPress Pro running. Please, deactivate and remove one of them.',
            'publishpress-pro'
        )
    );

    do_action(PUBLISHPRESS_PRO_ACTION_HALT);

    return false;
}

return true;
