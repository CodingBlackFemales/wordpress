<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 *
 */

if ( ! defined('WPPF2_FRAMEWORK_LOADED')) {
    require_once __DIR__ . '/defines.php';

    if (! class_exists('WPPF2\\Autoloader')) {
        require_once __DIR__ . '/src/Autoloader.php';
    }

    WPPF2\Autoloader::addNamespace('WPPF2', __DIR__ . '/src');
    WPPF2\Autoloader::register();
}
