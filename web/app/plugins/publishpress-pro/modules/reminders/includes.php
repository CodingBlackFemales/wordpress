<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     PublishPress\Reminders
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

defined('ABSPATH') or die('No direct script access allowed.');

use PublishPress\Legacy\Auto_loader;

if (!defined('PP_PRO_REMINDERS_LOADED')) {
    if (!defined('PP_REMINDERS_PATH_BASE')) {
        define('PP_REMINDERS_PATH_BASE', __DIR__);
    }

    // Register the library
    Auto_loader::register('\\PublishPress\\Addon\\Reminders', PP_REMINDERS_PATH_BASE . '/core');

    // Define the add-on as loaded
    define('PP_PRO_REMINDERS_LOADED', 1);
}
