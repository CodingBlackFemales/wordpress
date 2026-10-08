<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2;


class Buffer implements BufferInterface
{
    /**
     * @inheritDoc
     */
    public function start()
    {
        ob_start();
    }

    /**
     * @inheritDoc
     */
    public function getClean()
    {
        return ob_get_clean();
    }
}
