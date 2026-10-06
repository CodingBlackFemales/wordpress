<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     PublishPress\Slack
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

use PublishPress\Legacy\Auto_loader;

defined('ABSPATH') or die('No direct script access allowed.');


if (!defined('PP_PRO_SLACK_LOADED')) {
    if (!defined('PP_SLACK')) {
        define('PP_SLACK', 'Slack');
    }

    if (!defined('PP_SLACK_NAME')) {
        define('PP_SLACK_NAME', 'PublishPress Slack');
    }

    if (!defined('PP_SLACK_SLUG')) {
        define('PP_SLACK_SLUG', strtolower(PP_SLACK));
    }

    if (!defined('PP_SLACK_PATH_BASE')) {
        define('PP_SLACK_PATH_BASE', __DIR__);
    }

    if (!defined('PP_SLACK_PATH_CORE')) {
        define('PP_SLACK_PATH_CORE', PP_SLACK_PATH_BASE . PP_SLACK);
    }

    if (!defined('PUBLISHPRESS_SLACK_VERSION')) {
        define('PUBLISHPRESS_SLACK_VERSION', PUBLISHPRESS_PRO_VERSION);
    }

    if (!defined('PP_SLACK_MODULE_PATH')) {
        define('PP_SLACK_MODULE_PATH', PP_SLACK_PATH_BASE . '/modules/slack');
    }

    if (!defined('PP_SLACK_FILE')) {
        define('PP_SLACK_FILE', 'publishpress-slack/publishpress-slack.php');
    }

    if (!defined('PP_SLACK_ITEM_ID')) {
        define('PP_SLACK_ITEM_ID', '6728');
    }

    if (!defined('PP_SLACK_LIB_PATH')) {
        define('PP_SLACK_LIB_PATH', PP_SLACK_PATH_BASE . '/library');
    }

    if (!defined('PP_SLACK_OAUTH_URL')) {
        define('PP_SLACK_OAUTH_URL', 'https://publishpress.com');
    }

    // Register the library
    Auto_loader::register('\\PublishPress\\Addon\\Slack', PP_SLACK_PATH_BASE . '/library');

    // Define the add-on as loaded
    define('PP_PRO_SLACK_LOADED', 1);
}
