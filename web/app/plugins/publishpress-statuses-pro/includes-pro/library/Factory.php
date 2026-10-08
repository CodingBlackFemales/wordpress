<?php
/**
 * @package     PublishPress\Statuses
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2026 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 */

namespace PublishPress\Statuses;

defined('ABSPATH') or die('No direct script access allowed.');

/**
 * Class Factory
 */
abstract class Factory
{
    /**
     * @var Container
     */
    protected static $container = null;

    /**
     * @return Container
     */
    public static function get_container()
    {
        if (static::$container === null) {
            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/library/Services.php');
            $module = \PublishPress_Statuses_Pro::instance();
            $services = new Services($module);

            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/library/Container.php');
            static::$container = new Container();
            static::$container->register($services);
        }

        return static::$container;
    }
}
