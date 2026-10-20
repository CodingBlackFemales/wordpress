<?php
/**
 * @package     PublishPress\Permissions\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2024 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.3
 */

namespace PublishPress\Permissions;

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
            require_once(PRESSPERMIT_PRO_ABSPATH . '/includes-pro/library/Services.php');
            $module   = presspermit();

            if (class_exists('\PublishPress\Permissions\Services')) { // unexplained conflict with Smart Plugin Manager
                $services = new Services($module);

                require_once(PRESSPERMIT_PRO_ABSPATH . '/includes-pro/library/Container.php');
                static::$container = new Container();
                static::$container->register($services);
            }
        }

        return static::$container;
    }
}
