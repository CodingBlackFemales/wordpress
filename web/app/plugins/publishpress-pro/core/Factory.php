<?php
/**
 * @package     PublishPressPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressPro;

use PublishPress\Psr\Container\ContainerInterface;
use WPPF2\Plugin\DIContainer;

/**
 * Class Factory
 */
abstract class Factory
{
    /**
     * @var ContainerInterface
     */
    protected static $container = null;

    /**
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        if (static::$container === null) {
            static::$container = new DIContainer();
            static::$container->register(new PluginServiceProvider());
        }

        return static::$container;
    }
}
