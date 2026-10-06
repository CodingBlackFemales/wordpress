<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\Plugin;

use PublishPress\Pimple\Container;
use PublishPress\Psr\Container\ContainerExceptionInterface;
use PublishPress\Psr\Container\ContainerInterface;
use PublishPress\Psr\Container\NotFoundExceptionInterface;

/**
 * Class DIContainer. Implements the PSR-11 Container interface.
 *
 * @package WPPF2
 */
class DIContainer extends Container implements ContainerInterface
{
    /**
     * Finds an entry of the hooksHandler by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    public function get(string $id)
    {
        return $this->offsetGet($id);
    }

    /**
     * Returns true if the hooksHandler can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->offsetExists($id);
    }
}
