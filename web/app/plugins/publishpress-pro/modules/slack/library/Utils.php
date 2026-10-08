<?php
/**
 * @package     PublishPress\Slack
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Slack;

/**
 * Class Utils
 *
 * @package PublishPress\Addon\Slack
 */
class Utils
{
    /**
     * @param string $template
     * @param bool $return
     *
     * @return string|void
     */
    public static function load_template($template, $return = false)
    {
        if ($return) {
            ob_start();
        }

        if ($overridden_template = locate_template($template)) {
            load_template($overridden_template);
        } else {
            load_template(PP_SLACK_PATH_BASE . '/templates/' . $template);
        }

        if ($return) {
            return ob_get_clean();
        }
    }
}
