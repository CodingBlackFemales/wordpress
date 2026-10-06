<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP\Filesystem;


use WPPF2\WP\Filesystem\Storage\StorageInterface;

class Filesystem implements FilesystemInterface, StorageInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @inheritDoc
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function exists($path)
    {
        return $this->storage->exists($path);
    }
}
