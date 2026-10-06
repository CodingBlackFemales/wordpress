<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP\Filesystem\Storage;


interface StorageInterface
{
    /**
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);
}
