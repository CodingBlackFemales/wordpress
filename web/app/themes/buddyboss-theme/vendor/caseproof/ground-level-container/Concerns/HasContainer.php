<?php

declare (strict_types=1);
namespace BuddyBossTheme\GroundLevel\Container\Concerns;

use BuddyBossTheme\GroundLevel\Container\Container;
use BuddyBossTheme\GroundLevel\Container\Contracts\ContainerAwareness;
trait HasContainer
{
    /**
     * The container instance.
     *
     * @var \GroundLevel\Container\Container
     */
    protected Container $container;
    /**
     * Retrieves a container.
     *
     * @return \GroundLevel\Container\Container
     */
    public function getContainer() : Container
    {
        return $this->container;
    }
    /**
     * Sets a container.
     *
     * @param  \GroundLevel\Container\Container $container The container.
     * @return \GroundLevel\Container\Contracts\ContainerAwareness
     */
    public function setContainer(Container $container) : ContainerAwareness
    {
        $this->container = $container;
        return $this;
    }
}
