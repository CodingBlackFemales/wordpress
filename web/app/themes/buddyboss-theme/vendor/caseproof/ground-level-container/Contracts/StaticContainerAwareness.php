<?php

declare (strict_types=1);
namespace BuddyBossTheme\GroundLevel\Container\Contracts;

use BuddyBossTheme\GroundLevel\Container\Container;
interface StaticContainerAwareness
{
    /**
     * Retrieves a container.
     *
     * @return \GroundLevel\Container\Container
     */
    public static function getContainer() : Container;
    /**
     * Sets a container.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public static function setContainer(Container $container) : void;
}
