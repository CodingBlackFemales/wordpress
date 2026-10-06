<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP\Filesystem\Storage;


class Local implements StorageInterface
{
    /**
     * @inheritDoc
     */
    public function exists($path)
    {
        global $wp_filesystem;

        return $wp_filesystem->exists($path);
    }

    /**
     * @deprecated 3.12.0 Use exists() method instead.
     */
    public function has($path)
    {
        return $this->exists($path);
    }
}
