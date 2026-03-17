<?php

declare (strict_types=1);
namespace BuddyBossTheme\GroundLevel\Container\Contracts;

use BuddyBossTheme\GroundLevel\Container\Container;
interface LoadableDependency
{
    /**
     * Loads the dependency.
     *
     * This method is called automatically when the dependency is instantiated.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public function load(Container $container) : void;
}
